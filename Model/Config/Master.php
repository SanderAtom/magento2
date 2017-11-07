<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;

class Master {

	/**
	 * @var array
	 */
	private $paymentMethodIds = [];

	/**
	 * @var array
	 */
	private $paymentMethodCodes = [];

	/**
	 * @var \Magento\Framework\App\Cache\Type\Collection
	 */
	private $cache;

	/**
	 * @var \Magento\Framework\Filesystem
	 */
	private $filesystem;

	const CACHEKEY = "cgAllPM";

	/**
	 * @var \Cardgate\Payment\Model\Config
	 */
	private $config;

	public function __construct( \Magento\Framework\App\Cache\Type\Collection $cache, \Magento\Framework\Filesystem $filesystem ) {
		$this->cache = $cache;
		$this->filesystem = $filesystem;
		if ( $this->cache->test( self::CACHEKEY ) !== false ) {
			try {
				$cachedPMs = unserialize( $this->cache->load( self::CACHEKEY ) );
			} catch ( \Exception $e ) {
				$cachedPMs = [];
			}
			$this->paymentMethodIds = $cachedPMs;
		}
		if ( !is_array( $this->paymentMethodIds ) || count( $this->paymentMethodIds ) < 1 ) {
			$this->updatePaymentMethods();
		}

		$this->paymentMethodCodes = [];
		foreach ( $this->paymentMethodIds as $paymentMethod => $paymentMethodName ) {
			$this->paymentMethodCodes["cardgate_{$paymentMethod}"] = $paymentMethodName;
			$this->ensurePaymentClass( "cardgate_{$paymentMethod}" );
		}
	}

	/**
	 * Set CardGate config.
	 */
	public function setConfig( \Cardgate\Payment\Model\Config $config ) {
		$this->config = $config;
	}

	/**
	 * Get Paymentmethod classname by Code.
	 */
	public function getPMClassByCode( $paymentMethodCode, $fullClassName = true ) {
		return ( $fullClassName ? 'Cardgate\\Payment\\Model\\PaymentMethod\\' : '' ) . substr( $paymentMethodCode, 9 );
	}

	/**
	 * Get Paymentmethod instance by Code.
	 * $force can be set to ensure class exists (or create it if not exists).
	 */
	public function getPMInstanceByCode( $paymentMethodCode, $force = false ) {
		if ( $force ) {
			$this->ensurePaymentClass( $paymentMethodCode );
		}
		return ObjectManager::getInstance()->get( $this->getPMClassByCode( $paymentMethodCode ) );
	}

	/**
	 * Test if Paymentmethod ID exists (CardGate style ID).
	 */
	public function hasPMId( $paymentMethodId ) {
		return isset( $this->paymentMethodIds[$paymentMethodId] );
	}

	/**
	 * Tests if Paymentmethod Code is a CardGate Code.
	 */
	public function isCardgateCode( $paymentMethodCode ) {
		return ( substr( $paymentMethodCode, 0, 9 ) == 'cardgate_' );
	}

	/**
	 * Get Paymentmethod Code for a given PM ID (CardGate style ID).
	 */
	public function getPMCodeById( $paymentMethodId ) {
		return 'cardgate_' . $paymentMethodId;
	}

	/**
	 * Create Paymentmethod Class if not exists.
	 */
	private function ensurePaymentClass( $paymentMethodCode ) {
		if ( ! \class_exists( $this->getPMClassByCode( $paymentMethodCode ) ) ) {
			/** @var \Magento\Framework\Filesystem\Directory\Write $directory */
			$directory = $this->filesystem->getDirectoryWrite( DirectoryList::TMP );
			if ( ! $directory->isFile( 'paymentmethod_' . $paymentMethodCode ) ) {
				$directory->writeFile( 'paymentmethod_' . $paymentMethodCode, "<?php namespace Cardgate\\Payment\\Model\\PaymentMethod; class " . $this->getPMClassByCode( $paymentMethodCode, false ) . " extends \\Cardgate\\Payment\\Model\\PaymentMethod\\nonexistent {}" );
			}
			include $directory->getAbsolutePath( 'paymentmethod_' . $paymentMethodCode );
		}
	}

	/**
	 * Load Paymentmethods and save them in cache.
	 */
	private function updatePaymentMethods() {
		// YYY: Make dynamic
		$this->paymentMethodIds = [
			'ideal' => 'iDeal',
			'mistercash' => 'Bancontact',
			'creditcard' => 'Creditcard',
			'paysafecard' => 'PaySafeCard',
			'sofortbanking' => 'SofortBanking',
			'paypal' => 'PayPal',
			'klarna' => 'Klarna',
			'clickandbuy' => 'ClickandBuy',
			'afterpay' => 'Afterpay',
			'directdebit' => 'DirectDebit',
			'przelewy24' => 'Przelewy24',
			'focum' => 'Focum',
			'wallet' => 'Wallet',
			'safeklick' => 'Safeklick',
			'banktransfer' => 'Bank transfer',
			'giropay' => 'Giropay',
			'pos' => 'POS',
			'capayable' => 'Capayable',
			'bitcoin' => 'Bitcoin',
			'belfius' => 'Belfius',
			'eps' => 'EPS'
		];
		$this->cache->save( serialize( $this->paymentMethodIds ), self::CACHEKEY, [], 24 * 3600 );
	}

	public function getCardgateMethods() {
		return $this->paymentMethodIds;
	}

	public function getPaymentMethods( $bIncludingNames = false ) {
		if ( $bIncludingNames ) {
			return $this->paymentMethodCodes;
		} else {
			return array_keys( $this->paymentMethodCodes );
		}
	}

}
