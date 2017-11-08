<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Show version HTML block renderer.
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field {

	private $_oConfig;

	public function __construct( \Magento\Backend\Block\Template\Context $oContext_, Config $oConfig_, array $aData_ = [] ) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $aData_ );
	}

	public function _getElementHtml( \Magento\Framework\Data\Form\Element\AbstractElement $oElement_ ) {
		try {
			$oModuleList = ObjectManager::getInstance()->get( ModuleListInterface::class );
			$sVersion = $oModuleList->getOne( 'Cardgate_Payment' )['setup_version'];
		} catch ( \Exception $e_ ) {
			$sVersion = __( 'UNKOWN' );
		}
		return
			"v{$sVersion}"
			. ( $this->_oConfig->getGlobal( 'testmode' ) ? ' <span style="color:red;">' . __( 'TESTMODE ENABLED' ) . '</span>' : '' )
			. ( ! empty( $_SERVER['CG_API_URL'] ) ? ' <span style="color:red;">API OVERRIDE (' . $_SERVER['CG_API_URL'] . ')</span>' : '' )
		;
	}

}
