<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Controller\Payment;

use \Magento\Framework\App\ObjectManager;

/**
 * Start payment action.
 */
class Start extends \Magento\Framework\App\Action\Action {

	public function execute() {
		$oSession = ObjectManager::getInstance()->get( \Magento\Checkout\Model\Session::class );
		$oConfig = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\Config::class );
		$oRepository = ObjectManager::getInstance()->get( \Magento\Catalog\Model\ProductRepository::class );

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
			$oStock = ObjectManager::getInstance()->get( \Magento\CatalogInventory\Model\Stock\StockItemRepository::class );
			foreach ( $oOrder->getAllVisibleItems() as $oItem ) {
				$iItemQty = (int)( $oItem->getQtyOrdered() ? $oItem->getQtyOrdered() : $oItem->getQty() );
				$oProduct = $oItem->getProduct();
				$sUrl = $oProduct->getUrlModel()->getUrl( $oProduct );
				$oCartItem = $oCart->addItem(
					\cardgate\api\Item::TYPE_PRODUCT,
					$oItem->getSku(),
					$oItem->getName(),
					$iItemQty,
					round( $oItem->getPriceInclTax() * 100, 0 ),
					$sUrl
				);
				$oCartItem->setVat( round( $oItem->getTaxPercent(), 0 ) );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( round( ( $oItem->getTaxAmount() * 100 ) / $iItemQty, 0 ) );

				// Include stock in cart items will disable auto-capture on CardGate gateway if item
				// is backordered.
				$aStockData = $oStock->get( $oItem->getProduct()->getId() )->getData();
				if ( !!$aStockData['manage_stock'] ) {
					if ( $aStockData['qty'] <= -1 ) { // happens when backorders are allowed
						$oCartItem->setStock( 0 );
					} else {
						// The stock qty has already been lowered with the purchased quantity.
						$oCartItem->setStock( $iItemQty + $aStockData['qty'] );
					}
				}

				$fCalculatedGrandTotal += $oItem->getPriceInclTax() * $iItemQty;
				$fCalculatedVatTotal += $oItem->getTaxAmount();
			}

			$fShippingAmount = $oOrder->getShippingAmount();
			if ( $fShippingAmount > 0 ) {
				$oCartItem = $oCart->addItem(
					\cardgate\api\Item::TYPE_SHIPPING,
					'shipping',
					'Shipping Costs',
					1,
					round( $oOrder->getShippingInclTax() * 100, 0 )
				);
				$oCartItem->setVat( ceil( ( ( $oOrder->getShippingInclTax() / $fShippingAmount ) - 1 ) * 1000 ) / 10 );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( round( $oOrder->getShippingTaxAmount() * 100, 0 ) );

				$fCalculatedGrandTotal += $oOrder->getShippingInclTax();
				$fCalculatedVatTotal += $oOrder->getShippingTaxAmount();
			}

			$fDiscountAmount = $oOrder->getDiscountAmount();
			if ( $fDiscountAmount < 0 ) {
				$oCartItem = $oCart->addItem(
					\cardgate\api\Item::TYPE_DISCOUNT,
					'discount',
					'Discount',
					1,
					round( $fDiscountAmount * 100, 0 )
				);
				$oCartItem->setVat( ceil( ( ( $fDiscountAmount / ( $fDiscountAmount - $oOrder->getDiscountTaxCompensationAmount() ) ) - 1 ) * 1000 ) / 10 );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( round( $oOrder->getDiscountTaxCompensationAmount() * 100, 0 ) );

				$fCalculatedGrandTotal -= $fDiscountAmount;
				$fCalculatedVatTotal -= $oOrder->getDiscountTaxCompensationAmount();
			}

			// TODO this part is not yet working properly.
			$fCardGateFeeAmount = $oOrder->getCardgatefeeAmount();
			if ( $fCardGateFeeAmount > 0 ) {
				$oCartItem = $oCart->addItem(
					\cardgate\api\Item::TYPE_HANDLING,
					'cardgatefee',
					'Payment Fee',
					1,
					round( $oOrder->getCardgatefeeInclTax() * 100, 0 )
				);
				$oCartItem->setVat( ceil( ( ( $oOrder->getCardgatefeeInclTax() / $fCardGateFeeAmount ) - 1 ) * 1000 ) / 10 );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( round( $oOrder->getCardgatefeeTaxAmount() * 100, 0 ) );

				$fCalculatedGrandTotal += $oOrder->getCardgatefeeInclTax();
				$fCalculatedVatTotal += $oOrder->getCardgatefeeTaxAmount();
			}

			// Failsafe; correct VAT if needed.
			if ( $fCalculatedVatTotal != $oOrder->getTaxAmount() ) {
				$fVatCorrection = $oOrder->getTaxAmount() - $fCalculatedVatTotal;
				$oCartItem = $oCart->addItem(
					7,
					'cg-vatcorrection',
					'VAT Correction',
					1,
					round( $fVatCorrection * 100, 0 )
				);
				$oCartItem->setVat( 100 );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( round( $fVatCorrection * 100, 0 ) );

				$fCalculatedGrandTotal += $fVatCorrection;
			}

			// Failsafe; correct grandtotal if needed.
			$fGrandTotalCorrection = round( ( $oOrder->getGrandTotal() - $fCalculatedGrandTotal ) * 100, 0 );
			if ( abs( $fGrandTotalCorrection ) > 0 ) {
				$oCartItem = $oCart->addItem(
					( $fGrandTotalCorrection > 0 ) ? 1 : 4,
					'cg-correction',
					'Correction',
					1,
					round( $fGrandTotalCorrection * 100, 0 )
				);
				$oCartItem->setVat( 0 );
				$oCartItem->setVatIncluded( TRUE );
				$oCartItem->setVatAmount( 0 );
			}

			// If there was an issuer present (most likely iDeal), configure the transaction with this issuer. The
			// issuer is stored as additional data in the assignData method from Model/PaymentMethods.php.
			$oPayment = $oOrder->getPayment();
			$aData = $oPayment->getAdditionalInformation();

			if ( ! empty( $aData['issuer_id'] ) ) {
				$oTransaction->setIssuer( $aData['issuer_id'] );
			}

			// Register the transaction and finish up.
			$oTransaction->register();
			$oPayment->setCardgateTestmode( $oGatewayClient->getTestmode() );
			$oPayment->setCardgatePaymentmethod( $sPaymentMethod );
			$oPayment->setCardgateTransaction( $oTransaction->getId() );
			$oPayment->save();

			$oOrder->addStatusHistoryComment( __( "Transaction registered. Transaction ID %1", $oTransaction->getId() ) );
			$oOrder->save();

			$sActionUrl = $oTransaction->getActionUrl();
			if ( NULL !== $sActionUrl ) {
				// Redirect the consumer to the CardGate payment gateway.
				$this->getResponse()->setRedirect( $sActionUrl  );
			} else {
				// Payment methods without user interaction are not yet supported.
				throw new \Exception( 'unsupported payment action' );
			}

		} catch ( \Exception $oException_ ) {

			$this->messageManager->addErrorMessage( __( 'Error occurred while registering the transaction' ) . ' (' . $oException_->getMessage() . ')' );
			$oOrder->registerCancellation( __( 'Error occurred while registering the transaction' ) );
			$oOrder->save();
			$oSession->restoreQuote();
			$this->_redirect( 'checkout/cart' );
		}
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
		if ( !!( $sState = $oAddress_->getRegion() ) ) {
			$oConsumer_->$sMethod_()->setState( $sState );
		}
		$oConsumer_->$sMethod_()->setZipCode( $oAddress_->getPostcode() );
		$oConsumer_->$sMethod_()->setCountry( $oAddress_->getCountryId() );
	}

}
