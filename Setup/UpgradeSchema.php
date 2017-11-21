<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Setup;

/**
 * Upgrade schema class, executed every time this module is upgraded.
 */
class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface {

	public function upgrade( \Magento\Framework\Setup\SchemaSetupInterface $oSetup_, \Magento\Framework\Setup\ModuleContextInterface $oContext_ ) {
		$oSetup_->startSetup();
		$oSetup_->endSetup();
	}

}
