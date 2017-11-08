<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

/**
 * Information configuration group HTML block renderer.
 */
class GroupInfo extends \Magento\Config\Block\System\Config\Form\Fieldset {

	private $_oConfig;

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

	protected function _getHeaderTitleHtml( $oElement_ ) {
		$sLegend = $oElement_->getLegend();
		if ( ! $this->_testConfigurationHealth() ) {
			$sLegend .= ' - <span style="color:red;">' . __( 'Attention required' ) . '</span>';
		}
		$oElement_->setLegend( $sLegend );
		return parent::_getHeaderTitleHtml( $oElement_ );
	}

	private function _testConfigurationHealth() {
		$aExtra = $this->_authSession->getUser()->getExtra();
		if ( empty( $this->_oConfig->getGlobal( 'active_pm' ) ) ) {
			// The configState is used to set tne collapsed state below.
			// NOTE the id's are joined using the _-character as glue, so the group 'global' in the 'cardgate' group
			// gets the 'cardgate_global' id.
			// SEE in etc/adminhtml/system.xml.
			$aExtra['configState']['cardgate_info'] = TRUE;
			$aExtra['configState']['cardgate_info_pms'] = TRUE;
			$this->_authSession->getUser()->setExtra( $aExtra );
			return FALSE;
		}
		if ( ! empty( $_SERVER['CG_API_URL'] ) ) {
			// NOTE see above.
			$aExtra['configState']['cardgate_info'] = TRUE;
			$aExtra['configState']['cardgate_info_test'] = TRUE;
			$this->_authSession->getUser()->setExtra( $aExtra );
			return FALSE;
		}
		return TRUE;
	}

	protected function _getHeaderCommentHtml( $oElement_ ) {
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
		if ( isset( $aExtra['configState'][$oElement_->getId()] ) ) {
			return $aExtra['configState'][$oElement_->getId()];
		}
		$aGroupConfig = $oElement_->getGroup();
		if ( ! empty( $aGroupConfig['expanded'] ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
