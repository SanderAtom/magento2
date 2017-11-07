<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

class GroupPaymentMethod extends \Magento\Config\Block\System\Config\Form\Fieldset {

	private $_oConfig;
	private $_sPMId;

	// Payment method is active on the CardGate platform for the configured site.
	private $_bPMEnabled;

	// Payment method is active in the Magento configuration.
	private $_bPMActive;

	public function __construct(
		\Magento\Backend\Block\Context $oContext_,
		\Magento\Backend\Model\Auth\Session $oSession_,
		\Magento\Framework\View\Helper\Js $oJSHelper_,
		Config $oConfig_,
		array $aData_ = []
	) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $oSession_, $oJSHelper_, $aData_ );
	}

	public function render( \Magento\Framework\Data\Form\Element\AbstractElement $oElement_ ) {
		$this->_sPMId = $oElement_->getData( 'original_data' )['pmid'];
		$this->_bPMActive = !!$this->_oConfig->getField( 'cardgate_' . $this->_sPMId, 'active' );
		$aActivePMIds = $this->_oConfig->getActivePMIds();
		$this->_bPMEnabled = in_array( $this->_sPMId, $aActivePMIds );
		return parent::render( $oElement_ );
	}

	protected function _getHeaderTitleHtml( $oElement_ ) {
		$sLegend = $oElement_->getLegend();
		if ( ! $this->_bPMEnabled ) {
			$sLegend = "<span style=\"text-decoration:line-through;\">{$sLegend}</span>";
		} elseif ( ! $this->_bPMActive ) {
			$sLegend .= ' - <span style="color:red;">' . __( 'disabled' ) . '</span>';
		}
		if ( ! $this->_testConfigurationHealth() ) {
			$sLegend .= ' - <span style="color:red;">' . __( 'Enabled but not active' ) . '</span>';
		}
		$oElement_->setLegend( $sLegend );
		return parent::_getHeaderTitleHtml( $oElement_ );
	}

	private function _testConfigurationHealth() {
		if (
			! $this->_bPMEnabled
			&& $this->_bPMActive
		) {
			$aExtra = $this->_authSession->getUser()->getExtra();
			$aExtra['configState']['cardgate_' . $this->_sPMId] = TRUE;
			$this->_authSession->getUser()->setExtra( $aExtra );
			return FALSE;
		} else {
			return TRUE;
		}
	}

	protected function _getHeaderCommentHtml( $oElement_ ) {
		if ( ! $this->_bPMEnabled ) {
			return
				'<div class="comment">' . __( 'This paymentmethod is not active in the CardGate configuration.' ) . ' <a target="_blank" href="https://my.cardgate.com">' . __( 'Please check CardGate settings' ) . '</a> ' .
				__( 'or' ) .' <a target="_blank" href="https://www.cardgate.com">' . __( 'contact an accountmanager' ) . '</a>.</div>'
			;
		}
		$aGroupConfig = $oElement_->getGroup();
		if (
			empty( $aGroupConfig['help_url'] )
			|| ! $oElement_->getComment()
		) {
			return parent::_getHeaderCommentHtml( $oElement_ );
		}
		$sHtml = '<div class="comment">' . $oElement_->getComment() . ' <a target="_blank" href="' . $aGroupConfig['help_url'] . '">' . __( 'Help' ) . '</a></div>';
		return $sHtml;
	}

	protected function _isCollapseState( $oElement_ ) {
		$aExtra = $this->_authSession->getUser()->getExtra();
		if ( isset( $aExtra['configState']['cardgate_' . $this->_sPMId] ) ) {
			return $aExtra['configState']['cardgate_' . $this->_sPMId];
		}
		if ( ! $this->_bPMEnabled ) {
			return FALSE;
		}
		$aGroupConfig = $oElement_->getGroup();
		if ( ! empty( $aGroupConfig['expanded'] ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
