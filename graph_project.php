<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->ca_horaire) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$fk_statut = GETPOST('fk_statut', 'int');
	
	$sql = 'SELECT p.rowid, p.title, p.dateo AS date_deb, p.datee AS date_fin
				, pt.planned_workload AS temps_prevu
				, pt.planned_workload * (pt.progress / 100) AS temps_theorique
				, ptt.task_duration AS temps_reel
				FROM '.MAIN_DB_PREFIX.'projet p
				LEFT JOIN '.MAIN_DB_PREFIX.'projet_task pt ON (p.rowid = pt.fk_projet)
				LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_time ptt ON (pt.rowid = ptt.fk_task)
				WHERE p.entity = '.$conf->entity;

	if (!empty($fk_statut)) $sql .= ' AND p.fk_statut = '.$fk_statut;
	$sql .= ' GROUP BY pt.rowid ORDER BY p.title';

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$temps_reelle = $line->temps_reel / 3600;
			$temps_theorique = $line->temps_theorique / 3600;
			
			if (!isset($TData[$line->rowid]))
			{
				$TData[$line->rowid] = array(
					'name'=>dol_sanitizeFileName($line->title)
					,'Temps réelle'=>$temps_reelle
					,'Temps théorique'=>$temps_theorique
				);
			}
			else
			{
				$TData[$line->rowid]['Temps réelle'] += $temps_reelle;
				$TData[$line->rowid]['Temps théorique'] += $temps_theorique;
			}
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphProjet'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphCACumule');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'chartType' => 'ColumnChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphProject')
			)
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphProject'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphProject', $rapport_ca_ht))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();