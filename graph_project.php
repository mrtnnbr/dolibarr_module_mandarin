<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->project) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$fk_statut = GETPOST('fk_statut', 'int');
	
	$sql = 'SELECT p.rowid, p.title, pt.rowid AS fk_task
			, (SELECT SUM(task_duration) 
				FROM '.MAIN_DB_PREFIX.'projet_task_time ptt
				LEFT JOIN '.MAIN_DB_PREFIX.'projet_task t ON (t.rowid = ptt.fk_task)
				WHERE t.fk_projet = p.rowid) AS temps_reel
			, SUM(pt.planned_workload) AS temps_prevu
			, SUM(pt.planned_workload * (pt.progress / 100)) AS temps_theorique
			FROM '.MAIN_DB_PREFIX.'projet p
			LEFT JOIN '.MAIN_DB_PREFIX.'projet_task pt ON (p.rowid = pt.fk_projet)
			WHERE p.entity = '.$conf->entity;

	if (!empty($fk_statut)) $sql .= ' AND p.fk_statut = '.$fk_statut;
	$sql .= ' GROUP BY p.rowid 
	ORDER BY p.title';

	$resql = $db->query($sql);
	
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			
			$TData[$line->rowid] = array(
					'name'=>$line->title
					,'temps_prevu' => $line->temps_prevu
					,'Progression réelle'=>$line->temps_reel
					,'Progression théorique'=> $line->temps_theorique
				);
			
		}

		$TDataTransform = array();
		foreach ($TData as $fk_project=>&$Tab)
		{
			$temps_prevu = $Tab['temps_prevu'];
			//unset($Tab['temps_prevu']);
			
			if (empty($temps_prevu)){
				continue;	
			}
			
			$TDataTransform[$fk_project] = array(
				'name'=>dol_escape_js($Tab['name'])
				
				,'Progression réelle' => $temps_prevu>0 ? round($Tab['Progression réelle'] * 100 / $temps_prevu) : 100
				,'Progression théorique' => $temps_prevu > 0 ? round($Tab['Progression théorique'] * 100 / $temps_prevu) : 100
			);
			
			$TDataTransform[$fk_project]['Achat'] = _completeAchat($PDOdb, $fk_project);
		}
	}
	//var_dump($TDataTransform);
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphProjet'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphProject');
	print $listeview->renderArray($PDOdb, $TDataTransform
		,array(
			'type' => 'chart'
			,'chartType' => 'ColumnChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphProject')
			)
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphProject'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphProject'))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();
	
function _completeAchat(&$PDOdb,  $fk_project) {
	//TODO comment calculer les achats théorique / réel ? 
	
	// méthode 1 PA propal / PA Commande Founisseur ?
	//TODO methode en conf

	$PDOdb->Execute("SELECT SUM(d.buy_price_ht * d.qty) as total_achat_prevu
		FROM ".MAIN_DB_PREFIX."commandedet d
		LEFT JOIN ".MAIN_DB_PREFIX."commande p ON (p.rowid=d.fk_commande) 
		WHERE p.fk_projet=".$fk_project);
	$obj = $PDOdb->Get_line();
	$total_achat_prevu = $obj->total_achat_prevu;
	
	if(empty($total_achat_prevu)) {
		$PDOdb->Execute("SELECT SUM(d.buy_price_ht * d.qty) as total_achat_prevu
		FROM ".MAIN_DB_PREFIX."propaldet d
		LEFT JOIN ".MAIN_DB_PREFIX."propal p ON (p.rowid=d.fk_propal) 
		WHERE p.fk_projet=".$fk_project);
		$obj = $PDOdb->Get_line();
		$total_achat_prevu = $obj->total_achat_prevu;
	}

	$PDOdb->Execute("SELECT SUM(d.total_ht) as total_achat_effectue
		FROM ".MAIN_DB_PREFIX."commande_fournisseurdet d
		LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur p ON (p.rowid=d.fk_commande) 
		WHERE p.fk_projet=".$fk_project);
	$obj = $PDOdb->Get_line();
		
	$total_achat_effectue = $obj->total_achat_effectue;
	if(empty($total_achat_effectue)) {
		
		$PDOdb->Execute("SELECT SUM(d.total_ht) as total_achat_effectue
			FROM ".MAIN_DB_PREFIX."facture_fourn_det d
			LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn p ON (p.rowid=d.fk_facture_fourn) 
			WHERE p.fk_projet=".$fk_project);
		$obj = $PDOdb->Get_line();
		$total_achat_effectue = $obj->total_achat_effectue;
	} 
	//var_dump($total_achat_effectue , $total_achat_prevu ,round($total_achat_effectue / $total_achat_prevu * 100));
	return $total_achat_prevu>0 ? round($total_achat_effectue / $total_achat_prevu * 100) : 100; 
	
	
	return 0;
	
}
