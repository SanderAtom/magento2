<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Cardgate\Payment\Model\Config\Master as MasterConfig;
use Cardgate\Payment\Model\Config;
use Cardgate\Payment\Model\GatewayClient;
use Magento\Framework\App\ObjectManager;

/**
 * UI Config provider.
 */
class ConfigProvider implements ConfigProviderInterface {

	/**
	 * @var \Magento\Framework\App\Cache\Type\Collection
	 */
	private $cache;

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Escaper
	 */
	protected $escaper;

	/**
	 * @var MasterConfig
	 */
	private $masterConfig;

	/**
	 * @var GatewayClient
	 */
	private $cardgateClient;

	public function __construct( PaymentHelper $paymentHelper, Escaper $escaper, MasterConfig $masterConfig, Config $config, GatewayClient $cardgateClient, \Magento\Framework\App\Cache\Type\Collection $cache ) {
		$this->escaper = $escaper;
		$this->config = $config;
		$this->cache = $cache;
		$this->masterConfig = $masterConfig;
		$this->cardgateClient = $cardgateClient;
	}

	public function getConfig() {
		$session = ObjectManager::getInstance()->get( 'Magento\\Checkout\\Model\\Session' );

		$config = [];
		$config['payment'] = [];
		$config['payment']['instructions'] = [];
		// iDeal issuers are globally assigned to the UI config
		$config['payment']['cardgate_ideal_issuers'] = $this->getIDealIssuers();

		foreach ( $this->masterConfig->getPaymentMethods() as $method ) {
			$methodClass = $this->masterConfig->getPMClassByCode( $method );
			/**
			 *
			 * @var \Cardgate\Payment\Model\Total\FeeData $fee
			 */
			$fee = $this->masterConfig->getPMInstanceByCode( $method )->getFeeForQuote( $session->getQuote() );
			$config['payment'][$method] = [
				'renderer' => $methodClass::$renderer,
				'cardgatefee' => $fee->getAmount(),
				'cardgatefeetax' => $fee->getTaxAmount()
			];
			$config['payment']['instructions'][$method] = 'Test instructies';
		}
		return $config;
	}

	/**
	 * Get list of iDeal issuers.
	 * Read from cache or fetch from CardGate if not cached.
	 */
	public function getIDealIssuers() {
		$testmode = boolval( $this->cardgateClient->getTestmode() );
		$cacheID = "cgIDealIssuers" . ( $testmode ? 'test' : 'live' );
		if ( $this->cache->test( $cacheID ) !== false ) {
			try {
				$issuers = unserialize( $this->cache->load( $cacheID ) );
				if ( count( $issuers ) > 0 ) {
					return $issuers;
				}
			} catch ( \Exception $e ) {
				// ignore
			}
		}
		try {
			$ideal = new \curopayments\api\Payment\Request\iDeal( $this->cardgateClient );
			$issuers = $ideal->getIssuers()->getList();
			$this->cache->save( serialize( $issuers ), $cacheID, [], 7200 );
		} catch ( \Exception $e ) {
			// YYY: Log error here
			$issuers = [];
		}
		return $issuers;
	}

}
