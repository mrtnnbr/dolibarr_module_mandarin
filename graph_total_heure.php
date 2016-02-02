<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->total_heure) accessforbidden();
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
		$TData[$i] = array('week' => $i, $year_n_1 => 0, $year_n => 0, 'Dispo CDI' => 0);
	}
	
	$sql_n_1 = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600)) AS total_heure
				FROM '.MAIN_DB_PREFIX.'projet_task_time ppt
				INNER JOIN '.MAIN_DB_PREFIX.'user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n_1.'
				AND ue.contrat = "cdi"
				GROUP BY `week` 
				ORDER BY `week` ASC';

	$resql = $db->query($sql_n_1);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week][$year_n_1] = $line->total_heure;
		}
	}
	
	
	$sql_n = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600)) AS total_heure
				FROM '.MAIN_DB_PREFIX.'projet_task_time ppt
				INNER JOIN '.MAIN_DB_PREFIX.'user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n.'
				AND ue.contrat = "cdi"
				GROUP BY `week` 
				ORDER BY `week` ASC';
			
	$resql = $db->query($sql_n);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week][$year_n] = $line->total_heure;
		}
	}
	

	$sql_cdi_n = 'SELECT ue.dda, ue.dds, u.weeklyhours
				FROM '.MAIN_DB_PREFIX.'user u 
				INNER JOIN '.MAIN_DB_PREFIX.'user_extrafields ue ON (u.rowid = ue.fk_object)
				WHERE ue.contrat = "cdi"
				AND u.weeklyhours IS NOT NULL';
			
	$resql = $db->query($sql_cdi_n);
	$Tab = array();
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$time_date_entree = strtotime($line->dda);
			$year_date_entree = date('Y', $time_date_entree); 
			if ($year_date_entree < $year_n) $week_start = 1;
			else $week_start = date('W', $time_date_entree);
			
			if (empty($line->dds)) $week_end = 53;
			else {
				$time_dds = strtotime($line->dds);
				$year_dds = date('Y', $time_dds);
				if ($year_dds < $year_n) continue;
				else $week_end = date('W', $time_dds);
			}
			
			$Tab[] = array(
				'week_start' => $week_start
				,'week_end' => $week_end
				,'horaire' => $line->weeklyhours
			);
		}
	}

	if (count($Tab) > 0)
	{
		foreach ($Tab as &$TInfo)
		{
			for ($i=$TInfo['week_start']; $i <= $TInfo['week_end']; $i++)
			{
				$TData[$i]['Dispo CDI'] += $TInfo['horaire']; // Somme des horaires CDI dispo
			}
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphTotalHeure'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphTotalHeure');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphTotalHeure')
			)
			,'title'=>array(
				'year' => $langs->transnoentitiesnoconv('Year')
				,'week' => $langs->transnoentitiesnoconv('Week')
			)
			,'xaxis'=>'week'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphTotalHeure'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphTotalHeure'))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();