/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
define(
	[
		'mage/url',
		'mage/storage',
		'Magento_Checkout/js/action/get-totals',
		'Magento_Checkout/js/model/totals'
	],
	function(
		urlBuilder,
		storage,
		getTotalsAction,
		totals
	) {
		'use strict';

		return function( messageContainer, paymentData ) {
			totals.isLoading( true );
			return storage.get( urlBuilder.build( 'cardgate/payment/updatepm?pm=' + paymentData.method ) )
				.fail(
					function( response ) {
						totals.isLoading( false );
					}
				).done(
					function() {
						getTotalsAction([]);
					}
				)
			;
		};
	}
);
