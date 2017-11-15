<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model;

use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Cardgate\Payment\Model\Config\Master;

/**
 * CardGate Magento2 module config.
 */
class Config implements ConfigInterface {

	/**
	 * Active Paymentmethods as configured in my.cardgate.com (and fetched from
	 * RESTful API)
	 */
	private static $activePMIDs = [];

	/**
	 * @var Master
	 */
	private $_masterConfig;

	public function __construct( MutableScopeConfigInterface $scopeConfig, ConfigResource $configResource, Master $master ) {
		$this->_scopeConfig = $scopeConfig;
		$this->_configResource = $configResource;
		$this->_masterConfig = $master;
	}

	/**
	 * Retrieve information from CardGate configuration for given paymentmethod.
	 */
	public function getField( $method, $field, $storeId = NULL ) {
		return $this->_scopeConfig->getValue( 'payment/' . $method . '/' . $field, ScopeInterface::SCOPE_STORE, $storeId );
	}

	/**
	 * Set information info CardGate configuration for given paymentmethod and
	 * save configuration.
	 */
	public function setField( $method, $field, $value, $storeId = NULL ) {
		$this->_scopeConfig->setValue( 'payment/' . $method . '/' . $field, $value, ScopeInterface::SCOPE_STORE, $storeId );
		$this->_configResource->saveConfig( 'payment/' . $method . '/' . $field, $value, MutableScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0 );
	}

	/**
	 * Retrieve information from Global CardGate configuration.
	 */
	public function getGlobal( $field, $storeId = NULL ) {
		return $this->_scopeConfig->getValue( 'cardgate/global/' . $field, ScopeInterface::SCOPE_STORE, $storeId );
	}

	/**
	 * Set information info Global CardGate configuration and save configuration.
	 */
	public function setGlobal( $field, $value, $storeId = NULL ) {
		$this->_scopeConfig->setValue( 'cardgate/global/' . $field, $value, ScopeInterface::SCOPE_STORE, $storeId );
		$this->_configResource->saveConfig( 'cardgate/global/' . $field, $value, MutableScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0 );
	}

	/**
	 * Get active Paymentmethod ID's (CardGate style ID's).
	 */
	public function getActivePMIds( $storeId = 0 ) {
		if ( isset( self::$activePMIDs[$storeId] ) && is_array( self::$activePMIDs[$storeId] ) ) {
			return self::$activePMIDs[$storeId];
		}
		self::$activePMIDs[$storeId] = [];
		$activePmInfo = unserialize( $this->getGlobal( 'active_pm', $storeId ) );
		if ( !is_array($activePmInfo) ) {
			$activePmInfo = [];
		}
		foreach ( $activePmInfo as $activePm ) {

			self::$activePMIDs[$storeId][] = $activePm['id'];
		}
		return self::$activePMIDs[$storeId];
	}

	/**
	 * Sets method code.
	 */
	public function setMethodCode($methodCode) {
		return NULL;
		//$this->_methodCode = $methodCode;
	}

	/**
	 * Sets path pattern.
	 */
	public function setPathPattern($pathPattern) {
		return NULL;
		//$this->pathPattern = $pathPattern;
	}

	/**
	 * Retrieve information from payment configuration.
	 */
	public function getValue( $field, $storeId = NULL ) {
		return NULL;
		//return $this->_scopeConfig->getValue( sprintf( $this->_pathPattern, $this->_methodCode, $field ), ScopeInterface::SCOPE_STORE, $storeId );
	}

}
