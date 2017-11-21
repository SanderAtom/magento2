<?php
/**
 * Copyright (c) 2017 CardGate B.V.
 * All rights reserved.
 * See LICENSE for license details.
 */
namespace Cardgate\Payment\Setup;

/**
 * Install schema class, executed at first installation of this plugin.
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface {

	public function install( \Magento\Framework\Setup\SchemaSetupInterface $oSetup_, \Magento\Framework\Setup\ModuleContextInterface $oContext_ ) {
		$oSetup_->startSetup();

		// Setup quote payment table.
		foreach( [
			'cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Incl Tax'
			],
			'base_cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Incl Tax'
			],

			'base_cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Amount'
			],
			'base_cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Tax Amount'
			],

			'cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Amount'
			],
			'cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Tax Amount'
			]
		] as $sColumnName => $aDefinition ) {
			$oSetup_->getConnection()->addColumn( $oSetup_->getTable( 'quote_payment' ), $sColumnName, $aDefinition );
		}

		// Setup sales order table.
		foreach( [
			'cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Incl Tax'
			],
			'base_cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Incl Tax'
			],

			'base_cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Amount'
			],
			'base_cardgatefee_cancelled' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Cancelled'
			],
			'base_cardgatefee_invoiced' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Invoiced'
			],
			'base_cardgatefee_refunded' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Refunded'
			],
			'base_cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Tax Amount'
			],
			'base_cardgatefee_tax_refunded' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Tax Refunded'
			],

			'cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Amount'
			],
			'cardgatefee_cancelled' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Cancelled'
			],
			'cardgatefee_invoiced' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Invoiced'
			],
			'cardgatefee_refunded' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Refunded'
			],
			'cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Tax Amount'
			],
			'cardgatefee_tax_refunded' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Tax Refunded'
			]
		] as $sColumnName => $aDefinition ) {
			$oSetup_->getConnection()->addColumn( $oSetup_->getTable( 'sales_order' ), $sColumnName, $aDefinition );
		}

		// Setup sales invoice table.
		foreach( [
			'cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Incl Tax'
			],
			'base_cardgatefee_incl_tax' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Incl Tax'
			],

			'base_cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Excl Tax Amount'
			],
			'base_cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'Base CardGate Fee Tax Amount'
			],

			'cardgatefee_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Excl Tax Amount'
			],
			'cardgatefee_tax_amount' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
				'length'   => '12,4',
				'default'  => '0.0000',
				'nullable' => TRUE,
				'comment'  => 'CardGate Fee Tax Amount'
			]
		] as $sColumnName => $aDefinition ) {
			$oSetup_->getConnection()->addColumn( $oSetup_->getTable( 'sales_invoice' ), $sColumnName, $aDefinition );
		}

		// Setup sales order payment.
		foreach( [
			'cardgate_paymentmethod' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length'   => 64,
				'default'  => '',
				'nullable' => TRUE,
				'comment'  => 'CardGate PaymentMethod'
			],
			'cardgate_transaction' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'length'   => 64,
				'default'  => '',
				'nullable' => TRUE,
				'comment'  => 'CardGate TransactionID'
			],
			'cardgate_status' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				'default'  => 0,
				'nullable' => TRUE,
				'comment'  => 'CardGate StatusCode'
			],
			'cardgate_testmode' => [
				'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
				'default'  => 0,
				'nullable' => TRUE,
				'comment'  => 'CardGate TestMode'
			]
		] as $sColumnName => $aDefinition ) {
			$oSetup_->getConnection()->addColumn( $oSetup_->getTable( 'sales_order_payment' ), $sColumnName, $aDefinition );
		}
	}

}
