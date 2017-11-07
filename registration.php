<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
	\Magento\Framework\Component\ComponentRegistrar::MODULE,
	'Cardgate_Payment',
	__DIR__
);

$vendorDir = require BP . '/app/etc/vendor_path.php';
$vendorAutoload = BP . "/{$vendorDir}/autoload.php";
$composerAutoloader = include $vendorAutoload;
$composerAutoloader->addPsr4( 'curopayments\\', array( __DIR__ . '/lib' ) );
