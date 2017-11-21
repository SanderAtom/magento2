<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Setup;

/**
 * Install data class, executed at first installation of this plugin.
 */
class InstallData implements \Magento\Framework\Setup\InstallDataInterface {

	public function install( \Magento\Framework\Setup\ModuleDataSetupInterface $oSetup_, \Magento\Framework\Setup\ModuleContextInterface $oContext_ ) {
		$oSetup_->startSetup();

		$aData = [];
		$aStatuses = [
			'cardgate_waitconf'   => __( 'Waiting Confirmation CardGate' ),
			'cardgate_authorized' => __( 'Authorized CardGate' ),
			'cardgate_refund'     => __( 'Refund CardGate' )
		];
		foreach ( $aStatuses as $sCode => $sInfo ) {
			$aData[] = [
				'status' => $sCode,
				'label'  => $sInfo
			];
		}
		$oSetup_->getConnection()->insertArray( $oSetup_->getTable( 'sales_order_status' ), [
			'status',
			'label'
		], $aData );

		$oSetup_->endSetup();
	}

}
