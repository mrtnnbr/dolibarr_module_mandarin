<?php

require('config.php');
dol_include_once('/core/class/html.form.class.php');

if (!$user->rights->mandarin->graph->propal_commercial) accessforbidden();
$langs->load('mandarin@mandarin');

$userid = GETPOST('userid');
if(!$user->rights->mandarin->graph->propal_alldata) $userid = $user->id;

// Begin of page
llxHeader('', $langs->trans('linkMenuPropalesByCommercial'), '');

print dol_get_fiche_head('linkMenuPropalesByCommercial');
print_fiche_titre($langs->trans('linkMenuPropalesByCommercial'));

if($conf->global->MANDARIN_COMMERCIAL_GROUP > 0){ // la requête SQL étant dépendante de cette conf, il est important de la renseigh
    echo 'cool';
    print_form_filter($userid);

    $TData = get_data_tab($userid);
    
    draw_table($TData, get_list_id_user($TData), get_tab_label_statut());
    
    print '<br />';
    draw_graphique($TData, get_tab_label_statut());
}
else echo '<a href="'.dol_buildpath('/mandarin/admin/mandarin_setup.php', 1).'">'.$langs->trans('err_NOGROUPCOMM').'</a>';



//end of page
llxFooter();

function print_form_filter($userid) {
    
    global $db, $langs;
    
    $langs->load('users');
    
    $form = new Form($db);
    
    print '<form name="filter" methode="GET" action="'.$_SERVER['PHP_SELF'].'">';
    
    print $langs->trans('mandarinTitlePropalFactureCommercial');
    
    print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', '', 1);
    
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

function get_data_tab($userid) {
    
    global $db;
    
    $TData = array();
    
    $sql = getSqlForData($userid);
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    
    $sql = getSqlForData($userid, true);
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    
    $sql = getSqlForData($userid, false, true);
    $resql = $db->query($sql);  
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    
    $sql = getSqlForData($userid, false, false, true);
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    
    $sql = getSqlForData($userid, false, false, false, true);
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    $sql = getSqlForData($userid, false, false, false, false, true);
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->nb_propales;
    
    return $TData;
    
}

function getSqlForData($userid, $only_draft=false, $only_valid=false, $only_signed=false, $only_nonsigned=false, $only_factured=false)
{
    global $conf, $user;
    
    $sql = 'SELECT p.fk_statut as code, u.rowid, COUNT(*) as nb_propales'; 
    
    $sql.=' FROM '.MAIN_DB_PREFIX.'propal p
			LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = p.fk_user_author)
            LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user ug ON (ug.fk_user = p.fk_user_author)';
    
    $sql.=' WHERE 1=1';
    if ($only_draft) $sql.=' WHERE p.fk_statut = 0';
    elseif ($only_valid) $sql.=' WHERE p.fk_statut = 1';
    elseif ($only_signed) $sql.=' WHERE p.fk_statut = 2';
    elseif ($only_nonsigned) $sql.=' WHERE p.fk_statut = 3';
    elseif ($only_factured) $sql.=' WHERE p.fk_statut = 4';
    
    $sql.=' AND p.entity=' . $conf->entity;
    $sql.=' AND ug.fk_usergroup='.$conf->global->MANDARIN_COMMERCIAL_GROUP; 
    
    if(!empty($_REQUEST['date_deb'])) $sql.= ' AND p.datec >= "'.$_REQUEST['date_debyear'].'-'.$_REQUEST['date_debmonth'].'-'.$_REQUEST['date_debday'].' 00:00:00"';
    if(!empty($_REQUEST['date_fin'])) $sql.= ' AND p.datec <= "'.$_REQUEST['date_finyear'].'-'.$_REQUEST['date_finmonth'].'-'.$_REQUEST['date_finday'].' 23:59:59"';
    
    if($userid > 0) $sql.= ' AND u.rowid = '.$userid;
    
    $sql.= ' GROUP BY p.fk_statut, u.rowid';
           
    return $sql;
}

function get_tab_label_statut() {
    
    global $db, $langs;
    
    $TLabel = array();
    
    $sql = 'SELECT id, code, label, active FROM '.MAIN_DB_PREFIX.'c_propalst';
    $resql = $db->query($sql);
    while($res = $db->fetch_object($resql)) {
        $label = $res->label;
        $TLabel[$res->code] = array('rowid'=>$res->id, 'code'=>$res->code, 'label'=>$label, 'active'=>$res->active);
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

function draw_table(&$TData, &$TIDUser, &$TLabelStatut) {
    
    global $db, $langs;
    
    $langs->load('agenda');
    
    print '<table class="noborder" width="100%">';
    
    $TFkStatut = array_keys($TLabelStatut);
    asort($TFkStatut);
    
    print '<tr class="liste_titre">';
    print '<td>';
    print $langs->trans('User');
    print '</td>';
    
    // Rangement par pourcentage croissant
    ksort($TFkStatut);
    
    foreach($TFkStatut as $status) {
        print '<td>';
        
        print $TLabelStatut[$status]['label'];
                
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
        for($i = 0; $i < count($TFkStatut); $i++) {
            print '<td>';
            print '<a href="'.dol_buildpath('/comm/propal/list.php?search_author='.$u->login.'&propal_statut='.$i, 1).'">'.$TData[$i][$id_user].'</a>';
            print '</td>';
            $nb_total_user+=$TData[$i][$id_user];
            $TNBTotalType[$i]+=$TData[$i][$id_user];
        }
                
        print '<td>';
        print '<a href="'.dol_buildpath('/comm/propal/list.php?search_author='.$u->login, 1).'">'.$nb_total_user.'</a>';
        print '</td>';
        print '</tr>';
        
        $var = !$var;
        
    }
    
    print '<tr class="liste_total">';
    print '<td>';
    print 'Total';
    print '</td>';
    foreach($TFkStatut as $k =>$v) print '<td><a href="'.dol_buildpath('/comm/propal/list.php?propal_statut='.$TLabelStatut[$v]['rowid'], 1).'">'.$TNBTotalType[$k].'</a></td>';
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
