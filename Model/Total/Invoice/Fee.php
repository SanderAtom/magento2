<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Total\Invoice;

/**
 * Inject Fee into invoice (for tax injection see Helper/TaxData).
 */
class Fee extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal {

	public function __construct( array $data = [] ) {
		parent::__construct( $data );
	}

	public function collect ( \Magento\Sales\Model\Order\Invoice $invoice ) {
		$store = $invoice->getStore();
		$order = $invoice->getOrder();
		if ( $invoice->isLast() ) {
			$invoice->setTaxAmount( $invoice->getTaxAmount() + $order->getCardgatefeeTaxAmount() );
			$invoice->setBaseTaxAmount( $invoice->getBaseTaxAmount() + $order->getBaseCardgatefeeTaxAmount() );
			$invoice->setGrandTotal( $invoice->getGrandTotal() + $order->getCardgatefeeInclTax() );
			$invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $order->getBaseCardgatefeeInclTax() );
		}

		return $this;
	}

}
