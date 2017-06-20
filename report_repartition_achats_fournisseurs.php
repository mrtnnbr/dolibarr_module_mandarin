<?php 

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

$action = GETPOST('action');
$year = GETPOST('year');
$formother = new FormOther($db);
$sortfield = GETPOST('sortfield');
$sortorder= GETPOST('sortorder');
$param = 'year='.$year;

if(empty($sortfield)) {
	$sortfield = 'soc.nom';
	$sortorder = 'asc';
}

llxHeader('', $langs->trans('mandarinTitleRepartitionAchatsFournisseurs'), '');
//print dol_get_fiche_head('mandarinTitleRepartitionAchatsFournisseurs');
print_fiche_titre($langs->trans('mandarinTitleRepartitionAchatsFournisseurs'));

switch ($action) {
	case '':
		_print_form_repartition_achats($year);
		_print_repartition_achats($year);
		break;
	default:
		_print_form_repartition_achats($year);
		break;
}

function _print_form_repartition_achats($year) {
	
	global $db, $formother, $langs;
	
	print '<form name="formPrintRapport" method="GET" action="'.$_SERVER['PHP_SELF'].'">';
	print $langs->trans('YearOfSearch').'&nbsp;';
	$formother->select_year($year, 'year');
	print '<input class="butAction" type="SUBMIT" name="btSubForm" value="'.$langs->trans('Valid').'" />';
	print '</form>';
	
	print '<br />';
	
}

function _print_repartition_achats($year) {
	
	global $db, $langs, $sortfield, $sortorder, $param, $bc;
	
	$sql = 'SELECT soc.rowid, soc.nom, SUM(facf.total_ht) as mt_total_ht_producteur, (SELECT SUM(facf.total_ht) FROM '.MAIN_DB_PREFIX.'facture_fourn facf WHERE YEAR(facf.datef) = '.$year.') as mt_total_ht
			FROM '.MAIN_DB_PREFIX.'facture_fourn facf
			INNER JOIN '.MAIN_DB_PREFIX.'societe soc ON (soc.rowid = facf.fk_soc)
			WHERE YEAR(facf.datef) = '.$year.'
			GROUP BY soc.rowid';
	
	$sql.= $db->order($sortfield,$sortorder);
	
	$resql = $db->query($sql);
	
	if(!empty($resql)) {
		print '<table class="liste">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Supplier'),$_SERVER['PHP_SELF'],'soc.nom','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('TotalHT'),$_SERVER['PHP_SELF'],'mt_total_ht_producteur','',$param,'',$sortfield,$sortorder);
		print '<td>'.$langs->trans('Percent').'</td>';
		print '</tr>';
		$var=0;
		$soc = new Societe($db);
		while($res = $db->fetch_object($resql)) {
			print '<tr '.$bc[$var].'>';
			$soc->fetch($res->rowid);
			print '<td>'.$soc->getNomUrl(1).'</td>';
			print '<td>'.price($res->mt_total_ht_producteur, 0, $langs, 1, -1, -1, 'EUR').'</td>';
			print '<td>'.price($res->mt_total_ht_producteur / $res->mt_total_ht * 100, 0, $langs, 1, -1, 2).'</td>';
			print '</tr>';
			$var=!$var;
		}
		print '</table>';
	}
	
}
