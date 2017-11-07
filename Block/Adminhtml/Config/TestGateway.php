<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

/**
 * Test gateway settings HTML block renderer.
 */
class TestGateway extends \Magento\Config\Block\System\Config\Form\Field {

	private $_oConfig;

	public function __construct( \Magento\Backend\Block\Template\Context $oContext_, Config $oConfig_, array $aData_ = [] ) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $aData_ );
	}

	protected function _getElementHtml( \Magento\Framework\Data\Form\Element\AbstractElement $oElement_ ) {
		if (
			! empty( $this->_oConfig->getGlobal( 'api_username' ) )
			&& ! empty( $this->_oConfig->getGlobal( 'api_password' ) )
			&& ! empty( $this->_oConfig->getGlobal( 'site_id' ) )
		) {
			$sTestGatewayUrl = $this->_urlBuilder->getUrl( 'cardgate/gateway/test', [
				'section' => 'gateway'
			] );
			return "<button onclick=\"window.open('{$sTestGatewayUrl}'); return false;\"><span>" . __( 'Test Gateway communication' ) . '</span></button>';
		} else {
			return __( 'Please enter api-username & api-password first' );
		}
	}

}
