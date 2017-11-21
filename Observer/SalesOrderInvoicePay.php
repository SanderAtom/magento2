<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Observer;

/**
 * Event to copy CardGate fee data from an order to an invoice.
 */
class SalesOrderInvoicePay implements \Magento\Framework\Event\ObserverInterface {

	public function execute( \Magento\Framework\Event\Observer $oObserver_ ) {
		$oInvoice = $oObserver_->getEvent()->getInvoice();
		$oOrder = $oInvoice->getOrder();

		$oInvoice->setCardgatefeeAmount( $oOrder->getCardgatefeeAmount() );
		$oInvoice->setBaseCardgatefeeAmount( $oOrder->getBaseCardgatefeeAmount() );
		$oInvoice->setCardgatefeeTaxAmount( $oOrder->getCardgatefeeTaxAmount() );
		$oInvoice->setBaseCardgatefeeTaxAmount( $oOrder->getBaseCardgatefeeTaxAmount() );
		$oInvoice->setCardgatefeeInclTax( $oOrder->getCardgatefeeInclTax() );
		$oInvoice->setBaseCardgatefeeInclTax( $oOrder->getBaseCardgatefeeInclTax() );

		return $this;
	}

}
