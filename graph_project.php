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
		foreach ($TData as $k=>&$Tab)
		{
			$temps_prevu = $Tab['temps_prevu'];
			//unset($Tab['temps_prevu']);
			
			if (empty($temps_prevu)){
				continue;	
			}
			
			$TDataTransform[] = array(
				'name'=>dol_escape_js($Tab['name'])
				
				,'Progression réelle' => $temps_prevu>0 ? round($Tab['Progression réelle'] * 100 / $temps_prevu) : 100
				,'Progression théorique' => $temps_prevu > 0 ? round($Tab['Progression théorique'] * 100 / $temps_prevu) : 100
			);
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