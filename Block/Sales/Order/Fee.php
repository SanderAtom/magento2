<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Block\Sales\Order;

class Fee extends \Magento\Framework\View\Element\Template {

	protected $_oConfig;
	protected $_oOrder;
	protected $_oSource;

	public function __construct( \Magento\Framework\View\Element\Template\Context $oContext_, \Magento\Tax\Model\Config $oConfig_, array $aData_ = [] ) {
		$this->_oConfig = $oConfig_;
		parent::__construct( $oContext_, $aData_ );
	}

	public function displayFullSummary() {
		return TRUE;
	}

	public function getSource() {
		return $this->_oSource;
	}

	public function getStore() {
		return $this->_oOrder->getStore();
	}

	public function getOrder() {
		return $this->_oOrder;
	}

	public function getLabelProperties() {
		return $this->getParentBlock()->getLabelProperties();
	}

	public function getValueProperties() {
		return $this->getParentBlock()->getValueProperties();
	}

	public function initTotals() {
		$oParent = $this->getParentBlock();
		$this->_oOrder = $oParent->getOrder();
		$this->_oSource = $oParent->getSource();

		$oFee = new \Magento\Framework\DataObject( [
			'code'       => 'cardgatefee',
			'strong'     => FALSE,
			'value'      => $this->_oOrder->getCardgatefeeAmount(),
			'base_value' => $this->_oOrder->getBaseCardgatefeeAmount(),
			'label'      => __( 'Checkout fee' )
		] );
		$oParent->addTotal( $oFee, 'shipping' );

		return $this;
	}

}
