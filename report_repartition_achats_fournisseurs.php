<?php 

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

$action = GETPOST('action');
$date_debut = GETPOST('date_debut');
$date_fin = GETPOST('date_fin');
$form = new Form($db);
$formother = new FormOther($db);
$sortfield = GETPOST('sortfield');
$sortorder= GETPOST('sortorder');

if(empty($date_debut)) $date_debut = date('Y-01-01');
else $date_debut = GETPOST('date_debutyear')
									.'-'.str_pad(GETPOST('date_debutmonth'), 2, 0, STR_PAD_LEFT)
									.'-'.str_pad(GETPOST('date_debutday'), 2, 0, STR_PAD_LEFT);

if(empty($date_fin)) $date_fin = date('Y-12-31');
else $date_fin = GETPOST('date_finyear')
								.'-'.str_pad(GETPOST('date_finmonth'), 2, 0, STR_PAD_LEFT)
								.'-'.str_pad(GETPOST('date_finday'), 2, 0, STR_PAD_LEFT);

$param = 'date_debutyear='.GETPOST('date_debutyear')
		.'&date_debutmonth='.GETPOST('date_debutmonth')
		.'&date_debutday='.GETPOST('date_debutday')
		.'&date_finyear='.GETPOST('date_finyear')
		.'&date_finmonth='.GETPOST('date_finmonth')
		.'&date_finday='.GETPOST('date_finday')
		.'&date_debut='.$date_debut
		.'&date_fin='.$date_fin
		.'&action='.$action;
												
if(empty($sortfield)) {
	$sortfield = 'soc.nom';
	$sortorder = 'asc';
}

llxHeader('', $langs->trans('mandarinTitleRepartitionAchatsFournisseurs'), '');
print_fiche_titre($langs->trans('mandarinTitleRepartitionAchatsFournisseurs'));

switch ($action) {
	case 'report':
		_print_form_repartition_achats($date_debut, $date_fin);
		_print_repartition_achats($date_debut, $date_fin);
		break;
	default:
		_print_form_repartition_achats($date_debut, $date_fin);
		break;
}

function _print_form_repartition_achats($date_debut, $date_fin) {
	
	global $db, $form, $formother, $langs;
	
	print '<form name="formPrintRapport" method="GET" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="action" value="report" />';
	print $langs->trans('Période d\'analyse').', Du&nbsp;';
	$form->select_date($date_debut, 'date_debut', 0, 0, 0, '', 1, 1);
	print 'Au&nbsp;';
	$form->select_date($date_fin, 'date_fin', 0, 0, 0, '', 1, 1);
	print '<input class="butAction" type="SUBMIT" name="btSubForm" value="'.$langs->trans('Valid').'" />';
	print '</form>';
	
	print '<br />';
	
}

function _print_repartition_achats($date_debut, $date_fin) {
	
	global $conf, $db, $langs, $sortfield, $sortorder, $param, $bc;
	
	$sql = 'SELECT soc.rowid, soc.nom, SUM(facf.total_ht) as mt_total_ht_producteur, (SELECT SUM(facf.total_ht) FROM '.MAIN_DB_PREFIX.'facture_fourn facf WHERE facf.datef BETWEEN "'.$date_debut.'" AND "'.$date_fin.'") as mt_total_ht
			FROM '.MAIN_DB_PREFIX.'facture_fourn facf
			INNER JOIN '.MAIN_DB_PREFIX.'societe soc ON (soc.rowid = facf.fk_soc)
			WHERE facf.datef BETWEEN "'.$date_debut.'" AND "'.$date_fin.'"
			GROUP BY soc.rowid';
	
	$sql.= $db->order($sortfield,$sortorder);
	
	$resql = $db->query($sql);
	
	if(!empty($resql)) {
		
		print '<table class="liste">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Supplier'),$_SERVER['PHP_SELF'],'soc.nom','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('TotalHT'),$_SERVER['PHP_SELF'],'mt_total_ht_producteur','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Percent'),$_SERVER['PHP_SELF'],'mt_total_ht_producteur','',$param,'',$sortfield,$sortorder);
		print '</tr>';
		
		$var=0;
		$soc = new Societe($db);
		
		while($res = $db->fetch_object($resql)) {
			
			$percent = $res->mt_total_ht_producteur / $res->mt_total_ht * 100;
			
			if(!empty($conf->global->MANDARIN_POURCENTAGE_ALERTE)
					&& $percent > $conf->global->MANDARIN_POURCENTAGE_ALERTE) $plus = ' bgcolor="#F5A9A9" style="font-weight:bold;"';
			else $plus = $bc[$var];
			
			print '<tr '.$plus.'>';
			$soc->fetch($res->rowid);
			print '<td>'.$soc->getNomUrl(1).'</td>';
			
			print '<td >'.price($res->mt_total_ht_producteur, 0, $langs, 1, -1, -1, 'EUR').'</td>';
			print '<td >'.price($percent, 0, $langs, 1, -1, 2).' %</td>';
			print '</tr>';
			$var=!$var;
			
		}
		
		print '</table>';
		
	}
	
}
