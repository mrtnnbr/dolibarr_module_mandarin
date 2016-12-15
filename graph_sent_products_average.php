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

llxHeader('', $langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'), '');
	
print dol_get_fiche_head('RapportMoyenneProduitsVendusCommandesEnvoyes');
print_fiche_titre($langs->trans('RapportMoyenneProduitsVendusCommandesEnvoyes'));
print_form_filter($date_deb, $date_fin, $type);

switch($action) {
	
	case 'print_data':
		$TData = get_data_tab($date_deb, $date_fin, $type);
		draw_table($TData);
		break;
	
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
						);
	}
	
	return $TData;
	
}

function get_sql($date_deb, $date_fin, $type) {
	
	if($type === 'expedition') {
		
		$field_date = 'e.date_valid';
		// TODO average
		$sql = 'SELECT WEEK('.$field_date.') as semaine
					   , '.$field_date.' as date_valid
					   , p.rowid as id_prod, p.ref as ref_prod
					   , SUM(ed.qty) as nb_products
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
	
	if(!empty($date_deb)) $sql.= ' AND '.$field_date.' >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
	if(!empty($date_fin)) $sql.= ' AND '.$field_date.' <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
	
	$sql.= ' GROUP BY WEEK('.$field_date.'), p.ref';
	
	return $sql;
	
}

function draw_table(&$TData) {
	
	global $db, $langs;
	
	// Je ne fais pas de liste TBS parce que je vais devoir afficher le tableau en base 64 dans la dom pour le poster vers un fichier download.php pour télécharger un csv
	print_entete();
	
	$p = new Product($db);
	foreach($TData as $Tab) {
		$p->fetch($Tab['id_prod']);
		print '<tr>';
		print '<td>'.$Tab['semaine'].'</td>';
		print '<td>'.$p->getNomUrl(1).'</td>';
		print '<td>'.$Tab['nb_products'].'</td>';
		print '</tr>';
		
	}

	print '</table>';
	
}

function print_entete() {
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>Semaine</td>';
	print '<td>Produit</td>';
	print '<td>Nombres de produits</td>';
	print '</tr>';
	
}
