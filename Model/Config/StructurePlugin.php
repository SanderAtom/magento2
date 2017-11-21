<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Config;

/**
 * Config Structure plugin.
 */
class StructurePlugin {

	/**
	 * @var \Magento\Config\Model\Config\ScopeDefiner
	 */
	protected $_scopeDefiner;

	/**
	 * @var \Cardgate\Payment\Model\Config
	 */
	protected $_cgconfig;

	public function __construct( \Magento\Config\Model\Config\ScopeDefiner $scopeDefiner, \Cardgate\Payment\Model\Config $config ) {
		$this->_scopeDefiner = $scopeDefiner;
		$this->_cgconfig = $config;
	}

	/**
	 * Substitute payment section with CardGate configs.
	 */
	public function aroundGetElementByPathParts( \Magento\Config\Model\Config\Structure $subject, \Closure $proceed, array $pathParts ) {
		/** @var \Magento\Config\Model\Config\Structure\Element\Section $result **/
		$result = $proceed( $pathParts );

		if ( $pathParts[0] == 'cardgate' && count( $pathParts ) == 1 ) {
			// get all methods
			$allPaymentMethods = \Cardgate\Payment\Model\PaymentMethod::getAllPaymentMethods();

			// get all active methods
			$activePms = unserialize( $this->_cgconfig->getGlobal( 'active_pm' ) );
			if ( ! is_array( $activePms ) ) {
				$activePms = [];
			}
			$activePmIds = [];
			foreach ( $activePms as $pmRecord ) {
				$activePmIds[$pmRecord['id']] = $pmRecord['name'];
			}
			asort( $activePmIds, SORT_STRING | SORT_FLAG_CASE );
			asort( $allPaymentMethods, SORT_STRING | SORT_FLAG_CASE );
			$paymentMethods = $activePmIds;
			foreach ( $allPaymentMethods as $pm => $pmname ) {
				if ( ! isset( $paymentMethods[$pm] ) ) {
					$paymentMethods[$pm] = $pmname;
				}
			}

			$newData = $result->getData();
			$newPath = $pathParts[0];
			foreach ( $paymentMethods as $paymentMethod => $paymentMethodName ) {
				$paymentMethodResult = $proceed( [
					'cardgate_pm_skelleton_section'
				] );
				if ( isset( $paymentMethodResult ) && $paymentMethodResult instanceof \Magento\Config\Model\Config\Structure\Element\Section ) {
					$newChildren = [];
					foreach ( $paymentMethodResult->getChildren() as $child ) {
						$childData = array_merge( $child->getData(),
								[
									'id' => "cardgate_{$paymentMethod}",
									'path' => $newPath,
									'label' => sprintf( $child->getLabel(), $paymentMethodName ),
									'sortOrder' => strval( in_array( $paymentMethod, $activePmIds ) ? 10 : 100 ),
									'title' => $paymentMethodName,
									'pmid' => $paymentMethod,
									'pmname' => $paymentMethodName
								] );
						if ( $child instanceof \Magento\Config\Model\Config\Structure\Element\Group ) {
							$childData['children'] = [];
							foreach ( $child->getChildren() as $subchild ) {
								$childData['children'][$subchild->getId()] = array_merge( $subchild->getData(), [
									'path' => $newPath . '/' . $childData['id'],
									'label' => sprintf( $subchild->getLabel(), $paymentMethod )
								] );
							}
						}
						$newChildren[$childData['id']] = $childData;
					}
					$newData['children'] = array_merge( $newData['children'], $newChildren );
				}
			}

			$result->setData( $newData, $this->_scopeDefiner->getScope() );
		}
		return $result;
	}

}
