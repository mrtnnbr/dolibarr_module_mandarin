<?php

require('config.php');
dol_include_once('/core/class/html.form.class.php');

if (!$user->rights->mandarin->graph->action_by_user) accessforbidden();
$langs->load('mandarin@mandarin');

$PDOdb = new TPDOdb;
$TData = array();
$rapport_ca_ht = GETPOST('rapport_ca_ht', 'int'); // Permet de diviser le montant HT pour réduire l'affichage
if (empty($rapport_ca_ht)) $rapport_ca_ht = 1;

$year_n_1 = GETPOST('year_n_1', 'int');
if (empty($year_n_1)) $year_n_1 = date('Y')-1;
$year_n = GETPOST('year_n', 'int');
if (empty($year_n)) $year_n = date('Y');

// Begin of page
llxHeader('', $langs->trans('mandarinTitleGraphCAHoraire'), '');

print_fiche_titre($langs->trans('RapportEvenementsParCommerciaux'));

print_form_filter();

$TData = get_data_tab();
draw_table($TData, get_list_id_user($TData), get_tab_label_action_comm());

llxFooter();

function print_form_filter() {
	
	global $db, $langs;
	
	$langs->load('users');
	
	$form = new Form($db);
	
	print '<form name="filter" methode="GET" action="'.$_SERVER['PHP_SELF'].'">';
	
	print $langs->trans('HierarchicalResponsible');
	print $form->select_users(GETPOST('userid'), 'userid', 1);
	
	print '<br /><br />';
	
	$date_deb = explode('/', $_REQUEST['date_deb']);
	$date_deb = implode('/', array_reverse($date_deb));
	$date_fin = explode('/', $_REQUEST['date_fin']);
	$date_fin = implode('/', array_reverse($date_fin));
	
	print 'Du ';
	$form->select_date(strtotime($date_deb), 'date_deb');
	print 'Au ';
	$form->select_date(strtotime($date_fin), 'date_fin');
	
	print '<input type="SUBMIT" class="butAction" value="Filtrer" />';
	
	print '</form>';
	
	print '<br />';
	
}

function get_data_tab() {
	
	global $db;
	
	$TData = array();
	
	$sql = 'SELECT u.rowid, a.code, COUNT(*) as nb_events
			FROM llx_user u
			LEFT JOIN llx_actioncomm a ON (a.fk_user_action = u.rowid)
			LEFT JOIN llx_c_actioncomm ON (a.id = a.fk_action)
			WHERE (u.rowid > 1)';

	if(!empty($_REQUEST['date_deb'])) $sql.= ' AND a.datep >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
	if(!empty($_REQUEST['date_fin'])) $sql.= ' AND a.datep <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
	if($_REQUEST['userid'] > 0) $sql.= ' AND u.fk_user = '.$_REQUEST['userid'];
	$sql.= ' AND a.code NOT IN ("AC_OTH_AUTO")
			GROUP BY u.rowid, a.fk_action';
	
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_events;
	
	return $TData;
	
}

function get_tab_label_action_comm() {
	
	global $db;
	
	$TLabel = array();
	
	$sql = 'SELECT code, libelle FROM '.MAIN_DB_PREFIX.'c_actioncomm';
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)) $TLabel[$res->code] = $res->libelle;
	
	return $TLabel;
	
}

function get_list_id_user(&$TData) {
	
	global $db;
	
	$TIDUser = array();
	
	foreach($TData as $type_event=>$TDonnes) {
		$TIDUser = array_merge(array_keys($TDonnes), $TIDUser);
	}
	$TIDUser = array_unique($TIDUser);
	
	ksort($TIDUser);
	
	return $TIDUser;
	
}

function draw_table(&$TData, &$TIDUser, &$TLabelActionComm) {
	
	global $db, $langs;
	
	$langs->load('agenda');
	
	print '<table class="noborder" width="100%">';
	
	$TTypeEvents = array_keys($TData);
	asort($TTypeEvents);
	
	print '<tr class="liste_titre">';
	print '<td>';
	print $langs->trans('Users'). ' / '.$langs->trans('EventType');
	print '</td>';
	
	foreach($TTypeEvents as $type) {
		$key=$langs->trans("Action".strtoupper($type));
        $valuetoshow=($type && $key != "Action".strtoupper($type)?$key:$TLabelActionComm[$type]);
		print '<td>'.$valuetoshow.'</td>';
	}
	
	print '<td>';
	print 'Total';
	print '</td>';
	print '</tr>';
	
	$u = new User($db);
	
	$class = array('pair', 'impair');
	$var = true;
	
	$TNBTotalType = array(); // Contient le nombre d'occurences pour chaque type d'événement, tout user confondu
	foreach($TIDUser as $id_user) {
		
		print '<tr class="'.$class[$var].'">';
		print '<td>';
		$u->fetch($id_user);
		print $u->getNomUrl(1);
		print '</td>';
		
		$nb_total_user = 0; // Contient le nombre d'occurences pour chaque user, tout type d'événement confondu
		foreach($TTypeEvents as $type) {
			print '<td>';
			print $TData[$type][$id_user];
			print '</td>';
			$nb_total_user+=$TData[$type][$id_user];
			$TNBTotalType[$type]+=$TData[$type][$id_user];
		}
		
		print '<td style="color:#332266">';
		print $nb_total_user;
		print '</td>';
		print '</tr>';
		
		$var = !$var;
		
	}
	
	print '<tr class="liste_total">';
	print '<td>';
	print 'Total';
	print '</td>';
	foreach($TTypeEvents as $type) print '<td>'.$TNBTotalType[$type].'</td>';
	print '</tr>';
	
	print '</table>';
	
}
