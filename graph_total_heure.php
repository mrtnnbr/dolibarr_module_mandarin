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
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n_1.'
				AND ue.type_contrat = "cdi"
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
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n.'
				AND ue.type_contrat = "cdi"
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
	

	$sql_cdi_n = 'SELECT u.datec, ue.dda, ue.horaire
				FROM llx_user u 
				INNER JOIN llx_user_extrafields ue ON (u.rowid = ue.fk_object)
				WHERE ue.type_contrat = "cdi"
				AND ue.horaire IS NOT NULL';
			
	$resql = $db->query($sql_cdi_n);
	$Tab = array();
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$time_datec = strtotime($line->datec);
			$year_datec = date('Y', $time_datec); 
			if ($year_datec < $year_n) $week_start = 1;
			else $week_start = date('W', $time_datec);
			
			if (empty($line->dda)) $week_end = 53;
			else {
				$time_dda = strtotime($line->dda);
				$year_dda = date('Y', $time_dda);
				if ($year_dda < $year_n) continue;
				else $week_end = date('W', $time_dda);
			}
			
			$Tab[] = array(
				'week_start' => $week_start
				,'week_end' => $week_end
				,'horaire' => $line->horaire
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
		)
	);
	
	// End of page
	llxFooter();