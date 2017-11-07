<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

/**
 * Fetch payment methods HTML block renderer.
 */
class FetchPM extends \Magento\Config\Block\System\Config\Form\Field {

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
			$sFetchPMUrl = $this->_urlBuilder->getUrl( 'cardgate/gateway/fetchpm', [
				'section' => 'gateway'
			] );
			return "<button onclick=\"window.open('{$sFetchPMUrl}'); return false;\"><span>" . __( 'Refresh active paymentmethods' ) . '</span></button>';
		} else {
			return __( 'Please enter api-username, api-password and site-id first' );
		}
	}

}
