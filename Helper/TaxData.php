<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Helper;

/**
 * Taxdata-helper plugin to add CardGate fee tax to invoices
 */
class TaxData extends \Magento\Tax\Helper\Data {

	/**
	 * Add CardGate fee when calculating taxes for invoices.
	 * @param \Magento\Tax\Helper\Data
	 * @param \Closure
	 * @param mixed \Magento\Sales\Model\Order or \Magento\Sales\Model\Order\Invoice or \Magento\Sales\Model\Order\Creditmemo
	 * @return array
	 */
	public function aroundGetCalculatedTaxes( \Magento\Tax\Helper\Data $aTaxData_, \Closure $oProceed_, $oSource_ ) {
		$aTaxClassAmount = [];
		if ( empty( $oSource_ ) ) {
			return $aTaxClassAmount;
		}
		$oCurrent = $oSource_;

		if (
			$oSource_ instanceof \Magento\Sales\Model\Order\Invoice
			|| $oSource_ instanceof \Magento\Sales\Model\Order\Creditmemo
		) {
			$oSource_ = $oCurrent->getOrder();
		}
		if ( $oCurrent == $oSource_ ) {
			$aTaxClassAmount = $this->calculateTaxForOrder( $oCurrent );
		} else {
			$aTaxClassAmount = $this->calculateTaxForItems( $oSource_, $oCurrent );

			// Apply any taxes for cardgatefee.
			$fCardgatefeeTaxAmount = $source->getCardgatefeeTaxAmount();
			$fOriginalCardgatefeeTaxAmount = $current->getCardgatefeeTaxAmount();
			if (
				$fCardgatefeeTaxAmount
				&& $fOriginalCardgatefeeTaxAmount
				&& $fCardgatefeeTaxAmount != 0
				&& floatval( $fOriginalCardgatefeeTaxAmount )
			) {
				$oOrderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails( $source->getId() );

				// An invoice or credit memo can have a different qty than its
				// order
				$fCardgatefeeRatio = $fCardgatefeeTaxAmount / $fOriginalCardgatefeeTaxAmount;
				$aItemTaxDetails = $oOrderTaxDetails->getItems();
				foreach ( $aItemTaxDetails as $oItemTaxDetail ) {

					// Aggregate taxable items associated with shipping
					if ( $oItemTaxDetail->getType() == \Cardgate\Payment\Model\Total\Fee::TYPE_FEE ) {
						$aTaxClassAmount = $this->_aggregateTaxes( $aTaxClassAmount, $oItemTaxDetail, $fCardgatefeeRatio );
					}
				}
			}

		}

		foreach( $aTaxClassAmount as $sKey => $aTax ) {
			$aTaxClassAmount[$sKey]['tax_amount'] = $this->priceCurrency->round( $aTax['tax_amount'] );
			$aTaxClassAmount[$sKey]['base_tax_amount'] = $this->priceCurrency->round( $aTax['base_tax_amount'] );
		}

		return array_values( $aTaxClassAmount );
	}

	/**
	 * Copied from \Magento\Tax\Helper\Data because it's private there.
	 * Accumulates the pre-calculated taxes for each tax class.
	 * @param array $taxClassAmount
	 * @param \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface $itemTaxDetail
	 * @param float $ratio
	 * @return array
	 */
	private function _aggregateTaxes( $aTaxClassAmount_, \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface $oItemTaxDetail_, $fRatio_ ) {
		$aItemAppliedTaxes = $oItemTaxDetail_->getAppliedTaxes();
		foreach ( $aItemAppliedTaxes as $oItemAppliedTax ) {
			$fTaxAmount = $oItemAppliedTax->getAmount() * $fRatio_;
			$fBaseTaxAmount = $oItemAppliedTax->getBaseAmount() * $fRatio_;

			if (
				0 == $fTaxAmount
				&& 0 == $fBaseTaxAmount
			) {
				continue;
			}
			$sTaxCode = $oItemAppliedTax->getCode();

			if ( ! isset( $aTaxClassAmount_[$sTaxCode] ) ) {
				$sTaxCode[$sTaxCode]['title'] = $oItemAppliedTax->getTitle();
				$sTaxCode[$sTaxCode]['percent'] = $oItemAppliedTax->getPercent();
				$sTaxCode[$sTaxCode]['tax_amount'] = $fTaxAmount;
				$sTaxCode[$sTaxCode]['base_tax_amount'] = $fBaseTaxAmount;
			} else {
				$sTaxCode[$sTaxCode]['tax_amount'] += $fTaxAmount;
				$sTaxCode[$sTaxCode]['base_tax_amount'] += $fBaseTaxAmount;
			}
		}

		return $aTaxClassAmount_;
	}

}
