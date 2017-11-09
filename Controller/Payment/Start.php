<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Controller\Payment;

#use Cardgate\Payment\Model\GatewayClient;
#use Cardgate\Payment\Model\Config;
#use Cardgate\Payment\Model\Config\Master;
#use Cardgate\Payment\Model\PaymentMethods;
#use Magento\Framework\Module\ModuleListInterface;
use \Magento\Framework\App\ObjectManager;

/**
 * Start payment action.
 */
class Start extends \Magento\Framework\App\Action\Action {

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	#protected $customerSession;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	#protected $checkoutSession;

	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
	#protected $scopeConfig;

	/**
	 * @var \Magento\Framework\UrlInterface
	 */
	#protected $urlBuilder;

	/**
	 * @var \Magento\Quote\Model\Quote
	 */
	#protected $quote = false;

	/**
	 * @var GatewayClient
	 */
	#private $_gatewayClient;

	/**
	 * @var \Cardgate\Payment\Model\Config
	 */
	#private $_cardgateConfig;

	/**
	 * @var \Cardgate\Payment\Model\Config\Master
	 */
	#private $_masterConfig;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 */
	/*
	 public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		GatewayClient $gatewayClient,
		Config $cardgateConfig,
		Master $masterConfig
	) {
		// $this->_logger = $logger;
		// $this->_logger->addDebug('some text or variable');
		$this->customerSession = $customerSession;
		$this->checkoutSession = $checkoutSession;
		$this->scopeConfig = $scopeConfig;
		$this->_gatewayClient = $gatewayClient;
		$this->_cardgateConfig = $cardgateConfig;
		$this->_masterConfig = $masterConfig;
		parent::__construct( $context );
	}
	*/

	public function execute() {
		$oSession = ObjectManager::getInstance()->get( \Magento\Checkout\Model\Session::class );
		$oConfig = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\Config::class );

		$oOrder = $oSession->getLastRealOrder();
		$iOrderId = $oOrder->getIncrementId();

		try {
			$oGatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
			$oTransaction = $oGatewayClient->transactions()->create(
				$oGatewayClient->getSiteId(),
				(int)round( $oOrder->getBaseGrandTotal() * 100 ),
				$oOrder->getBaseCurrencyCode()
			);

			$sCode = $oOrder->getPayment()->getMethodInstance()->getCode();
			$sPaymentMethod = substr( $sCode, 9 );
			$oTransaction->setPaymentMethod( $oGatewayClient->methods()->get( $sPaymentMethod ) );

			$oTransaction->setCallbackUrl( $this->_url->getUrl( 'cardgate/payment/callback' ) );
			$oTransaction->setRedirectUrl( $this->_url->getUrl( 'cardgate/payment/redirect' ) );
			$oTransaction->setReference( $iOrderId );
			$oTransaction->setDescription( str_replace( '%id%', $iOrderId, $oConfig->getGlobal( 'order_description' ) ) );

			// Add the consumer data to the transaction.
			$oConsumer = $oTransaction->getConsumer();
			$oBillingAddress = $oOrder->getBillingAddress();
			if ( !$oBillingAddress ) {
				throw new \Exception( 'missing or invalid billing address' );
			}
			$oConsumer->setEmail( $oBillingAddress->getEmail() );
			$oConsumer->setPhone( $oBillingAddress->getTelephone() );
			self::_convertAddress( $oBillingAddress, $oConsumer, 'address' );
			$oShippingAddress = $oOrder->getShippingAddress();
			if ( !$oShippingAddress ) {
				$oShippingAddress = &$oBillingAddress;
			}
			self::_convertAddress( $oShippingAddress, $oConsumer, 'shippingAddress' );

			// Add the cart items to the transaction.
			$fCalculatedGrandTotal = 0.00;
			$fCalculatedVatTotal = 0.00;
			$oCart = $oTransaction->getCart();

			/*
			$oStock = ObjectManager::getInstance()->get( \Magento\CatalogInventory\Model\Stock\StockItemRepository::class );
			foreach ( $oOrder->getAllVisibleItems() as $oItem ) {
				$iItemQty = (int)( $oItem->getQtyOrdered() ? $oItem->getQtyOrdered() : $oItem->getQty() );
				$oCartItem = $oCart->addItem(
					\cardgate\api\Item::TYPE_PRODUCT,
					$oItem->getSku(),
					$oItem->getName(),
					$iItemQty,
					round( $oItem->getPriceInclTax() * 100, 0 )
					//'http://www.apple.com/imac/'
				);
				#$oCartItem->setVat( round( $oItem->getTaxPercent(), 0 ) );
				#$oCartItem->setVatIncluded( TRUE );
				#$oCartItem->setVatAmount( round( ( $oItem->getTaxAmount() * 100 ) / $iItemQty, 0 ) );

				// Include stock in cart items will disable auto-capture on CardGate gateway if item
				// is backordered.
				#$aStockData = $oStock->get( $oItem->getProduct()->getId() )->getData();
				#if ( !!$aStockData['manage_stock'] ) {
				#	if ( $aStockData['qty'] <= -1 ) { // happens when backorders are allowed
				#		$oCartItem->setStock( 0 );
				#	} else {
				#		// The stock qty has already been lowered with the purchased quantity.
				#		$oCartItem->setStock( $iItemQty + $aStockData['qty'] );
				#	}
				#}

				#$fCalculatedGrandTotal += $oItem->getPriceInclTax() * $iItemQty;
				#$fCalculatedVatTotal += $oItem->getTaxAmount();
			}
			*/




			echo '<pre>';
			print_r ( $oTransaction );
			exit;

		} catch ( \Exception $oException_ ) {

			$this->messageManager->addErrorMessage( __( 'Error occurred while registering the transaction' ) . ' (' . $oException_->getMessage() . ')' );
			$oOrder->registerCancellation( __( 'Error occurred while registering the transaction' ) );
			$oOrder->save();
			$oSession->restoreQuote();
			$this->_redirect( 'checkout/cart' );
			return;
		}




echo '<pre>';
print_r ( $oTransaction );
exit;



		$fShippingAmount = $oOrder->getShippingAmount();
		if ( $fShippingAmount > 0 ) {
			$aCartItems[] = [
				'sku'        => 'shipping',
				'name'       => 'Shipping costs',
				'quantity'   => 1,
				'vat_amount' => round( $oOrder->getShippingTaxAmount() * 100, 0 ),
				'vat'        => ceil( ( ( $oOrder->getShippingInclTax() / $fShippingAmount ) - 1 ) * 1000 ) / 10,
				'price'      => round( $oOrder->getShippingInclTax() * 100, 0 ),
				'vat_inc'    => 1,
				'type'       => 2
			];
			$fCalculatedGrandTotal += $oOrder->getShippingInclTax();
			$fCalculatedVatTotal += $oOrder->getShippingTaxAmount();

		}

		$fDiscountAmount = $oOrder->getDiscountAmount();
		if ( $fDiscountAmount < 0 ) {
			$aCartItems[] = [
				'sku'        => 'discount',
				'name'       => 'Discount',
				'quantity'   => 1,
				'vat_amount' => round( $oOrder->getDiscountTaxCompensationAmount() * 100, 0 ),
				'vat'        => ceil( ( ( $fDiscountAmount / ( $fDiscountAmount - $oOrder->getDiscountTaxCompensationAmount() ) ) - 1 ) * 1000 ) / 10,
				'price'      => round( $fDiscountAmount * 100, 0 ),
				'vat_inc'    => 1,
				'type'       => 4
			];
			$fCalculatedGrandTotal -= $fDiscountAmount;
			$fCalculatedVatTotal -= $oOrder->getDiscountTaxCompensationAmount();
		}

		// TODO this part is not yet working properly.
		$fCardGateFeeAmount = $oOrder->getCardgatefeeAmount();
		if ( $fCardGateFeeAmount > 0 ) {
			$aCartItems[] = [
				'sku'        => 'cardgatefee',
				'name'       => 'Payment Fee',
				'quantity'   => 1,
				'vat_amount' => round( $oOrder->getCardgatefeeTaxAmount() * 100, 0 ),
				'vat'        => ceil( ( ( $oOrder->getCardgatefeeInclTax() / $fCardGateFeeAmount ) - 1 ) * 1000 ) / 10,
				'price'      => round( $oOrder->getCardgatefeeInclTax() * 100, 0 ),
				'vat_inc'    => 1,
				'type'       => 5
			];
			$fCalculatedGrandTotal += $oOrder->getCardgatefeeInclTax();
			$fCalculatedVatTotal += $oOrder->getCardgatefeeTaxAmount();
		}

		// Failsafe; correct VAT if needed.
		if ( $fCalculatedVatTotal != $oOrder->getTaxAmount() ) {
			$fVatCorrection = $oOrder->getTaxAmount() - $fCalculatedVatTotal;
			$aCartItems[] = [
				'sku'        => 'cg-vatcorrection',
				'name'       => 'VAT Correction',
				'quantity'   => 1,
				'vat_amount' => round( $fVatCorrection * 100, 0 ),
				'vat'        => 100,
				'price'      => round( $fVatCorrection * 100, 0 ),
				'vat_inc'    => 1,
				'type'       => 7
			];
			$fCalculatedGrandTotal += $fVatCorrection;
		}

		// Failsafe; correct grandtotal if needed.
		if ( $fCalculatedGrandTotal != $oOrder->getGrandTotal() ) {
			$fGrandTotalCorrection = $oOrder->getGrandTotal() - $fCalculatedGrandTotal;
			$aCartItems[] = [
				'sku'        => 'cg-correction',
				'name'       => 'Correction',
				'quantity'   => 1,
				'vat_amount' => 0,
				'vat'        => 0,
				'price'      => round( $fGrandTotalCorrection * 100, 0 ),
				'vat_inc'    => 1,
				'type'       => ( $fGrandTotalCorrection > 0 ) ? 1 : 4
			];
		}









		$aData['cartitems'] = $aCartItems;


		echo '<pre>';
		print_r ( $aData );
		exit;

		$code = $order->getPayment()
			->getMethodInstance()
			->getCode();
		$paymentmethod = substr( $code, 9 );

		/**
		 *
		 * @var \Magento\Sales\Model\Order\Payment $payment
		 */
		$payment = $order->getPayment();

		$additional = $payment->getAdditionalInformation();
		unset( $additional['method_title'] );
		$data = array_merge( $additional, $data );
		//try {
			$gatewayResult = $this->_gatewayClient->postRequest( 'payment/' . $paymentmethod . '/', $data );
		//} catch ( \Exception $e ) {
			// YYY: log error here
		//}

		if (
			! isset( $gatewayResult )
			|| ! is_object( $gatewayResult )
		) {
			$this->messageManager->addErrorMessage( __( 'Error occurred while communicating with the payment service provider' ) );
			$order->registerCancellation( 'Error occurred while communicating with the payment service provider' );
			$order->save();
			$this->checkoutSession->restoreQuote();
			$this->_redirect( 'checkout/cart' );
			return;
		} elseif ( ! isset( $gatewayResult->success ) || $gatewayResult->success != true || ! isset( $gatewayResult->payment ) || ! isset( $gatewayResult->payment->transaction_id ) ) {
			$this->messageManager->addErrorMessage( __( 'Error occurred while registering the transaction' ) . ' (' . $gatewayResult->warning . ( isset( $gatewayResult->error ) ? ' #' . $gatewayResult->error->code : '' ) . ')' );
			$order->registerCancellation(
					__( 'Error occurred while registering the transaction' ) . $gatewayResult->warning . ' // ' . ( isset( $gatewayResult->error ) ? ' #' . $gatewayResult->error->message . ' #' . $gatewayResult->code : '' ) . ')' );
			$order->save();
			$this->checkoutSession->restoreQuote();
			$this->_redirect( 'checkout/cart' );
			return;
		}

		// At this point 'success' is true
		$payment->setCardgateTestmode( $this->_gatewayClient->getTestmode() );
		$payment->setCardgatePaymentmethod( $paymentmethod );
		$payment->setCardgateTransaction( $gatewayResult->payment->transaction_id );
		$payment->save();

		$order->addStatusHistoryComment( __("Transaction registered. Transaction ID %1", $gatewayResult->payment->transaction_id ), PaymentMethods::ORDER_STATUS_AUTHORIZED );
		$order->save();

		$this->getResponse()->setRedirect( $gatewayResult->payment->url );
		return;

	}

	/**
	 * Converts a Magento address object to a cardgate consumer address.
	 * @return array
	 */
	private static function _convertAddress( \Magento\Sales\Model\Order\Address &$oAddress_, \cardgate\api\Consumer &$oConsumer_, $sMethod_ ) {
		$oConsumer_->$sMethod_()->setFirstName( $oAddress_->getFirstname() );
		$oConsumer_->$sMethod_()->setLastName( $oAddress_->getLastname() );
		if ( !!( $sCompany = $oAddress_->getCompany() ) ) {
			$oConsumer_->$sMethod_()->setCompany( $sCompany );
		}
		$oConsumer_->$sMethod_()->setAddress( implode( PHP_EOL, $oAddress_->getStreet() ) );
		$oConsumer_->$sMethod_()->setCity( $oAddress_->getCity() );
		// TODO update the client lib to also accept states.
		//$oConsumer_->$sMethod_()->setState( $oAddress_->getRegion() );
		$oConsumer_->$sMethod_()->setZipCode( $oAddress_->getPostcode() );
		$oConsumer_->$sMethod_()->setCountry( $oAddress_->getCountryId() );


		/*
		$sPrefix = ( $bIsShipping_ ? 'shipto_' : '' );
		return [



			$sPrefix . 'state' => ,

			$sPrefix . 'country_id' => $oAddress_->getCountryId(),
			$sPrefix . 'phone' => $oAddress_->getTelephone(),
			$sPrefix . 'email' => $oAddress_->getEmail()
		];
		*/
	}






	/**
	 * Return checkout quote object
	 *
	 * @return \Magento\Quote\Model\Quote
	 */
	//protected function getQuote () {
	//	if ( ! $this->quote ) {
	//		$this->quote = $this->checkoutSession->getQuote();
	//	}
	//	return $this->quote;
	//}

	/**
	 * Returns a list of action flags [flag_key] => boolean
	 *
	 * @return array
	 */
	//public function getActionFlagList () {
	//	return [];
	//}

	/**
	 * Returns before_auth_url redirect parameter for customer session
	 *
	 * @return null
	 */
	//public function getCustomerBeforeAuthUrl () {
	//	return;
	//}

	/**
	 * Returns login url parameter for redirect
	 *
	 * @return string
	 */
	//public function getLoginUrl () {
	//	return $this->_customerUrl->getLoginUrl();
	//}

	/**
	 * Returns action name which requires redirect
	 *
	 * @return string
	 */
	//public function getRedirectActionName () {
	//	return 'start';
	//}
}
