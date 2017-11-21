<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model;

/**
 * CardGate Magento2 module config.
 */
class Config implements \Magento\Payment\Model\Method\ConfigInterface {

	/**
	 * Active Paymentmethods as configured in my.cardgate.com (and fetched from
	 * RESTful API)
	 */
	private static $activePMIDs = [];

	public function __construct( \Magento\Framework\App\Config\MutableScopeConfigInterface $oScopeConfig_ ) {
		$this->_scopeConfig = $oScopeConfig_;
	}

	/**
	 * Retrieve information from CardGate configuration for given paymentmethod.
	 * @return mixed
	 */
	public function getField( $sMethod_, $sField_, $iStoreId_ = NULL ) {
		return $this->_scopeConfig->getValue( 'payment/' . $sMethod_ . '/' . $sField_, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $iStoreId_ );
	}

	/**
	 * Set information info CardGate configuration for given paymentmethod and
	 * save configuration.
	 */
	public function setField( $sMethod_, $sField_, $mValue_, $iStoreId_ = NULL ) {
		$this->_scopeConfig->setValue( 'payment/' . $sMethod_ . '/' . $sField_, $mValue_, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $iStoreId_ );
	}

	/**
	 * Retrieve information from Global CardGate configuration.
	 * @return mixed
	 */
	public function getGlobal( $sField_, $iStoreId_ = NULL ) {
		return $this->_scopeConfig->getValue( 'cardgate/global/' . $sField_, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $iStoreId_ );
	}

	/**
	 * Set information info Global CardGate configuration and save configuration.
	 */
	public function setGlobal( $sField_, $mValue_, $iStoreId_ = NULL ) {
		$this->_scopeConfig->setValue( 'cardgate/global/' . $sField_, $mValue_, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $iStoreId_ );
	}

	/**
	 * Get active Paymentmethod ID's (CardGate style ID's).
	 * @return array
	 */
	public function getActivePMIds( $iStoreId_ = 0 ) {
		if (
			isset( self::$activePMIDs[$iStoreId_] )
			&& is_array( self::$activePMIDs[$iStoreId_] )
		) {
			return self::$activePMIDs[$iStoreId_];
		}
		self::$activePMIDs[$iStoreId_] = [];
		$activePmInfo = unserialize( $this->getGlobal( 'active_pm', $iStoreId_ ) );
		if ( ! is_array( $activePmInfo ) ) {
			$activePmInfo = [];
		}
		foreach ( $activePmInfo as $activePm ) {
			self::$activePMIDs[$iStoreId_][] = $activePm['id'];
		}
		return self::$activePMIDs[$iStoreId_];
	}

	/**
	 * Sets method code.
	 */
	public function setMethodCode( $sMethodCode_ ) {
		return NULL;
	}

	/**
	 * Sets path pattern.
	 */
	public function setPathPattern( $sPathPattern_ ) {
		return NULL;
	}

	/**
	 * Retrieve information from payment configuration.
	 */
	public function getValue( $sField_, $iStoreId_ = NULL ) {
		return NULL;
	}

}
