<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

/**
 * Show payment methods HTML block renderer.
 */
class ShowPM extends \Magento\Config\Block\System\Config\Form\Field {

	private $_oConfig;

	public function __construct( \Magento\Backend\Block\Template\Context $oContext_, Config $oConfig_, array $aData_ = [] ) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $aData_ );
	}

	protected function _getElementHtml( \Magento\Framework\Data\Form\Element\AbstractElement $oElement_ ) {
		if ( empty( $this->_oConfig->getGlobal( 'active_pm' ) ) ) {
			return '<span style="color:red;">' . __( 'No active paymentmethods found' ) . '</span>';
		} else {
			return implode( ', ', $this->_oConfig->getActivePMIds() );
		}
	}

}
