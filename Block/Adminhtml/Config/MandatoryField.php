<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Adminhtml\Config;

use Cardgate\Payment\Model\Config;

/**
 * Mandatory field HTML block renderer.
 */
class MandatoryField extends \Magento\Config\Block\System\Config\Form\Field {

	private $_oConfig;

	public function __construct( \Magento\Backend\Block\Template\Context $oContext_, Config $oConfig_, array $aData_ = [] ) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $aData_ );
	}

	protected function _renderValue( \Magento\Framework\Data\Form\Element\AbstractElement $oElement_ ) {
		if ( empty( $oElement_->getValue() ) ) {
			$oElement_->setComment( $oElement_->getComment() . '<span style="color:red;">' . __( 'Missing value' ) . '</span>' );
		}
		return parent::_renderValue( $oElement_ );
	}

}
