<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\Layout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Layout Processor plugin to inject paymentmethods in checkout billing-step section.
 */
class LayoutProcessorPlugin {

	/**
	 * Inject paymentmethods in checkout billing-step section.
	 */
	public function aroundProcess( LayoutProcessor $layoutProcessor, \Closure $proceed, $scope ) {
		$aData = $proceed( $scope );
		$aSubData = [
			'component' => 'Cardgate_Payment/js/view/payment/paymentmethods',
			'label'     => 'CardGate',
			'methods'   => []
		];
		foreach ( \Cardgate\Payment\Model\PaymentMethod::getAllPaymentMethods() as $sPaymentMethodId => $sPaymentMethodName ) {
			$sPaymentMethodCode = 'cardgate_' . $sPaymentMethodId;
			$aSubData['methods'][$sPaymentMethodCode] = [
				'isBillingAddressRequired' => TRUE
			];
		}
		$aData['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children']['cardgate'] = $aSubData;
		return $aData;
	}

}
