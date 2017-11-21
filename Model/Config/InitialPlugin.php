<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Config;

use Magento\Framework\App\Config\Initial;

/**
 * Initial Config plugin to dynamically add all paymentmethods.
 */
class InitialPlugin {

	/**
	 * Alter getData's output.
	 */
	public function aroundGetData( Initial $initialConfig, \Closure $proceed, $scope ) {
		$aData = $proceed( $scope );
		foreach ( \Cardgate\Payment\Model\PaymentMethod::getAllPaymentMethods() as $sPaymentMethodId => $sPaymentMethodName ) {
			$sPaymentMethodCode = 'cardgate_' . $sPaymentMethodId;
			$aData['payment'][$sPaymentMethodCode] = [
				'model' => \Cardgate\Payment\Model\PaymentMethod::getPaymentMethodClassByCode( $sPaymentMethodCode ),
				'label' => $sPaymentMethodCode,
				'group' => 'cardgate',
				'title' => $sPaymentMethodName
			];
		}
		return $aData;
	}

}
