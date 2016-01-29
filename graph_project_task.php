<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/core/lib/project.lib.php');
	
	if (!$user->rights->mandarin->graph->ca_horaire) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$progress_min = GETPOST('progress_min', 'int');
	$progress_max = GETPOST('progress_max', 'int');
	$id = GETPOST('id', 'int');
	$object = new Project($db);
	
	if ($id > 0) $object->fetch($id);
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$sql = 'SELECT pt.rowid, pt.label, pt.ref
				, SUM(pt.planned_workload) AS temps_prevu
				, SUM(pt.planned_workload * (pt.progress / 100)) AS temps_theorique
				, SUM(ptt.task_duration) AS temps_reel
				FROM '.MAIN_DB_PREFIX.'projet_task pt
				LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_time ptt ON (pt.rowid = ptt.fk_task)
				WHERE pt.entity = '.$conf->entity.'
				#AND pt.rowid = 1546 
				AND pt.fk_projet = '.$id;
				
	if (!empty($progress_min)) $sql .= ' AND pt.progress >= '.$progress_min;
	if (!empty($progress_max)) $sql .= ' AND pt.progress >= '.$progress_max;
	$sql .= ' GROUP BY pt.rowid ORDER BY pt.progress';

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			
			if (empty($line->temps_prevu)) continue;
			
			$temps_prevu = (!empty($line->temps_prevu) ? $line->temps_prevu : 1);
			$progress_reelle = (100 * $line->temps_reel) / $temps_prevu;
			$progress_theorique = (100 * $line->temps_theorique) / $temps_prevu;
			
			$TData[] = array(
				'name' => dol_escape_js($line->label).' ('.$line->ref.')'
				,'Progression réelle' => $progress_reelle
				,'Progression theorique' => $progress_theorique
			);
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphProjet'), '');
	
	$head=project_prepare_head($object);
    dol_fiche_head($head, 'mandarin_rapport', $langs->trans("mandarinProjectTask"),0,($object->public?'projectpub':'project'));
	
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphProjectTask');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'chartType' => 'ColumnChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphProjectTask')
			)
			,'xaxis'=>'name'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphProjectTask'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphProjectTask'))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();