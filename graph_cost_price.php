<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->product_cost_price) accessforbidden();
	
	dol_include_once('/product/class/product.class.php');
	
	$langs->load('mandarin@mandarin');
	$langs->load('of@of');
	$langs->load('nomenclature@nomenclature');
	
	$PDOdb = new TPDOdb;
	
	llxHeader('', $langs->trans('RapportAnalyseCoutProduct'), '');
	

	$sql="SELECT l.fk_product, ROUND(SUM(l.qty),3) as qty, ROUND(SUM(l.qty_used),3) as qty_used, '' as percent 
	FROM ".MAIN_DB_PREFIX."assetOf_line l
	WHERE l.type='NEEDED' 
	GROUP BY l.fk_product";
	
	$formCore=new TFormCore('auto','formGraph');
	
	$listeview = new TListviewTBS('graphCost');
	//$PDOdb->debug=true;
	print $listeview->render($PDOdb, $sql,array(
		'liste'=>array(
			'titre'=>$langs->transnoentitiesnoconv('RapportAnalyseCoutProductMP')
		)
		,'eval'=>array(
			
			'fk_product'=>'_product_link(@fk_product@)'
			,'percent'=>'_get_percent(@qty@,@qty_used@)'
		)
		,'type'=>array(
			'qty'=>'number'
			,'qty_used'=>'number'
			,'date_maj'=>'date'
		)
		,'title'=>array(
			'fk_product'=>$langs->trans('Product')
			,'qty'=>$langs->trans('Qty')
			,'qty_used'=>$langs->trans('QtyUsed')
			,'percent'=>$langs->trans('Percent')
			,'date_maj'=>$langs->trans('Date')
		)
		,'search'=>array(
			'date_maj'=>array('recherche'=>'calendars','table'=>'l')
		)
	));
	
	$formCore->end();
	
	
	
	$formCore=new TFormCore('auto','formGraph2');
	
	$sql="SELECT l.fk_product, ROUND(SUM(l.qty_used),3) as qty,l.fk_nomenclature, n.totalPRCMO_PMP,n.totalPRCMO_OF,n.totalPRCMO 
	FROM ".MAIN_DB_PREFIX."assetOf_line l
	INNER JOIN ".MAIN_DB_PREFIX."nomenclature n ON (n.rowid=l.fk_nomenclature)
	WHERE l.type='TO_MAKE' 
	GROUP BY l.fk_product,l.fk_nomenclature";
	
	
	$listeview = new TListviewTBS('graphCost2');
	//$PDOdb->debug=true;
	print $listeview->render($PDOdb, $sql,array(
		'liste'=>array(
			'titre'=>$langs->transnoentitiesnoconv('RapportAnalyseCoutProductPF')
		)
		,'link'=>array(
			'fk_nomenclature'=>'<a href="'.dol_buildpath('/nomenclature/nomenclature.php?fk_product=@fk_product@',1).'">'.img_picto($langs->trans('Nomenclature'),'object_list').' @val@</a>'
		)
		,'eval'=>array(
			'fk_product'=>'_product_link(@fk_product@)'
		)
		,'type'=>array(
			'qty'=>'number'
			,'totalPRCMO'=>'number'
			,'totalPRCMO_PMP'=>'number'
			,'totalPRCMO_OF'=>'number'
			,'date_maj'=>'date'
		)
		,'title'=>array(
			'fk_product'=>$langs->trans('Product')
			,'qty'=>$langs->trans('Qty')
			,'totalPRCMO'=>$langs->trans('PricePA')
			,'totalPRCMO_PMP'=>$langs->trans('PricePMP')
			,'totalPRCMO_OF'=>$langs->trans('PriceOF')
			,'date_maj'=>$langs->trans('Date')
			,'fk_nomenclature'=>$langs->trans('Nomenclature')
		)
		,'search'=>array(
			'date_maj'=>array('recherche'=>'calendars','table'=>'l')
		)
	));
	
	$formCore->end();

	// End of page
	llxFooter();
	
function _get_percent($qty,$qty_used) {
	
	$percent =( ($qty_used - $qty) / $qty )* 100;
	
	$color = '';
	if($percent>0.5) $color = 'red';
	else if($percent<-0.5) $color = 'orange';
	
	return '<span style="color:'.$color.';">'.price($percent,'','',1,2,2).'%</span>';
}
	
function _product_link($fk_product) {
	global $db,$langs,$conf;
	
	$p=new Product($db);
	$p->fetch($fk_product);
	
	return $p->getNomUrl(1).' '.$p->label;
	
}
