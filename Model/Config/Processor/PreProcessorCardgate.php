<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Config\Processor;

use \Magento\Framework\App\ObjectManager;

class PreProcessorCardgate implements \Magento\Framework\App\Config\Spi\PreProcessorInterface {

	public function process( array $aConfig_ ) {
		foreach ( \Cardgate\Payment\Model\PaymentMethod::getAllPaymentMethods() as $sPaymentMethodId => $sPaymentMethodName ) {
			$sPaymentMethodCode = 'cardgate_' . $sPaymentMethodId;
			if ( ! isset( $aConfig_['default']['payment'][$sPaymentMethodCode] ) ) {
				$aConfig_['default']['payment'][$sPaymentMethodCode] = [];
			}
			if ( is_array( $aConfig_['default']['payment'][$sPaymentMethodCode] ) ) {
				$aConfig_['default']['payment'][$sPaymentMethodCode] = array_merge( [
					'model' => \Cardgate\Payment\Model\PaymentMethod::getPaymentMethodClassByCode( $sPaymentMethodCode ),
					'label' => $sPaymentMethodCode,
					'group' => 'cardgate',
					'title' => $sPaymentMethodName
				], $aConfig_['default']['payment'][$sPaymentMethodCode] );
			}
		}
		return $aConfig_;
	}

}
