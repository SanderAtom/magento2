<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Controller\Payment;

use \Magento\Framework\App\ObjectManager;

/**
 * Client redirect after payment action.
 */
class Redirect extends \Magento\Framework\App\Action\Action {

	public function execute() {
		$oSession = ObjectManager::getInstance()->get( \Magento\Checkout\Model\Session::class );
		$oRedirect = $this->resultRedirectFactory->create();

		$iOrderId = $this->getRequest()->getParam( 'reference' );
		$sStatus = $this->getRequest()->getParam( 'status' );
		$sTransactionId = $this->getRequest()->getParam( 'transaction' );

		try {
			if (
				empty( $iOrderId )
				|| empty( $sStatus )
				|| empty( $sTransactionId )
			) {
				throw new \Exception( 'Wrong parameters supplied' );
			}

			// If the callback hasn't been received (yet) the most recent status is fetched from the gateway instead
			// of relying on the provided status in the url.
			$oOrder = ObjectManager::getInstance()->create( \Magento\Sales\Model\Order::class )->loadByIncrementId( $iOrderId );
			if ( \Magento\Sales\Model\Order::STATE_NEW == $oOrder->getState() ) {
				$oGatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
				$sStatus = $oGatewayClient->transactions()->status( $sTransactionId );
			}

			if (
				'success' == $sStatus
				|| 'pending' == $sStatus
			) {
				$oSession->start();
				$oRedirect->setPath( 'checkout/onepage/success' );
			} else {
				throw new \Exception( 'Payment not completed' );
			}

		} catch ( \Exception $oException_ ) {

			$oSession->restoreQuote();
			$this->messageManager->addErrorMessage( __( $oException_->getMessage() ) );
			$oRedirect->setPath( 'checkout/cart' );
		}

		return $oRedirect;
	}

}
