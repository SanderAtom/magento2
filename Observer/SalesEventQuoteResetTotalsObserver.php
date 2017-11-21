<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Observer;

use Cardgate\Payment\Model\Total\Fee;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * Resets quote totals.
 */
class SalesEventQuoteResetTotalsObserver implements \Magento\Framework\Event\ObserverInterface {

	public function execute( \Magento\Framework\Event\Observer $oObserver_ ) {
		$oQuote = $oObserver_->getEvent()->getQuote();
		$oQuote->setCardgatefeeAmount( 0 );
		$oQuote->setBaseCardgatefeeAmount( 0 );
		$oQuote->setCardgatefeeTaxAmount( 0 );
		$oQuote->setBaseCardgatefeeTaxAmount( 0 );
		$oQuote->setCardgatefeeInclTax( 0 );
		$oQuote->setBaseCardgatefeeInclTax( 0 );

		foreach ( $oQuote->getAllAddresses() as $oAddress ) {
			$aAssociatedTaxables = $oAddress->getAssociatedTaxables();
			if ( ! $aAssociatedTaxables ) {
				continue;
			}
			$aNewAssociatedTaxables = [];
			foreach ( $aAssociatedTaxables as $aExtraTaxable ) {
				if (
					$aExtraTaxable[CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE] != Fee::TYPE_FEE
					&& $aExtraTaxable[CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE] != Fee::CODE_FEE
				) {
					$aNewAssociatedTaxables[] = $aExtraTaxable;
				}
			}
			$oAddress->setAssociatedTaxables( $aNewAssociatedTaxables );
		}

		$oPayment = $oQuote->getPayment();
		$oPayment->setCardgatefeeAmount( 0 );
		$oPayment->setBaseCardgatefeeAmount( 0 );
		$oPayment->setCardgatefeeTaxAmount( 0 );
		$oPayment->setBaseCardgatefeeTaxAmount( 0 );
		$oPayment->setCardgatefeeInclTax( 0 );
		$oPayment->setBaseCardgatefeeInclTax( 0 );

		return $this;
	}

}
