<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

if (!$user->rights->mandarin->list->diff_by_commercial) accessforbidden();

llxHeader('', $langs->trans('titleGraphByCommercial'), '');

dol_fiche_head();

$PDOdb=new TPDOdb;
//$PDOdb->debug=true;

$TUser = array();



$date_start = dol_mktime(0, 0, 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));
$date_end = dol_mktime(23, 59, 59, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));

$TData=array();

$sql = 'SELECT p.fk_user_valid, SUM(p.total_ht) as propal_amout_ht, SUM(p.total) as propal_amout_ttc';
$sql.= ' FROM '.MAIN_DB_PREFIX.'propal p';
$sql.= ' WHERE p.fk_statut IN ('..', 4)'; // TODO vérifier statut
$sql.= ' AND p.date_valid BETWEEN "'.date('Y-m-d H:i:s', $date_start).'" AND "'.date('Y-m-d H:i:s', $date_end).'"';
$sql.= ' GROUP BY p.fk_user_valid';
new Propal;
$Tab = $PDOdb->ExecuteAsArray($sql);

// TODO réécrire l'afféctation
foreach($Tab as &$row)
{
	if (!isset($TUser[$row->fk_user_valid]))
	{
		$u = new User($db);
		$u->fetch($row->fk_user_valid);
		$TUser[$row->fk_user_valid] = $u;
	}
	
	$TData[$row->fk_user_valid] = array(
		'commercial' => $TUser[$row->fk_user_valid]->getNomUrl()
		,'propal_amount_ht' => $row->propal_amout_ht
		,'propal_amount_ttc' => $row->propal_amout_ttc
	);
}


// TODO finir la requete pour les commandes
$sql = 'SELECT c.fk_user_valid, SUM(c.total_ht) as commande_amout_ht, SUM(c.total_ttc) as commande_amout_ttc';
$sql.= ' FROM '.MAIN_DB_PREFIX.'commande c';
$sql.= ' WHERE c.fk_statut IN (2, 4)'; // TODO vérifier statut
$sql.= ' AND c.date_valid BETWEEN "'.date('Y-m-d H:i:s', $date_start).'" AND "'.date('Y-m-d H:i:s', $date_end).'"';
$sql.= ' GROUP BY c.fk_user_valid';

$Tab = $PDOdb->ExecuteAsArray($sql);

// TODO réécrire l'afféctation
foreach($Tab as &$row)
{
	if (!isset($TUser[$row->fk_user_valid]))
	{
		$u = new User($db);
		$u->fetch($row->fk_user_valid);
		$TUser[$row->fk_user_valid] = $u;
	}
	
	$TData[$row->fk_user_valid]['commercial'] = $TUser[$row->fk_user_valid]->getNomUrl();
	$TData[$row->fk_user_valid]['commande_amount_ht'] = $row->commande_amout_ht;
	$TData[$row->fk_user_valid]['commande_amount_ttc'] = $row->commande_amout_ttc;
}

foreach ($TData as $fk_user => &$TInfo)
{
	if (!isset($TInfo['propal_amount_ht'])) $TInfo['propal_amount_ht'] = 0;
	if (!isset($TInfo['propal_amount_ttc'])) $TInfo['propal_amount_ttc'] = 0;
	if (!isset($TInfo['commande_amount_ht'])) $TInfo['commande_amount_ht'] = 0;
	if (!isset($TInfo['commande_amount_ttc'])) $TInfo['commande_amount_ttc'] = 0;
	
	$TInfo['diff_ht'] = abs($TInfo['propal_amount_ht'] - $TInfo['commande_amount_ht']);
	$TInfo['diff_ttc'] = abs($TInfo['propal_amount_ttc'] - $TInfo['commande_amount_ttc']);
}


// TODO terminer l'affichage



$formCore=new TFormCore('auto','form2', 'get');

// print 2x datepicker
$form = new Form($db);

$headsearch.=$form->select_date($date_start, 'start', 0, 0, 1, '', 1, 0, 1, 0);
$headsearch.=$form->select_date($date_end, 'end', 0, 0, 1, '', 1, 0, 1, 0);

$headsearch.= $formCore->btsubmit($langs->trans('Ok'),'bt_ok');

$ColFormat = array(
	'commercial' => 'string'
	,'propal_amount_ht' => 'number'
	,'propal_amount_ttc' => 'number'
	,'commande_amount_ht' => 'number'
	,'commande_amount_ttc' => 'number'
	,'diff_ht' => 'number'
	,'diff_ttc' => 'number'
);
$ColTotal = array(
	'propal_amount_ht' => 'sum'
	,'propal_amount_ttc' => 'sum'
	,'commande_amount_ht' => 'sum'
	,'commande_amount_ttc' => 'sum'
	,'diff_ht' => 'sum'
	,'diff_ttc' => 'sum'
);

$listeview = new TListviewTBS('titleGraphByCommercial');
print $listeview->renderArray($PDOdb, $TData
	,array(

		'liste'=>array(
			'titre'=>$langs->transnoentitiesnoconv('titleGraphByCommercial')
			,'head_search'=>$headsearch
		)
		,'type'=>$ColFormat
		,'title'=>array(
			'commercial' => $langs->transnoentitiesnoconv('commercial')
			,'propal_amount_ht' => $langs->transnoentitiesnoconv('propal_amount_ht')
			,'propal_amount_ttc' => $langs->transnoentitiesnoconv('propal_amount_ttc')
			,'commande_amount_ht' => $langs->transnoentitiesnoconv('commande_amount_ht')
			,'commande_amount_ttc' => $langs->transnoentitiesnoconv('commande_amount_ttc')
			,'diff_ht' => $langs->transnoentitiesnoconv('diff_ht')
			,'diff_ttc' => $langs->transnoentitiesnoconv('diff_ttc')
		)
		,'math'=>$ColTotal
		,'export'=>array('CSV')

	)
);

?>
<style type="text/css">
	#titleGraphByCommercial th.liste_titre {
		text-align: right;
	}
	
	#titleGraphByCommercial th.liste_titre:first-child {
		text-align: left;
	}
</style>
<?php

$formCore->end();

dol_fiche_end();

llxFooter();

