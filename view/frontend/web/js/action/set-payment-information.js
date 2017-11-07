/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
define(
	[
		'Magento_Checkout/js/model/quote',
		'Magento_Checkout/js/model/url-builder',
		'mage/storage',
		'Magento_Checkout/js/model/error-processor',
		'Magento_Customer/js/model/customer',
		'Magento_Checkout/js/action/get-totals',
		'Magento_Checkout/js/model/full-screen-loader',
		'Magento_Checkout/js/model/resource-url-manager'
	],
	function (quote, urlBuilder, storage, errorProcessor, customer, getTotalsAction, fullScreenLoader, resourceUrlManager) {
		'use strict';

		return function (messageContainer, paymentData) {
			var serviceUrl,
				payload;

			/**
			 * Checkout for guest and registered customer.
			 */
			if (!customer.isLoggedIn()) {
				serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/set-payment-information', {
					cartId: quote.getQuoteId()
				});
				payload = {
					cartId: quote.getQuoteId(),
					email: quote.guestEmail,
					paymentMethod: paymentData,
					billingAddress: quote.billingAddress()
				};
			} else {
				serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information', {});
				payload = {
					cartId: quote.getQuoteId(),
					paymentMethod: paymentData,
					billingAddress: quote.billingAddress()
				};
			}

			fullScreenLoader.startLoader();

			return storage.post(
				serviceUrl, JSON.stringify(payload)
			).fail(
				function (response) {
					errorProcessor.process(response, messageContainer);
				}
			).done(
				function () {
					getTotalsAction([]);
				}
			).always(
				function () {
					fullScreenLoader.stopLoader();
				}
			);
		};
	}
);
