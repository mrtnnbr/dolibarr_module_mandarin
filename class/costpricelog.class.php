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
		
		if(empty($price) || empty($qty)) return false;
		
		$cpl=new TProductCostPriceLog;
		$cpl->fk_product = $fk_product;
		$cpl->fk_supplier = $fk_supplier;
		$cpl->price = $price;
		$cpl->qty = $qty;
		$cpl->log_type = $log_type;
		return $cpl->save($PDOdb);
		
	}
	
	static function getDataForProduct(&$PDOdb, $fk_product) {
		
		$sql="SELECT log_type,YEAR(date_cre) as year,MONTH(date_cre) as month, (SUM(qty * price) / SUM(qty)) as price
			FROM ".MAIN_DB_PREFIX."product_cost_price_log
			WHERE fk_product = ".(int)$fk_product."
			GROUP BY log_type,YEAR(date_cre),MONTH(date_cre)
			";
		
		return $PDOdb->ExecuteAsArray($sql);
		
		
	}
	
} 