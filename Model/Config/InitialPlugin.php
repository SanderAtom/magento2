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

	private $_masterConfig = null;

	public function __construct( Master $masterConfig ) {
		$this->_masterConfig = $masterConfig;
	}

	/**
	 * Alter getData's output.
	 */
	public function aroundGetData( Initial $initialConfig, \Closure $proceed, $scope ) {
		$data = $proceed( $scope );
		foreach ( $this->_masterConfig->getPaymentMethods( true ) as $paymentMethod => $paymentMethodName ) {
			$data['payment'][$paymentMethod] = [
				'model' => $this->_masterConfig->getPMClassByCode( $paymentMethod ),
				'label' => $paymentMethod,
				'group' => 'cardgate',
				'title' => $paymentMethodName
			];
		}
		return $data;
	}

}
