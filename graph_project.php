<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->ca_horaire) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$fk_statut = GETPOST('fk_statut', 'int');
	
	$sql = 'SELECT p.rowid, p.title, pt.rowid AS fk_task
			, pt.planned_workload AS temps_prevu
			, pt.planned_workload * (pt.progress / 100) AS temps_theorique
			, SUM(ptt.task_duration) AS temps_reel
			FROM llx_projet p
			LEFT JOIN llx_projet_task pt ON (p.rowid = pt.fk_projet)
			LEFT JOIN llx_projet_task_time ptt ON (pt.rowid = ptt.fk_task)
			WHERE p.entity = 1';

	if (!empty($fk_statut)) $sql .= ' AND p.fk_statut = '.$fk_statut;
	$sql .= ' GROUP BY pt.rowid ORDER BY p.title';

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			
			if (!isset($TData[$line->rowid]))
			{
				$TData[$line->rowid] = array(
					'name'=>dol_escape_js($line->title)
					,'temps_prevu' => $line->temps_prevu
					,'Progression réel'=>$line->temps_reel
					,'Progression théorique'=> $line->temps_theorique
				);
			}
			else
			{
				$TData[$line->rowid]['temps_prevu'] += $line->temps_prevu;
				$TData[$line->rowid]['Progression réel'] += $line->temps_reel;
				$TData[$line->rowid]['Progression théorique'] += $line->temps_theorique;
			}
		}

		foreach ($TData as &$Tab)
		{
			$temps_prevu = $Tab['temps_prevu'];
			unset($Tab['temps_prevu']);
			
			if (empty($temps_prevu)) unset($Tab);
			
			$Tab['Progression réel'] = ($Tab['Progression réel'] * 100) / $temps_prevu;
			$Tab['Progression théorique'] = ($Tab['Progression théorique'] * 100) / $temps_prevu;
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphProjet'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphProject');
	print $listeview->renderArray($PDOdb, $TData
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