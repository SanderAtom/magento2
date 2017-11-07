<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

/**
 * Inject CardGate fee into Creditmemo.
 */
class Fee extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal {

	public function __construct( array $data = [] ) {
		parent::__construct( $data );
	}

	/**
	 * Collect CardGate fee for the credit memo.
	 */
	public function collect ( Creditmemo $creditmemo ) {
		$store = $creditmemo->getStore();

		// YYY: Creditmemo is not finished yet
		$totalFeeAmount = $baseTotalFeeAmount = $totalTaxAmount = $baseTotalTaxAmount = $totalFeeAmountInclTax = $baseTotalFeeAmountInclTax = 0;

		$creditmemo->setSubtotal( $creditmemo->getSubtotal() + $totalFeeAmount );
		$creditmemo->setBaseSubtotal( $creditmemo->getBaseSubtotal() + $baseTotalFeeAmount );

		$creditmemo->setTaxAmount( $creditmemo->getTaxAmount() + $totalTaxAmount );
		$creditmemo->setBaseTaxAmount( $creditmemo->getBaseTaxAmount() + $baseTotalTaxAmount );

		$creditmemo->setSubtotalInclTax( $creditmemo->getSubtotalInclTax() + $totalFeeAmountInclTax );
		$creditmemo->setBaseSubtotalInclTax( $creditmemo->getBaseSubtotalInclTax() + $baseTotalFeeAmountInclTax );

		$creditmemo->setGrandTotal( $creditmemo->getGrandTotal() + $totalFeeAmount + $totalTaxAmount );
		$creditmemo->setBaseGrandTotal( $creditmemo->getBaseGrandTotal() + $baseTotalFeeAmount + $baseTotalTaxAmount );

		return $this;
	}

}
