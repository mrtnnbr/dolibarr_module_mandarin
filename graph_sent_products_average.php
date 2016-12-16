<?php

require('config.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

if (!$user->rights->mandarin->graph->action_by_user) accessforbidden();
$langs->load('mandarin@mandarin');

$action = GETPOST('action');
$date_deb = GETPOST('date_deb');
$date_fin = GETPOST('date_fin');
$type = GETPOST('type');

switch($action) {
	
	case 'print_data':
		llxHeader('', $langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'), '');
		print dol_get_fiche_head('RapportMoyenneProduitsVendusCommandesEnvoyes');
		print_fiche_titre($langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'));
		print_form_filter($date_deb, $date_fin, $type);
		$TData = get_data_tab($date_deb, $date_fin, $type);
		if(!empty($TData)) draw_table($TData);
		break;
		
	case 'download_file':
		$TData = get_data_tab($date_deb, $date_fin, $type);
		download_file($TData, $type);
		break;
	
	default:
		llxHeader('', $langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'), '');
		print dol_get_fiche_head('RapportMoyenneProduitsVendusCommandesEnvoyes');
		print_fiche_titre($langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'));
		print_form_filter($date_deb, $date_fin, $type);
	
}

llxFooter();

function print_form_filter($date_deb, $date_fin, $type) {
	
	global $db, $langs;
	
	$langs->load('users');
	
	$form = new Form($db);
	
	print '<form name="filter" methode="GET" action="'.$_SERVER['PHP_SELF'].'">';
	
	print '<input type="hidden" name="action" value="print_data" />';
	
	print $langs->trans('Données analysées :&nbsp&nbsp');
	print $form->selectarray('type', array('expedition'=>'Expéditions', 'commande'=>'Commandes', 'facture'=>'Factures'), $type);
	
	print '<br /><br />';
	
	$date_deb = explode('/', $date_deb);
	$date_deb = implode('/', array_reverse($date_deb));
	$date_fin = explode('/', $date_fin);
	$date_fin = implode('/', array_reverse($date_fin));
	
	print 'Du ';
	$form->select_date(strtotime($date_deb), 'date_deb');
	print 'Au ';
	$form->select_date(strtotime($date_fin), 'date_fin');
	
	print '<input type="SUBMIT" class="butAction" value="Filtrer" />';
	
	print '</form>';
	
	print '<br />';
	
}

function get_data_tab($date_deb, $date_fin, $type) {
	
	global $db;
	
	if(empty($date_deb) || empty($date_fin)){
		setEventMessage('Renseignez une date de début et de fin', 'warnings');
		return 0;
	}
	
	$TData = array();
	
	$sql = get_sql($date_deb, $date_fin, $type);
	
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)){
		$TData[] = array(
							'semaine'=>$res->semaine
							,'id_prod'=>$res->id_prod
							,'ref_prod'=>$res->ref_prod
							,'nb_products'=>$res->nb_products
							,'avg_products'=>round($res->avg, 1)
						);
	}
	
	return $TData;
	
}

function get_sql($date_deb, $date_fin, $type) {
	
	if($type === 'expedition') {
		
		$field_date = 'e.date_valid';
		$field_date2 = 'e2.date_valid';
		
		$sql = 'SELECT WEEK('.$field_date.') as semaine
					   , '.$field_date.' as date_valid
					   , p.rowid as id_prod, p.ref as ref_prod
					   , SUM(ed.qty) as nb_products
					   ,(
					   		SELECT AVG(ed2.qty)
							FROM llx_expeditiondet ed2
							INNER JOIN llx_expedition e2 ON (ed2.fk_expedition = e2.rowid)
							INNER JOIN llx_commandedet cd2 ON (cd2.rowid = ed2.fk_origin_line)
							INNER JOIN llx_product p2 ON (p2.rowid = cd2.fk_product)
							WHERE p2.rowid = p.rowid';
							$sql.= ' AND '.$field_date2.' >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
							$sql.= ' AND '.$field_date2.' <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
		$sql.= '	   	) as avg
				FROM llx_expeditiondet ed
				INNER JOIN llx_expedition e ON (ed.fk_expedition = e.rowid)
				INNER JOIN llx_commandedet cd ON (cd.rowid = ed.fk_origin_line)
				INNER JOIN llx_product p ON (p.rowid = cd.fk_product)
				WHERE (1)';
		
	} elseif($type === 'commande') {
		
		$field_date = 'c.date_commande';
		
		$sql = 'SELECT WEEK('.$field_date.') as semaine
					   , '.$field_date.' as date_valid
					  , p.rowid as id_prod, p.ref as ref_prod
					   , SUM(cd.qty) as nb_products
				FROM llx_commandedet cd
				INNER JOIN llx_commande c ON (cd.fk_commande = c.rowid)
				INNER JOIN llx_product p ON (p.rowid = cd.fk_product)
				WHERE (1)';
				
	} elseif($type === 'facture') {
		
		$field_date = 'f.datef';
		
		$sql = 'SELECT WEEK('.$field_date.') as semaine
					   , '.$field_date.' as date_valid
					   , p.rowid as id_prod, p.ref as ref_prod
					   , SUM(fd.qty) as nb_products
				FROM llx_facturedet fd
				INNER JOIN llx_facture f ON (fd.fk_facture = f.rowid)
				INNER JOIN llx_product p ON (p.rowid = fd.fk_product)
				WHERE (1)';
		
	}
	
	$sql.= ' AND '.$field_date.' >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
	$sql.= ' AND '.$field_date.' <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
	
	$sql.= ' GROUP BY WEEK('.$field_date.'), p.ref';
	//echo $sql;exit;
	return $sql;
	
}

function draw_table(&$TData) {
	
	global $db, $langs;
	
	// Je ne fais pas de liste TBS parce que je vais devoir afficher le tableau en base 64 dans la dom pour le poster vers un fichier download.php pour télécharger un csv
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>Semaine</td>';
	print '<td>Produit</td>';
	print '<td>Nombres de produits</td>';
	print '<td>Moyenne période</td>';
	print '</tr>';
	
	$p = new Product($db);
	foreach($TData as $Tab) {
		$p->fetch($Tab['id_prod']);
		print '<tr>';
		print '<td>'.$Tab['semaine'].'</td>';
		print '<td>'.$p->getNomUrl(1).'</td>';
		print '<td>'.$Tab['nb_products'].'</td>';
		print '<td>'.$Tab['avg_products'].'</td>';
		print '</tr>';
		
	}

	print '</table>';
	
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=download_file&type='.GETPOST('type');
	print '&date_deb='.GETPOST('date_deb').'&date_fin='.GETPOST('date_fin');
	print '&date_debyear='.GETPOST('date_debyear').'&date_debmonth='.GETPOST('date_debmonth').'&date_debday='.GETPOST('date_debday');
	print '&date_finyear='.GETPOST('date_finyear').'&date_finmonth='.GETPOST('date_finmonth').'&date_finday='.GETPOST('date_finday').'">';
	print 'Télécharger CSV';
	print '</a>';
	print '</div>';
	
}

function download_file(&$TData, $type) {
	
	$name = $type.'_'.$_REQUEST['date_debyear'].$_REQUEST['date_debmonth'].$_REQUEST['date_debday'].'_'
					 .$_REQUEST['date_finyear'].$_REQUEST['date_finmonth'].$_REQUEST['date_finday'].'.csv';
	$fname = sys_get_temp_dir().'/'.$name;
	$f = fopen($fname, 'w+');
	fputcsv($f, array('Semaine', 'Produit', 'Nombre de produits', 'Moyenne période'), ';');
	
	foreach($TData as $Tab) {
		
		fputcsv($f, array($Tab['semaine']
						  , $Tab['ref_prod']
						  , $Tab['nb_products']
						  , $Tab['avg_products']
						  )
				, ';');
		
	}
	
	fclose($f);
	
	header('Content-Description: File Transfer');
    header('Content-Type: application/CSV');
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fname));
    readfile($fname);
	exit;
	
}
