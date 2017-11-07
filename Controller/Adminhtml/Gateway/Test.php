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
use Magento\Framework\App\ObjectManager;

/**
 * Test gateway connectivity Adminhtml action.
 */
class Test extends Action {

	public function execute() {
		$oGatewayClient = ObjectManager::getInstance()->get( GatewayClient::class );
		$sResult = $this->resultFactory->create( ResultFactory::TYPE_RAW );

		$sTestResult = "Testing Cardgate gateway communication...\n\n";
		try {
			$oPMResult = $oGatewayClient->postRequest( 'options/' . $oGatewayClient->getSiteId() );
			$sTestResult .= "Gateway request for site #" . $oGatewayClient->getSiteId() . " completed...\n\nFound paymentmethods:\n";
			foreach ( $oPMResult->options as $sPMId => $oPMRecord ) {
				$sTestResult .= "  {$oPMRecord->name}\n";
			}
		} catch ( \Exception $e_ ) {
			$sTestResult .= "Error occurred : " . $e_->getMessage();
		}

		$sResult->setContents( '<pre>' . $sTestResult . "\n\nCompleted.<pre>" );
		return $sResult;
	}

}
