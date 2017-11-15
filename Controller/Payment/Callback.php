<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Controller\Payment;

use \Magento\Framework\App\ObjectManager;

/**
 * Callback handler action.
 */
class Callback extends \Magento\Framework\App\Action\Action {

	public function execute() {
		$oMasterConfig = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\Config\Master::class );
		$oResult = $this->resultFactory->create( \Magento\Framework\Controller\ResultFactory::TYPE_RAW );
		$oOrder = $oPayment = NULL;

		$sTransactionId = $this->getRequest()->getParam( 'transaction' );
		$sReference = $this->getRequest()->getParam( 'reference' );
		$iCode = (int)$this->getRequest()->getParam( 'code' );
		$sCurrency = $this->getRequest()->getParam( 'currency' );
		$iAmount = (int)$this->getRequest()->getParam( 'amount' );
		$sPt = $this->getRequest()->getParam( 'pt' );
		$sPmId = ( ! empty( $sPt ) ? $sPt : 'unknown' );
		$bUpdateCardgateData = FALSE;

		try {
			$oGatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
			if ( FALSE == $oGatewayClient->transactions()->verifyCallback( $_GET, $oGatewayClient->getSiteKey() ) ) {
				throw new \Exception( 'Hash verification failure' );
			}

			$oOrder = ObjectManager::getInstance()->create( \Magento\Sales\Model\Order::class )->loadByIncrementId( $sReference );
			$oPayment = $oOrder->getPayment();
			$bUpdateCardgateData = ! (
				$oPayment->getCardgateStatus() >= 200
				&& $oPayment->getCardgateStatus() < 300
			);

			// If the gateway is using a different payment method than us, update the payment method of our order to
			// match the one from the gateway.
			if ( $oPayment->getCardgatePaymentmethod() != $sPmId ) {
				$oPayment->setCardgatePaymentmethod( $pmId );
				$oOrder->addStatusHistoryComment( __( "Callback received for transaction %1 with paymentmethod '%2' but paymentmethod should be '%3'. Processing anyway.", $sTransactionId, $sPmId, $oOrder->getPayment()->getCardgatePaymentmethod() ) );
			}

			$oOrder->addStatusHistoryComment( __( "Update for transaction %1. Received status code %2.", $sTransactionId, $iCode ) );

			if ( $iCode < 100 ) {

				// 0xx pending
				if ( $oOrder->getState() != \Magento\Sales\Model\Order::STATE_NEW ) {
					$oOrder->addStatusHistoryComment( __( 'Transaction already processed.' ) );
				}

			} elseif ( $iCode < 200 ) {

				// 1xx auth phase
				if ( $oOrder->getState() != \Magento\Sales\Model\Order::STATE_NEW ) {
					$oOrder->addStatusHistoryComment( __( 'Transaction already processed.' ) );
				}

			} elseif ( $iCode < 300 ) {

				// 2xx success
				// Uncancel if needed.
				if ( $oOrder->isCanceled() ) {
					$oStockRegistry = ObjectManager::getInstance()->get( \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface::class );
					foreach ( $oOrder->getItems() as $oItem ) {
						$oStockItem = $oStockRegistry->getStockItem( $oItem->getProductId(), $oOrder->getStore()->getWebsiteId() );
						$oStockItem->setQty( $oStockItem->getQty() - $oItem->getQtyCanceled() );
						$oStockItem->save();

						$oItem->setQtyCanceled( 0 );
						$oItem->setTaxCanceled( 0 );
						$oItem->setDiscountTaxCompensationCanceled( 0 );
						$oItem->save();
					}
					$oOrder->addStatusHistoryComment( __( 'Transaction rebooked. Product stock reclaimed from inventory.' ) );
				}

				// Test if transaction has been processed already.
				$oPaymentRepository = ObjectManager::getInstance()->get( \Magento\Sales\Model\Order\Payment\Transaction\Repository::class );
				$oCurrentTransaction = $oPaymentRepository->getByTransactionId( $sTransactionId, $oPayment->getId(), $oOrder->getId() );
				if (
					! empty( $oCurrentTransaction )
					&& $oCurrentTransaction->getTxnType() == Transaction::TYPE_CAPTURE
				) {
					$oOrder->addStatusHistoryComment( __( 'Transaction already processed.' ) );
					$bUpdateCardgateData = FALSE;
					throw new \Exception( 'Transaction already processed.' );
				}

				// Test if payment has been processed already.
				if (
					$oPayment->getCardgateStatus() >= 200
					&& $oPayment->getCardgateStatus() < 300
				) {
					$oOrder->addStatusHistoryComment( __( 'Payment already processed in another transaction.' ) );
					$bUpdateCardgateData = FALSE;
					throw new \Exception( 'Payment already processed in another transaction.' );
				}

				// Do capture.
				$oPayment->setTransactionId( $sTransactionId );
				$oPayment->setCurrencyCode( $sCurrency );
				$oPayment->registerCaptureNotification( $iAmount / 100 );
				if ( $oMasterConfig->hasPMId( $sPt ) ) {
					$oPayment->setMethod( $oMasterConfig->getPMCodeById( $sPt ) );
				}

				if ( ! $oOrder->getEmailSent() ) {
					ObjectManager::getInstance()->get( \Magento\Sales\Model\Order\Email\Sender\OrderSender::class )->send( $oOrder );
				}
				$oInvoice = $oPayment->getCreatedInvoice();
				if ( ! empty( $oInvoice ) ) {
					ObjectManager::getInstance()->get( \Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class )->send( $oInvoice );
				} else {
					$oOrder->addStatusHistoryComment( __( 'Failed to create invoice.' ) );
					throw new \Exception( 'Failed to create invoice.' );
				}

			} elseif ( $iCode < 400 ) {

				// 3xx error
				try {
					$oOrder->registerCancellation( __( 'Transaction canceled.' ), FALSE );
				} catch ( \Exception $e_ ) {
					$oOrder->addStatusHistoryComment( __( "Failed to cancel order. Order state was : %1.", $oOrder->getState() . '/' . $oOrder->getStatus() ) );
					throw new \Exception( 'Failed to cancel order.' );
				}

			} elseif ( $iCode < 500 ) {

				// 4xx refund
				$oOrder->registerCancellation( __( "Transaction refund received. Amount %1.", $sCurrency . ' ' . round( $iAmount / 100, 2 ) ) );

			} elseif (
				$iCode >= 600
				&& $iCode < 700
			) {

				// 6xx notification from bank

			} elseif ( $iCode < 800 ) {

				// 7xx waiting for confirmation
			}

			// Set the output to a string that the gateway expects.
			$oResult->setContents( $sTransactionId . '.' . $iCode );

		} catch ( \Exception $oException_ ) {

			// Add the exception message to the output.
			$oResult->setContents( $oException_->getMessage() );
		}

		if (
			$oPayment != NULL
			&& $bUpdateCardgateData
		) {
			$oPayment->setCardgateStatus( $iCode );
			$oPayment->setCardgateTransaction( $sTransactionId );
			$oPayment->save();
		}

		if ( $oOrder != NULL ) {
			$oOrder->save();
		}

		return $oResult;
	}

}
