<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Observer;

/**
 * Event to copy CardGate fee data from a quote to an order.
 */
class SalesEventQuoteSubmitBeforeObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver {

	public function execute( \Magento\Framework\Event\Observer $oObserver_ ) {
		$oQuote = $oObserver_->getEvent()->getQuote();
		$oOrder = $oObserver_->getEvent()->getOrder();

		$oOrder->setCardgatefeeAmount( $oQuote->getCardgatefeeAmount() );
		$oOrder->setBaseCardgatefeeAmount( $oQuote->getBaseCardgatefeeAmount() );
		$oOrder->setCardgatefeeTaxAmount( $oQuote->getCardgatefeeTaxAmount() );
		$oOrder->setBaseCardgatefeeTaxAmount( $oQuote->getBaseCardgatefeeTaxAmount() );
		$oOrder->setCardgatefeeInclTax( $oQuote->getCardgatefeeInclTax() );
		$oOrder->setBaseCardgatefeeInclTax( $oQuote->getBaseCardgatefeeInclTax() );

		return $this;
	}

}
