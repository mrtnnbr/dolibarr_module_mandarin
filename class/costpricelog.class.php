<?php

class TCostPriceLog extends TObjetStd {
	
	function __construct() {
		
		$this->set_table(MAIN_DB_PREFIX.'product_cost_price_log');
		parent::addChamps('fk_supplier,fk_product',array('type'=>'integer','index'=>true));
		parent::addChamps('qty',array('type'=>'float'));
		parent::addChamps('log_type',array('index'=>true, 'type'=>'string', 'length'=>10));
		
		parent::_init_vars();
		parent:start();
		
		//log_type : PA, PMP, OF
	}
	
	static function add(&$PDOdb, $fk_product, $qty, $log_type = 'PA', $fk_supplier=0) {
		
		$cpl=new TCostPriceLog;
		$cpl->fk_product = $fk_product;
		$cpl->fk_supplier = $fk_supplier;
		$cpl->qty = $qty;
		$cpl->log_type = $log_type;
		$cpt->save($PDOdb);
		
	}
	
} 