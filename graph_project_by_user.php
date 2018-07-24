<?php

require('config.php');
dol_include_once('/core/class/html.form.class.php');

if (!$user->rights->mandarin->graph->project_by_user) accessforbidden();
$langs->load('mandarin@mandarin');

$userid = GETPOST('userid');
if($userid != 0){
    $userdefault = $userid;
}
elseif(user_est_responsable_hierarchique()){
    $userid = $user->id;
}

if(!empty($conf->global->GRAPH_PROJECT_BY_USER_HIERARCHYME) && (empty($userid) || $userid < 0)){
    $userid = $user->id;
}

// Get user group and default
$groupid= GETPOST('groupid', 'int');
if(empty($groupid) && $conf->global->MANDARIN_COMMERCIAL_GROUP > 0)
{
	$groupid=$conf->global->MANDARIN_COMMERCIAL_GROUP;
}

// Begin of page
llxHeader('', $langs->trans('linkMenuProjectByUserReport'), '');

print dol_get_fiche_head('linkMenuProjectByUserReport');
print_fiche_titre($langs->trans('linkMenuProjectByUserReport'));

print_form_filter($userid,$groupid);

$TData = get_data_tab($userid,$groupid);
draw_table($TData, get_list_id_user($TData), get_tab_label_statut_opportunite());

print '<br />';
draw_graphique($TData, get_tab_label_statut_opportunite());

llxFooter();

function print_form_filter($userid,$groupid=-1) {
	
	global $db, $langs, $conf;
	
	$langs->load('users');
	
	$form = new Form($db);
	
	print '<form name="filter" methode="GET" action="'.$_SERVER['PHP_SELF'].'">';
	
	print $langs->trans('HierarchicalResponsible');
	
	$include = '';
	$show_empty=1;
	if(!empty($conf->global->GRAPH_PROJECT_BY_USER_HIERARCHYME)){
	   $include = 'hierarchyme';
	   $show_empty=0;
	}
	
	print $form->select_dolusers($userid, 'userid', $show_empty, '', 0, $include, '', 0, 0, 0, '', 0, '', '', 1);
	
	// User group filter
	print ' &nbsp;&nbsp;&nbsp;&nbsp; ';
	print $langs->trans('UserGroup');
	print $form->select_dolgroups($groupid, 'groupid',1);

	
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

function user_est_responsable_hierarchique() {
	
	global $db, $user;
	
	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'user WHERE fk_user = '.$user->id;
	$resql = $db->query($sql);
	$res = $db->fetch_object($resql);
	
	return $res->rowid > 0;
	
}

function get_data_tab($userid,$groupid=0) {
	
	global $db;
	
	$TData = array();
	
	$sql = getSqlForData($userid,false,false,$groupid);
	$resql = $db->query($sql);
	if(!empty($resql)){
	   while($res = $db->fetch_object($resql)) $TData[$res->code][$res->fk_socpeople] = $res->nb_projects;
	}
	
	$sql = getSqlForData($userid, true,false,$groupid);
	$resql = $db->query($sql);
	if(!empty($resql)){
	   while($res = $db->fetch_object($resql)) $TData[$res->code][$res->fk_socpeople] = $res->nb_projects;
	}
	
	$sql = getSqlForData($userid, false, true,$groupid);
	$resql = $db->query($sql);
	
	if(!empty($resql)){
	   while($res = $db->fetch_object($resql)) $TData[$res->code][$res->fk_socpeople] = $res->nb_projects;
	}
	
	return $TData;
	
}

function getSqlForData($userid, $only_draft=false, $only_close=false,$groupid=0)
{

	if (!$only_draft && !$only_close) $sql = 'SELECT ls.code, c.fk_socpeople, COUNT(*) as nb_projects';
	elseif ($only_draft)  $sql = 'SELECT "DRAFT" as code, c.fk_socpeople, COUNT(*) as nb_projects';
	elseif ($only_close)  $sql = 'SELECT "CLOSE" as code, c.fk_socpeople, COUNT(*) as nb_projects';
	
	$sql.=' FROM '.MAIN_DB_PREFIX.'projet p 
			LEFT JOIN '.MAIN_DB_PREFIX.'c_lead_status ls ON (p.fk_opp_status = ls.rowid)
			LEFT JOIN '.MAIN_DB_PREFIX.'element_contact c ON (p.rowid = c.element_id AND fk_c_type_contact IN(160, 161))
			LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = c.fk_socpeople)';
			
	if (!$only_draft && !$only_close) $sql.=' WHERE p.fk_statut = 1	AND fk_opp_status > 0';
	elseif ($only_draft) $sql.=' WHERE p.fk_statut = 0';
	elseif ($only_close) $sql.=' WHERE p.fk_statut = 2';
	
	$sql.=' AND u.statut = 1';
	
	// Filter by user group
	if($groupid>0)
	{
		$sql.=' AND u.rowid IN ( SELECT ugu.fk_user FROM '.MAIN_DB_PREFIX.'usergroup_user ugu WHERE ugu.fk_usergroup = '.(int)$groupid.' ) ';
	}
	
	if(!empty($_REQUEST['date_deb'])) $sql.= ' AND p.dateo >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
	if(!empty($_REQUEST['date_fin'])) $sql.= ' AND p.dateo <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
	if($userid > 0) $sql.= ' AND u.fk_user = '.$userid;
	
	if (!$only_draft && !$only_close) $sql.= ' GROUP BY p.fk_opp_status, c.fk_socpeople';
	else $sql.= ' GROUP BY c.fk_socpeople';

	$sql .= ' ORDER BY ls.percent';

	return $sql;
}

function get_tab_label_statut_opportunite() {
	
	global $db, $langs;
	
	$TLabel = array();
	
	$sql = 'SELECT rowid, code, label, percent FROM '.MAIN_DB_PREFIX.'c_lead_status';
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)) {
		$label = $langs->transnoentitiesnoconv("OppStatus".$res->code) != "OppStatus".$res->code
			  ? $langs->transnoentitiesnoconv("OppStatus".$res->code)
			  : $res->label;
		$TLabel[$res->code] = array('rowid'=>$res->rowid, 'code'=>$res->code, 'label'=>$label, 'percent'=>$res->percent);
	}
	
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

function draw_table(&$TData, &$TIDUser, &$TLabelStatutOpportunite) {
	
	global $db, $langs;
	
	$langs->load('agenda');
	
	print '<table class="noborder" width="100%">';
	
	$TFkStatutOpportunite = array_keys($TData);
	asort($TFkStatutOpportunite);
	
	print '<tr class="liste_titre">';
	print '<td>';
	print $langs->trans('User'). ' / '.$langs->trans('OpportunityStatus');
	print '</td>';
	
	// Rangement par pourcentage croissant
	ksort($TFkStatutOpportunite);

	foreach($TFkStatutOpportunite as $status) {
		print '<td>';
		if ($status != 'DRAFT' && $status != 'CLOSE')
		{
			print $TLabelStatutOpportunite[$status]['label'];
			print ' ('.$TLabelStatutOpportunite[$status]['percent'].')';
		}
		elseif ($status == 'DRAFT') {
			print $langs->trans('Draft');
		}
		elseif ($status == 'CLOSE') {
			print $langs->trans('Closed');
		}
		
		print '</td>';
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
		foreach($TFkStatutOpportunite as $status) {
			print '<td>';
			print '<a href="'.dol_buildpath('/projet/list.php?search_opp_status='.$TLabelStatutOpportunite[$status]['rowid'].'&search_user='.$id_user, 1).'">'.$TData[$status][$id_user].'</a>';
			print '</td>';
			$nb_total_user+=$TData[$status][$id_user];
			$TNBTotalType[$status]+=$TData[$status][$id_user];
		}
		
		print '<td>';
		print '<a href="'.dol_buildpath('/projet/list.php?search_user='.$id_user, 1).'">'.$nb_total_user.'</a>';
		print '</td>';
		print '</tr>';
		
		$var = !$var;
		
	}
	
	print '<tr class="liste_total">';
	print '<td>';
	print 'Total';
	print '</td>';
	foreach($TFkStatutOpportunite as $status) print '<td><a href="'.dol_buildpath('/projet/list.php?search_opp_status='.$TLabelStatutOpportunite[$status]['rowid'], 1).'">'.$TNBTotalType[$status].'</a></td>';
	print '<td>'.array_sum($TNBTotalType).'</td>';
	print '</tr>';
	
	print '</table>';
	
}

function draw_graphique(&$TData, &$TabTrad) {
	
	global $langs;
	
	$PDOdb = new TPDOdb;
	
	$TSum = array();

	foreach($TData as $code=>$Tab) {
		if (empty($TabTrad[$code]['label'])) continue;
		$TSum[] = array($TabTrad[$code]['label'], array_sum($Tab));
	}

	$listeview = new TListviewTBS('graphProjectByType');
	
	print $listeview->renderArray($PDOdb, $TSum
		,array(
			'type' => 'chart'
			,'chartType' => 'PieChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphProjectByType')
			)
		)
	);
	
}
