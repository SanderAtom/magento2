<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Model\PaymentMethod;

/**
 * iDeal exception class because we want another renderer template.
 */
class ideal extends \Cardgate\Payment\Model\PaymentMethod {

	/**
	 * Renderer template name.
	 * @var string
	 */
	public static $renderer = 'ideal';

}
