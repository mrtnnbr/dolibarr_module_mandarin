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
		
		$sql="SELECT log_type, DATE_FORMAT(date_cre,'%Y-%m-%d') as 'date' , (SUM(qty * price) / SUM(qty)) as price
			FROM ".MAIN_DB_PREFIX."product_cost_price_log
			WHERE fk_product = ".(int)$fk_product."
			GROUP BY log_type,DATE_FORMAT(date_cre,'%Y-%m-%d')
			ORDER BY date_cre
			";
		
		$Tab = $PDOdb->ExecuteAsArray($sql);
	//	var_dump($Tab);
		$TData = $Tmp = array();
		foreach($Tab as &$row) {
							
			if(!isset($Tmp[$row->date])) $Tmp[$row->date] = array('PA'=>0,'PMP'=>0, 'OF'=>0);
				
			$Tmp[$row->date][$row->log_type] = (double)$row->price;
			
		}
		
		self::normalizeArray($Tmp);
		

		foreach($Tmp as $date=>$values) {
			
			$TData[]= array_merge( array('date'=>$date), $values );
			
		}
		
	
		return $TData;
		
	}
	
	function normalizeArray(&$TData) {
		
		$previous_line= array();
		
		foreach($TData as $kd=>&$row) {
			
			if(!empty($previous_line)) {
				
				foreach($row as $k=>&$v) {
					
					if(empty($v) && !empty($previous_line[$k])) $v = $previous_line[$k];	
					
				}
				
				
			}
			
			$previous_line = $row;
			
		}
		
		
	}
	
} 