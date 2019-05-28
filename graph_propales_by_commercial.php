<?php

require('config.php');
dol_include_once('/core/class/html.form.class.php');

if (!$user->rights->mandarin->graph->propal_commercial) accessforbidden();
$langs->load('mandarin@mandarin');

$userid = GETPOST('userid');
$ca = (bool)GETPOST('ca'); // Booléen pour afficher le chiffre d'affaire au lieu du nb de propales

// si l'utilisateur n'est pas admin, il ne voit que c'est chiffre
if(!$user->rights->mandarin->graph->propal_alldata) $userid = $user->id;

// Begin of page
llxHeader('', $langs->trans('linkMenuPropalesByCommercial'), '');

print dol_get_fiche_head('linkMenuPropalesByCommercial');
print_fiche_titre($langs->trans('linkMenuPropalesByCommercial'));

// la requête SQL étant dépendante de cette conf, il est important de la renseigner
// je vérifie si >0 pour prendre en compte le cas où il serait égal à -1 si l'utilisateur met la valeur nulle dans le select de configuration
if($conf->global->MANDARIN_COMMERCIAL_GROUP > 0){ 
    print_form_filter($userid);

    $TData = get_data_tab($userid);
    
    draw_table($TData, get_list_id_user($TData), get_tab_label_statut());
    
    print '<br />';
    draw_graphique($TData, get_tab_label_statut());
}
else print '<a href="'.dol_buildpath('/mandarin/admin/mandarin_setup.php', 1).'">'.$langs->trans('err_NOGROUPCOMM').'</a>';

//end of page
llxFooter();

function print_form_filter($userid) {
    
    global $db, $langs, $ca;
    
    $langs->load('users');
    
    $form = new Form($db);
    
    print '<form name="filter" methode="GET" action="'.$_SERVER['PHP_SELF'].'">';
    
    print $langs->trans('mandarinTitlePropalFactureCommercial');
    
    print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', '', 1);
    
    print '<br /><br />';
    print '<input type="hidden" name="ca" value="'.$ca.'">';
    
    $date_deb = explode('/', $_REQUEST['date_deb']);
    $date_deb = implode('/', array_reverse($date_deb));
    $date_fin = explode('/', $_REQUEST['date_fin']);
    $date_fin = implode('/', array_reverse($date_fin));
    
    print $langs->trans('propalCreationDate') . ' ' . strtolower($langs->trans('From')) . ' ';
    $form->select_date(strtotime($date_deb), 'date_deb');
    print $langs->trans('to') .' ';
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
    while($res = $db->fetch_object($resql)) $TData[$res->code][$res->rowid] = $res->propales;

    return $TData;
    
}

function getSqlForData($userid)
{
    global $conf, $user, $ca;
    
    if($ca) $sql = 'SELECT DISTINCT p.fk_statut as code, u.rowid, SUM(p.total_ht) as propales';
    else $sql = 'SELECT p.fk_statut as code, u.rowid, COUNT(*) as propales';
    
    
    $sql.=' FROM '.MAIN_DB_PREFIX.'propal p
            INNER JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux sc ON sc.fk_soc = p.fk_soc
			LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = sc.fk_user)
            LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user ug ON (ug.fk_user = p.fk_user_author)';
    
    
    $sql.=' WHERE 1=1';
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
    
    global $db, $langs,$ca;
    
    $langs->load('agenda');
    
    print '<table class="noborder" width="100%">';
    
    $TFkStatut = array_keys($TLabelStatut);
    asort($TFkStatut);
    
    print '<tr class="liste_titre">';
    print '<td>';
    print $langs->trans('User');
    print '</td>';
    
    ksort($TFkStatut);
    
    foreach($TFkStatut as $status) {
        print '<td align="right">';
        
        print $TLabelStatut[$status]['label'];
                
        print '</td>';
    }
    
    print '<td align="right">';
    print 'Total';
    print '</td>';
    print '</tr>';
    
    $u = new User($db);
    
    $class = array('pair', 'impair');
    $var = true;
    
    $TNBTotalType = array(); // Contient le nombre d'occurences pour chaque type de propal, tout user confondu
    foreach($TIDUser as $id_user) {
        
        print '<tr class="'.$class[$var].'">';
        print '<td>';
        $u->fetch($id_user);
        print $u->getNomUrl(1);
        print '</td>';
        
        $nb_total_user = 0; // Contient le nombre d'occurences pour chaque user, tout type d'événement confondu
        for($i = 0; $i < count($TFkStatut); $i++) {
            print '<td align="right">';
            $unit = ($ca) ? price($TData[$i][$id_user]) : $TData[$i][$id_user];
            print '<a href="'.dol_buildpath('/comm/propal/list.php?search_author='.$u->login.'&propal_statut='.$i, 1).'">'. $unit .'</a>';
            print '</td>';
            $nb_total_user+=$TData[$i][$id_user];
            $TNBTotalType[$i]+=$TData[$i][$id_user];
        }
                
        print '<td align="right">';
        $unit = ($ca) ? price($nb_total_user) : $nb_total_user;
        print '<a href="'.dol_buildpath('/comm/propal/list.php?search_author='.$u->login, 1).'">'.$unit.'</a>';
        print '</td>';
        print '</tr>';
        
        $var = !$var;
        
    }
    
    print '<tr class="liste_total">';
    print '<td>';
    print 'Total';
    print '</td>';
   
    foreach($TFkStatut as $k =>$v){
        $unit = ($ca) ? price($TNBTotalType[$k]) : $TNBTotalType[$k];
        print '<td align="right"><a href="'.dol_buildpath('/comm/propal/list.php?propal_statut='.$TLabelStatut[$v]['rowid'], 1).'">'.$unit.'</a></td>';
    }
    $total = ($ca) ? price(array_sum($TNBTotalType)) : array_sum($TNBTotalType);
    print '<td align="right">' . $total . '</td>';
    print '</tr>';
    
    print '</table>';
    
}

function draw_graphique(&$TData, &$TabTrad) {
    
    global $langs, $ca;

    $PDOdb = new TPDOdb;
    
    $TSum = array();
    
    foreach($TData as $code=>$Tab) {
        foreach ($TabTrad as $k=>$v){
            if($v['rowid'] == $code) $code = $k;
        }
        if (empty($TabTrad[$code]['label'])) continue;
        $TSum[] = array($TabTrad[$code]['label'], array_sum($Tab));
    }
    
    $listeview = new TListviewTBS('graphPropalByComm');
    
    $title = ($ca) ? 'titleGraphCaPropalByComm' : 'titleGraphPropalByComm';
    print $listeview->renderArray($PDOdb, $TSum
        ,array(
            'type' => 'chart'
            ,'chartType' => 'PieChart'
            ,'liste'=>array(
                'titre'=>$langs->transnoentitiesnoconv($title)
            )
        )
        );
    
}
