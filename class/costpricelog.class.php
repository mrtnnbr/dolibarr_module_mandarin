<?php

class TProductCostPriceLog extends TObjetStd {
	
	function __construct() {
		
		$this->set_table(MAIN_DB_PREFIX.'product_cost_price_log');
		parent::add_champs('fk_supplier,fk_product',array('type'=>'integer','index'=>true));
		parent::add_champs('qty,price',array('type'=>'float'));
		parent::add_champs('log_type',array('index'=>true, 'type'=>'string', 'length'=>10));
		
		parent::_init_vars();
		parent::start();
		
		//log_type : PA, PMP, OF
	}
	
	static function add(&$PDOdb, $fk_product, $qty,$price, $log_type = 'PA', $fk_supplier=0) {
		
		$cpl=new TProductCostPriceLog;
		$cpl->fk_product = $fk_product;
		$cpl->fk_supplier = $fk_supplier;
		$cpl->price = $price;
		$cpl->qty = $qty;
		$cpl->log_type = $log_type;
		$cpl->save($PDOdb);
		
	}
	
} 