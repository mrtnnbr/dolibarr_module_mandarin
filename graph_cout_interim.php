<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->cout_interim) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$year_n_1 = GETPOST('year_n_1', 'int');
	if (empty($year_n_1)) $year_n_1 = date('Y')-1;
	$year_n = GETPOST('year_n', 'int');
	if (empty($year_n)) $year_n = date('Y');
	
	// Formatage du tableau de base
	for ($i=1; $i<=53; $i++) 
	{
		$TData[$i] = array('week' => $i, $year_n_1 => 0, $year_n => 0);
		if (!empty($conf->global->MANDARIN_OBJECTIF_COUT_INTERIM)) $TData[$i]['objectif'] = $conf->global->MANDARIN_OBJECTIF_COUT_INTERIM;
	}
	
	$sql_n_1 = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, (ue.thm * (SUM(ppt.task_duration) / 3600)) AS total_thm
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n_1.'
				AND ue.type_contrat = "interim"
				GROUP BY `week` 
				ORDER BY `week` ASC';

	$resql = $db->query($sql_n_1);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week][$year_n_1] = $line->total_thm;
		}
	}
	
	
	$sql_n = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, (ue.thm * (SUM(ppt.task_duration) / 3600)) AS total_thm
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n.'
				AND ue.type_contrat = "interim"
				GROUP BY `week` 
				ORDER BY `week` ASC';
			
	$resql = $db->query($sql_n);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week][$year_n] = $line->total_thm;
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphInterim'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphInterim');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphInterim')
			)
			,'title'=>array(
				'year' => $langs->transnoentitiesnoconv('Year')
				,'week' => $langs->transnoentitiesnoconv('Week')
			)
			,'xaxis'=>'week'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphInterim'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphInterim'))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();