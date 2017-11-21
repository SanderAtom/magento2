<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Ui;

use \Magento\Framework\App\ObjectManager;

/**
 * UI Config provider.
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface {

	/**
	 * @return array
	 */
	public function getConfig() {
		$oSession = ObjectManager::getInstance()->get( \Magento\Checkout\Model\Session::class );
		$aConfig = [];
		$aConfig['payment'] = [];
		$aConfig['payment']['instructions'] = [];
		// iDeal issuers are globally assigned to the UI config.
		$aConfig['payment']['cardgate_ideal_issuers'] = $this->getIDealIssuers();

		foreach ( \Cardgate\Payment\Model\PaymentMethod::getAllPaymentMethods() as $sPaymentMethodId => $sPaymentMethodName ) {
			$sPaymentMethodCode = 'cardgate_' . $sPaymentMethodId;
			$sMethodClass = \Cardgate\Payment\Model\PaymentMethod::getPaymentMethodClassByCode( $sPaymentMethodCode );
			$oFee = ObjectManager::getInstance()->get( $sMethodClass )->getFeeForQuote( $oSession->getQuote() );
			$aConfig['payment'][$sPaymentMethodCode] = [
				'renderer'       => $sMethodClass::$renderer,
				'cardgatefee'    => $oFee->getAmount(),
				'cardgatefeetax' => $oFee->getTaxAmount()
			];
			$aConfig['payment']['instructions'][$sPaymentMethodCode] = 'Test instructies';
		}
		return $aConfig;
	}

	/**
	 * Get list of iDeal issuers.
	 * Read from cache or fetch from CardGate if not cached.
	 * @return array
	 */
	public function getIDealIssuers() {
		$oGatewayClient = ObjectManager::getInstance()->get( \Cardgate\Payment\Model\GatewayClient::class );
		$oCache = ObjectManager::getInstance()->get( \Magento\Framework\App\Cache\Type\Collection::class );
		$sCacheKey = "cgIDealIssuers" . ( $oGatewayClient->getTestmode() ? 'test' : 'live' );
		if ( $oCache->test( $sCacheKey ) !== FALSE ) {
			try {
				$aIssuers = unserialize( $oCache->load( $sCacheKey ) );
				if ( count( $aIssuers ) > 0 ) {
					return $aIssuers;
				}
			} catch ( \Exception $e_ ) { /* ignore */ }
		}
		try {
			$aIssuers = $oGatewayClient->methods()->get( \cardgate\api\Method::IDEAL )->getIssuers();
			$oCache->save( serialize( $aIssuers ), $sCacheKey, [], 7200 );
		} catch ( \Exception $e_ ) {
			// TODO log an error here
			$aIssuers = [];
		}
		return $aIssuers;
	}

}
