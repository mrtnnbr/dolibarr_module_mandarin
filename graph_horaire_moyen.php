<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->horaire_moyen) accessforbidden();
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
		$TData[$i] = array('week' => $i, 'horaire normal'.$year_n_1 => 0, 'horaire normal'.$year_n => 0, 'Dispo CDI' => 0);
	}
	
	$sql_n_1 = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600) / COUNT(DISTINCT ue.fk_object) ) AS total_moyen
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n_1.'
				AND ue.contrat = "cdi"
				GROUP BY `week` 
				ORDER BY `week` ASC';

	$resql = $db->query($sql_n_1);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week]['horaire normal'.$year_n_1] = $line->total_moyen;
		}
	}
	
	
	$sql_n = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600) / COUNT(DISTINCT ue.fk_object) ) AS total_moyen
				FROM llx_projet_task_time ppt
				INNER JOIN llx_user_extrafields ue ON (ppt.fk_user = ue.fk_object)
				WHERE YEAR(ppt.task_date) = '.$year_n.'
				AND ue.contrat = "cdi"
				GROUP BY `week` 
				ORDER BY `week` ASC';
			
	$resql = $db->query($sql_n);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TData[$line->week]['horaire normal'.$year_n] = $line->total_moyen;
		}
	}
	

	$sql_cdi_n = 'SELECT ue.fk_object, ue.dda, ue.dds, u.weeklyhours
				FROM llx_user u 
				INNER JOIN llx_user_extrafields ue ON (u.rowid = ue.fk_object)
				WHERE ue.contrat = "cdi"
				AND u.weeklyhours IS NOT NULL
				AND ue.dda IS NOT NULL';
			
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
				,'fk_user' => $line->fk_object
			);
		}
	}

	if (count($Tab) > 0)
	{
		$NbUserPerWeek = array();
		foreach ($Tab as &$TInfo)
		{
			for ($i=$TInfo['week_start']; $i <= $TInfo['week_end']; $i++) $NbUserPerWeek[$i][$TInfo['fk_user']] = 1;
		}
		
		foreach ($Tab as &$TInfo)
		{
			for ($i=$TInfo['week_start']; $i <= $TInfo['week_end']; $i++)
			{
				$coef = 1;
				if (count($NbUserPerWeek[$i]) > 0) $coef = count($NbUserPerWeek[$i]);
				$TData[$i]['Dispo CDI'] += $TInfo['horaire'] / $coef; // Somme des horaires CDI dispo
			}
		}
	}
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphHoraireMoyen'), '');
	
	$listeview = new TListviewTBS('graphHoraireMoyen');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphHoraireMoyen')
			)
			,'title'=>array(
				'year' => $langs->transnoentitiesnoconv('Year')
				,'week' => $langs->transnoentitiesnoconv('Week')
			)
			,'xaxis'=>'week'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphHoraireMoyen'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphHoraireMoyen'))
		)
	);
	
	// End of page
	llxFooter();