<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Controller\Adminhtml\Gateway;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Cardgate\Payment\Model\GatewayClient;
use Cardgate\Payment\Model\Config;
use Magento\Framework\App\ObjectManager;

/**
 * Fetch paymentmethods Adminhtml action.
 */
class FetchPM extends Action {

	public function execute() {
		$oConfig = ObjectManager::getInstance()->get( Config::class );
		$oGatewayClient = ObjectManager::getInstance()->get( GatewayClient::class );

		$aTestResult = [];
		$aActivePMs = [];
		try {
			$oPMResult = $oGatewayClient->postRequest( 'options/' . $oGatewayClient->getSiteId() );
			foreach ( $oPMResult->options as $sPMId => $oPMRecord ) {
				$aActivePMs[] = [
					'id'   => $oPMRecord->id,
					'name' => $oPMRecord->name
				];
			}
			$aTestResult['pms'] = $aActivePMs;
			$oConfig->setGlobal( 'active_pm', serialize( $aActivePMs ) );
			$aTestResult['success'] = TRUE;
		} catch ( \Exception $e_ ) {
			$aTestResult['success'] = FALSE;
			$aTestResult['message'] = $e_->getMessage();
		}

		$sResult = $this->resultFactory->create( ResultFactory::TYPE_RAW );
		$sResult->setContents(
			"<html><body><pre>After successful query; close this tab and <b><u>please flush CACHE</u></b> ('System' > 'Tools' > 'Cache Management')." .
			( isset( $aTestResult['message'] ) ? "\n\n<b>Message : " . $aTestResult['message'] . '' : '' ) . "</b>\n\nNumber of active paymentmethods found : " . count( $aActivePMs ) .
			"\n\nRaw Result :\n" . var_export( $aActivePMs, 1 )."</pre></body></html>"
		);
		return $sResult;
	}

}
