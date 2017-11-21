/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
define(
	[
		'Magento_Checkout/js/model/quote',
		'mage/url',
		'mage/storage',
		'Magento_Checkout/js/model/error-processor',
		'Magento_Customer/js/model/customer',
		'Magento_Checkout/js/action/get-totals',
		'Magento_Checkout/js/model/full-screen-loader',
		'Magento_Checkout/js/model/resource-url-manager'
	],
	function(
		quote,
		urlBuilder,
		storage,
		errorProcessor,
		customer,
		getTotalsAction,
		fullScreenLoader,
		resourceUrlManager
	) {
		'use strict';

		return function( messageContainer, paymentData ) {
			fullScreenLoader.startLoader();
			return storage.get( urlBuilder.build( 'cardgate/payment/updatepm?pm=' + paymentData.method ) )
				.fail(
					function( response ) {
					}
				).done(
					function() {
						getTotalsAction([]);
					}
				).always(
					function() {
						fullScreenLoader.stopLoader();
					}
				)
			;
		};
	}
);
