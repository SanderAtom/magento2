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
class UpdatePM extends \Magento\Framework\App\Action\Action {

	public function execute() {
		$sPaymentMethod = $this->getRequest()->getParam( 'pm' );

		$oSession = ObjectManager::getInstance()->get( \Magento\Checkout\Model\Session::class );
		$oQuote = $oSession->getQuote();
		$oQuote->getPayment()->setMethod( $sPaymentMethod );
		$oQuote->save();

		$oResult = $this->resultFactory->create( \Magento\Framework\Controller\ResultFactory::TYPE_RAW );
		$oResult->setContents( 'OK' . $sPaymentMethod );
		return $oResult;
	}

}
