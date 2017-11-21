/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
define(
	[
		'Cardgate_Payment/js/view/checkout/summary/fee'
	],
	function( Component ) {
		'use strict';

		return Component.extend( {
			isDisplayed: function () {
				return true;
			}
		} );
	}
);
