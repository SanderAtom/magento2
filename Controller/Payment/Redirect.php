<?php
/**
 * Copyright © 2016 CardGate.
 * All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Cardgate\Payment\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;

/**
 * Client redirect after payment action
 *
 * @author DBS B.V.
 * @package Magento2
 */
class Redirect extends \Magento\Framework\App\Action\Action {

	/**
	 *
	 * @var Session
	 */
	protected $_checkoutSession;

	public function __construct ( \Magento\Framework\App\Action\Context $context, Session $checkoutSession ) {
		$this->_checkoutSession = $checkoutSession;
		parent::__construct( $context );
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Magento\Framework\App\ActionInterface::execute()
	 */
	public function execute () {
		$orderId = $this->getRequest()->getParam( 'reference' );
		$status = $this->getRequest()->getParam( 'status' );
		$transactionId = $this->getRequest()->getParam( 'transaction' );

		$resultRedirect = $this->resultRedirectFactory->create();

		try {
			if (
				empty( $orderId )
				|| empty( $status )
				|| empty( $transactionId )
			) {
				throw new \Exception( 'wrong parameters supplied' );
			}

			// If the callback hasn't been received (yet) the most recent status is fetched from the gateway instead
			// of relying on the provided status in the url.
			$order = ObjectManager::getInstance()->create( \Magento\Sales\Model\Order::class )->loadByIncrementId( $orderId );
			if ( \Magento\Sales\Model\Order::STATE_NEW == $order->getState() ) {
				$gatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
				$status = $gatewayClient->transactions()->status( $transactionId );
			}

			if (
				'success' == $status
				|| 'pending' == $status
			) {
				$this->_checkoutSession->start();
				$resultRedirect->setPath( 'checkout/onepage/success' );
			} else {
				throw new \Exception( 'payment not completed' );
			}
		} catch ( \Exception $e ) {
			$this->_checkoutSession->restoreQuote();
			$this->messageManager->addErrorMessage( __( $e->getMessage() ) );
			$resultRedirect->setPath( 'checkout/cart' );
		}

		return $resultRedirect;
	}

}
