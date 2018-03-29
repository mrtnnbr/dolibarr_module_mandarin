<?php

require('config.php');

if(!$user->rights->doc2project->read) accessforbidden();

dol_include_once("/doc2project/lib/report.lib.php");
dol_include_once("/doc2project/filtres.php");

llxHeader('',$langs->trans('Report'));
print_fiche_titre($langs->trans("Report"));
?>
<script type="text/javascript" src="<?php echo COREHTTP?>includes/js/dataTable/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?php echo COREHTTP?>includes/js/dataTable/js/dataTables.tableTools.min.js"></script>

<link rel="stylesheet" href="<?php echo COREHTTP?>includes/js/dataTable/css/jquery.dataTables.css" type="text/css" />
<link rel="stylesheet" href="<?php echo COREHTTP?>includes/js/dataTable/css/dataTables.tableTools.css" type="text/css" />
<?php

$PDOdb=new TPDOdb;

_fiche($PDOdb, 'statistiques_projet');

//Déclaration des DataTables
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#statistiques_projet').dataTable({
            "sDom": 'T<"clear">lfrtip',
            "oTableTools": {
                "sSwfPath": "<?php echo COREHTTP?>includes/js/dataTable/swf/copy_csv_xls_pdf.swf"
            },
            "bSort": false,
            "iDisplayLength": 100,
            "oLanguage": {
                    "sProcessing":     "Traitement en cours...",
                    "sSearch":         "Rechercher&nbsp;:",
                    "sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
                    "sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                    "sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
                    "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                    "sInfoPostFix":    "",
                    "sLoadingRecords": "Chargement en cours...",
                    "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                    "sEmptyTable":     "Aucune donnée disponible dans le tableau",
                    "oPaginate": {
                        "sFirst":      "Premier",
                        "sPrevious":   "Pr&eacute;c&eacute;dent",
                        "sNext":       "Suivant",
                        "sLast":       "Dernier"
                    },
                    "oAria": {
                        "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
                        "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
                    }
            }
        });
    });
</script>
<?php

function _fiche(&$PDOdb,$report=''){
	
	echo '<div>';
	
	$form = new TFormCore('auto','formReport', 'GET');
	
	echo $form->hidden('action', 'report');
	
	$TRapport = array(
			'statistiques_projet'=>"Statistiques Projets",
			'statistiques_categorie'=>'Statistiques Catégories',
	);
	
	$THide = array();
	
	if(!empty($report)){
		
		if(!in_array($report,$THide)){
			//Affichage des filtres
			_get_filtre($report,$PDOdb,$form);
		}
		else{
			echo $form->btsubmit('Afficher', '');
		
		}
		echo $form->end();
		
		$TReport=_get_statistiques_projet($PDOdb);
		_print_statistiques_projet($TReport);
		
	}
	else{
		echo $form->btsubmit('Afficher', '');
	}
	
	echo '</div>';
}

function _get_statistiques_projet(&$PDOdb){
	global $db,$conf;
	
	
	$idprojet = GETPOST('id_projet');
	
	$date_deb = GETPOST('date_deb');
	$t_deb = !$date_deb ? 0 : Tools::get_time($date_deb);
	
	$date_fin = GETPOST('date_fin');
	$t_fin = !$date_fin ? 0 : Tools::get_time($date_fin);
	
	$sql = "SELECT p.rowid, dateo, datee FROM ".MAIN_DB_PREFIX.'projet p';
	
	if($idprojet > 0) $sql.= " WHERE p.rowid = ".$idprojet;
	
	$sortfield = GETPOST('sortfield');
	$sortorder = GETPOST('sortorder');
//echo $sql;exit;	
	$PDOdb->Execute($sql);
	$TRapport = array();
	//pre($sql, true);
	while ($PDOdb->Get_line()) {
		list($total_cmd,$total_fac,$total_fac_fourn,$total_cmd_fourn_no_fac) = _getTotauxProjet($PDOdb, $PDOdb->Get_field('rowid'),$t_deb, $t_fin);
		
		//$marge = $vente - $achat - $ndf- $PDOdb->Get_field('total_cout_homme');
		$TRapport[]= array(
				"IdProject"					=> $PDOdb->Get_field('rowid'),
				"date_debut"				=> $PDOdb->Get_field('dateo'),
				"date_fin"					=> $PDOdb->Get_field('datee'),
				"total_cmd"       			=> $total_cmd,
				"total_fac"       			=> $total_fac,
				"total_fac_fourn"   		=> $total_fac_fourn,
				"total_cmd_fourn_no_fac"	=> $total_cmd_fourn_no_fac
		);
		
	}
	//pre($TRapport, true);
	return $TRapport;
	
	
}

function _get_filtre($report,$PDOdb,$form){
	
	echo '<div class="tabBar">';
	echo '<table>';
	
	_print_filtre_liste_projet($form,$PDOdb);
	_print_filtre_plage_date($form);
	
	echo '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
	echo '</table>';
	
	echo '</div>';
}

function _getTotauxProjet($PDOdb, $fk_projet, $t_deb=0,$t_fin=0){
	global $db, $conf;
	
	$total_cmd = $total_fac = $total_fac_fourn = $total_cmd_fourn_no_fac = 0;
	
	
	// CMD
	$sqlCmd = "SELECT SUM(c.total_ht) as total
			        FROM ".MAIN_DB_PREFIX."commande as c 
			        WHERE f.fk_statut > 0 AND c.fk_projet = ".$fk_projet.($t_deb>0 && $t_fin>0 ? " AND c.date_commande BETWEEN '".date('Y-m-d', $t_deb)."' AND '".date('Y-m-d', $t_fin)."' " : ''  );
	$PDOdb2= new TPDOdb;
	$PDOdb2->Execute($sqlCmd);
	while($obj = $PDOdb2->Get_line()) $total_cmd += $obj->total;
	
	// Facture
	$sqlFac='SELECT SUM(f.total) as total
			    FROM '.MAIN_DB_PREFIX.'facture f
			    WHERE f.fk_statut > 0 AND f.fk_projet = '.$fk_projet.($t_deb>0 && $t_fin>0 ? " AND f.datef BETWEEN '".date('Y-m-d', $t_deb)."' AND '".date('Y-m-d', $t_fin)."' " : ''  );
	$PDOdb2->Execute($sqlFac);
	while($obj = $PDOdb2->Get_line()) $total_fac+=$obj->total;
	
	// Factures fournisseurs
	$sqlFacFourn='SELECT SUM(total_ht) as total
					FROM '.MAIN_DB_PREFIX.'facture_fourn ff
					WHERE f.fk_statut > 0 ff.fk_projet = '.$fk_projet.($t_deb>0 && $t_fin>0 ? " AND ff.datef BETWEEN '".date('Y-m-d', $t_deb)."' AND '".date('Y-m-d', $t_fin)."' " : ''  );
	$PDOdb2->Execute($sqlFacFourn);
	while($obj = $PDOdb2->Get_line()) $total_fac_fourn+=$obj->total;
	
	// Factures fournisseurs
	$sqlCmdFournNoFac='SELECT SUM(total_ht) as total
						FROM llx_commande_fournisseur cf
						LEFT JOIN llx_element_element ee ON (ee.sourcetype="order_supplier" AND targettype="invoice_supplier" AND fk_source = cf.rowid)
						WHERE cf.fk_statut = 3 AND ee.rowid IS NULL AND cf.fk_projet = '.$fk_projet.($t_deb>0 && $t_fin>0 ? " AND cf.date_commande BETWEEN '".date('Y-m-d', $t_deb)."' AND '".date('Y-m-d', $t_fin)."' " : ''  ); // statut 3 = commande passée
	//echo $sqlCmdFournNoFac;exit;
	$PDOdb2->Execute($sqlCmdFournNoFac);
	
	while($obj = $PDOdb2->Get_line()) $total_cmd_fourn_no_fac+=$obj->total;
	//var_dump($total_cmd_fourn_no_fac);
	return array(
	$total_cmd
	,$total_fac
	,$total_fac_fourn
	,$total_cmd_fourn_no_fac
	);
	
}

function _print_statistiques_projet(&$TRapport){
	global $conf, $db;
	
	dol_include_once('/core/lib/date.lib.php');
	dol_include_once('/projet/class/project.class.php');
	
	$id_projet = GETPOST('');
	
	$params = $_SERVER['QUERY_STRING'];
	
	?>
    <div class="tabBar" style="padding-bottom: 25px;">
        <table id="statistiques_projet" class="noborder" width="100%">
            <thead>
                <tr style="text-align:left;" class="liste_titre nodrag nodrop">
                    <th class="liste_titre">Réf. Projet</th>
                    <?php
                    print_liste_field_titre('Date début', $_SERVER["PHP_SELF"], "p.dateo", "", $params, "", $sortfield, $sortorder);
                    print_liste_field_titre('Date fin', $_SERVER["PHP_SELF"], "p.datee", "", $params, "", $sortfield, $sortorder);
                    ?>
                    <th class="liste_titre">Vente : commandes</th>
                    <th class="liste_titre">Vente : factures</th>
                    <th class="liste_titre">Achat : réalisé</th>
                    <th class="liste_titre">Achat : engagé</th>
                    <?php if($conf->ndfp->enabled){ ?><th class="liste_titre">Total Note de frais (€)</th><?php } ?>
                    <th class="liste_titre">Total temps passé (JH)</th>
                    <th class="liste_titre">Total coût MO (€)</th>
                    <th class="liste_titre">Rentabilité</th>
                </tr>
            </thead>
            <tbody>
                <?php

                foreach($TRapport as $line){
                    $project=new Project($db);
                    $project->fetch($line['IdProject']);

                    $date_debut = empty($line['date_debut']) ? '' : date('d/m/Y', strtotime($line['date_debut']));
                    $date_fin = empty($line['date_fin']) ? '' : date('d/m/Y', strtotime($line['date_fin']));

                    ?>
                    <tr>
                        <td><?php echo $project->getNomUrl(1,'',1)  ?></td>
                        <td><?php echo $date_debut;  ?></td>
                        <td><?php echo $date_fin; ?></td>
                        <td nowrap="nowrap"><?php echo price(round($line['total_cmd'],2)) ?></td>
                        <td nowrap="nowrap"><?php echo price(round($line['total_fac'],2)) ?></td>
                        <td nowrap="nowrap"><?php echo price(round($line['total_fac_fourn'],2)) ?></td>
                        <td nowrap="nowrap"><?php echo price(round($line['total_cmd_fourn_no_fac'],2)) ?></td>
                        <?php if($conf->ndfp->enabled){ ?><td nowrap="nowrap"><?php echo price(round($line['total_ndf'],2)) ?></td><?php } ?>
                        <td nowrap="nowrap"><?php echo convertSecondToTime($line['total_temps'],'all',$conf->global->DOC2PROJECT_NB_HOURS_PER_DAY*60*60) ?></td>
                        <td nowrap="nowrap"><?php echo price(round($line['total_cout_homme'],2)) ?></td>
                        <td<?php echo ($line['marge'] < 0) ? ' style="color:red;font-weight: bold" ' : ' style="color:green" ' ?> nowrap="nowrap"><?php echo price(round($line['marge'],2)) ?></td>
                    </tr>
                    <?php
                    $total_cmd += $line['total_cmd'];
                    $total_fac+= $line['total_fac'];
                    if($conf->ndfp->enabled)$total_ndf += $line['total_ndf'];
                    $total_temps += $line['total_temps'];
                    $total_cout_homme += $line['total_cout_homme'];
                    $total_marge += $line['marge'];
                }
                ?>
            </tbody>
            <tfoot>

                <tr style="font-weight: bold;">
                    <td>Totaux</td>
                    <td></td>
                    <td></td>
                    <td><?php echo price($total_cmd) ?></td>
                    <td><?php echo price($total_fac) ?></td>
                    <?php if($conf->ndfp->enabled){ ?><td><?php echo price($total_ndf) ?></td><?php } ?>
                    <td><?php echo convertSecondToTime($total_temps,'all',$conf->global->DOC2PROJECT_NB_HOURS_PER_DAY*60*60) ?></td>
                    <td><?php echo price($total_cout_homme) ?></td>
                    <td<?php echo ($total_marge < 0) ? ' style="color:red" ' : ' style="color:green" ' ?>><?php echo price($total_marge) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}

if(!function_exists('_print_filtre_plage_date')) { // Ces fonctions existent aussi dans doc2project
	function _print_filtre_plage_date(&$form){
		?>
			<tr>
				<td>Date de début : </td>
				<td><?php echo $form->calendrier('', 'date_deb', ($_REQUEST['date_deb'])? $_REQUEST['date_deb'] : ''); ?></td>
			</tr>
			<tr>
				<td>Date de fin : </td>
				<td><?php echo $form->calendrier('', 'date_fin', ($_REQUEST['date_fin'])? $_REQUEST['date_fin'] : ''); ?></td>
			</tr>
		<?php
	}
}
if(!function_exists('_print_filtre_liste_projet')) {
	function _print_filtre_liste_projet(&$form,&$PDOdb) {
		global $db;
		dol_include_once('/core/class/html.formprojet.class.php');
		$formproject = new FormProjets($db);
		
		?>
			<tr>
				<td>Projet : </td>
				<td><?php $formproject->select_projects(-1, $_REQUEST['id_projet'], 'id_projet', 0); ?></td>
			</tr>
		<?php
	}
}