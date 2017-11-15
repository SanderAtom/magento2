<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model;

use \Magento\Framework\App\ObjectManager;

/**
 * Base Payment class from which all paymentmethods extend.
 */
class PaymentMethods extends \Magento\Payment\Model\Method\AbstractMethod {

	/**
	 * @see /web/js/view/payment/method-renderer
	 * @var string
	 */
	public static $renderer = 'paymentmethods';

	/**
	 * @var string
	 */
	protected $_code = 'unknown';

	/**
	 * @var string
	 */
	protected $_formBlockType = 'Cardgate\Payment\Block\Form\DefaultForm';

	/**
	 * @var string
	 */
	protected $_infoBlockType = 'Cardgate\Payment\Block\Info\DefaultInfo';

	/**
	 * @var boolean
	 */
	protected $_isOffline = FALSE;

	/**
	 * @var boolean
	 */
	protected $_canReviewPayment = TRUE;

	/**
	 * @var boolean
	 */
	protected $_canRefund = TRUE;

	/**
	 * @var boolean
	 */
	protected $_canRefundInvoicePartial = TRUE;

	public function __construct(
		\Magento\Framework\Model\Context $oContext_,
		\Magento\Framework\Registry $oRegistry_,
		\Magento\Framework\Api\ExtensionAttributesFactory $oExtensionFactory_,
		\Magento\Framework\Api\AttributeValueFactory $oCustomAttributeFactory_,
		\Magento\Payment\Helper\Data $oPaymentData_,
		\Magento\Framework\App\Config\ScopeConfigInterface $oScopeConfig_,
		\Magento\Payment\Model\Method\Logger $oLogger_,
		\Cardgate\Payment\Model\Config\Master $oMaster_,
		\Cardgate\Payment\Model\Config $oConfig_,
		\Magento\Sales\Model\Order\Email\Sender\OrderSender $oOrderSender_,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $oInvoiceSender_,
		\Magento\Sales\Model\Order\Payment\Transaction\Repository $oTransactionRepository_,
		\Magento\Framework\Model\ResourceModel\AbstractResource $oResource_ = NULL,
		\Magento\Framework\Data\Collection\AbstractDb $oResourceCollection_ = NULL,
		array $aData_ = []
	) {

		// Compose payment_code.
		// NOTE as of v2.2.1 of Magento the class name includes Interceptor which needs to be stripped off.
		$sClass = get_called_class();
		if ( 'Interceptor' == substr( $sClass, -11 ) ) {
			$sClass = substr( $sClass, 0, -12 );
		}
		$this->_code = substr( $sClass, strrpos( $sClass, '\\' ) + 1 );

		// YYY: .. nah ..
		if ( $this->_code == 'PaymentMethods' ) {
			// .. naaaah ..
		} else {
			$this->_code = 'cardgate_' . $this->_code;
		}

		parent::__construct(
			$oContext_,
			$oRegistry_,
			$oExtensionFactory_,
			$oCustomAttributeFactory_,
			$oPaymentData_,
			$oScopeConfig_,
			$oLogger_,
			$oResource_,
			$oResourceCollection_,
			$aData_
		);
	}

	public function getFeeForQuote( \Magento\Quote\Model\Quote $oQuote_, \Magento\Quote\Model\Quote\Address\Total $oTotal_ = NULL ) {
		$oConfig = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\Config::class );
		$oCatalogHelper = ObjectManager::getInstance()->get( \Magento\Catalog\Helper\Data::class );

		if ( ! is_null( $oTotal_ ) ) {
			$fCalculatedTotal = array_sum( $oTotal_->getAllBaseTotalAmounts() );
		} else {
			$fCalculatedTotal = 0 - $oQuote_->getPayment()->getBaseCardgatefeeInclTax();
			foreach ( $oQuote_->getAllAddresses() as $oAddress ) {
				$fCalculatedTotal += $oAddress->getBaseGrandTotal();
			}
		}
		$fFeeFixed = floatval( $oConfig->getField( $this->_code, 'paymentfee_fixed' ) );
		$fFeePercentage = floatval( $oConfig->getField( $this->_code, 'paymentfee_percentage' ) );
		$fFee = 0;
		if ( $fFeePercentage > 0 ) {
			$fFee = $fCalculatedTotal * ( $fFeePercentage / 100 );
		}
		$fFee = round( $fFee + $fFeeFixed, 4 );

		$sTaxClassId = $oConfig->getGlobal( 'paymentfee_tax_class' );
		$bFeeInclTax = !!$oConfig->getGlobal( 'paymentfee_includes_tax' );
		$oPseudoProduct = new \Magento\Framework\DataObject();
		$oPseudoProduct->setTaxClassId( $sTaxClassId );

		$fPriceExcl = $oCatalogHelper->getTaxPrice( $oPseudoProduct, $fFee, FALSE, $oQuote_->getShippingAddress(), $oQuote_->getBillingAddress(), $oQuote_->getCustomerTaxClassId(), $oQuote_->getStore(), $bFeeInclTax );
		$fPriceIncl = $oCatalogHelper->getTaxPrice( $oPseudoProduct, $fFee, TRUE, $oQuote_->getShippingAddress(), $oQuote_->getBillingAddress(), $oQuote_->getCustomerTaxClassId(), $oQuote_->getStore(), $bFeeInclTax );

		return ObjectManager::getInstance()->create( \Cardgate\Payment\Model\Total\FeeData::class, [
			'amount'           => $fPriceExcl,
			'tax_amount'       => ( $fPriceIncl - $fPriceExcl ),
			'tax_class'        => $sTaxClassId,
			'fee_includes_tax' => $bFeeInclTax
		] );
	}

	public function assignData( \Magento\Framework\DataObject $oData_ ) {
		$aAdditional = $oData_->getAdditionalData();
		if ( ! is_array( $aAdditional ) ) {
			return $this;
		}
		$oInfo = $this->getInfoInstance();
		foreach ( $aAdditional as $sKey => $mValue ) {
			if ( is_scalar( $mValue ) ) {
				$oInfo->setAdditionalInformation( $sKey, $mValue );
			}
		}
		return $this;
	}

	public function refund( \Magento\Payment\Model\InfoInterface $oPayment_, $fAmount_ ) {
		$oOrder = $oPayment_->getOrder();

		try {
			$oGatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
			$oTransaction = $oGatewayClient->transactions()->get( $oPayment_->getCardgateTransaction() );

			if ( $oTransaction->canRefund() ) {
				$oTransaction->refund( (int)( $fAmount_ * 100 ) );
			} else {
				throw new \Exception( 'refund not allowed' );
			}

		} catch ( \Exception $oException_ ) {

			$oOrder->addStatusHistoryComment( __( 'Error occurred while registering the refund (%1)', $oException_->getMessage() ) );
			throw $oException_;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPayableTo() {
		return $this->getConfigData( 'payable_to' );
	}

	/**
	 * @return string
	 */
	public function getMailingAddress() {
		return $this->getConfigData( 'mailing_address' );
	}

	/**
	 * @see \Magento\Payment\Model\Method\AbstractMethod::acceptPayment()
	 */
	public function acceptPayment( \Magento\Payment\Model\InfoInterface $oInfo_ ) {
		return TRUE;
	}

}
