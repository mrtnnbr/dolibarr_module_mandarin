<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->ca_cumule_horaire) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	$rapport_ca_ht = GETPOST('rapport_ca_ht', 'int');
	if (empty($rapport_ca_ht)) $rapport_ca_ht = 1;
	
	$year_n_1 = GETPOST('year_n_1', 'int');
	if (empty($year_n_1)) $year_n_1 = date('Y')-1;
	$year_n = GETPOST('year_n', 'int');
	if (empty($year_n)) $year_n = date('Y');
	
	// Formatage du tableau de base
	for ($i=1; $i<=53; $i++) $TData[$i] = array('week' => $i, 'CA'.$year_n_1 => 0, 'heures'.$year_n_1 => 0, 'CA'.$year_n => 0, 'heures'.$year_n => 0);
	
	// ANNEE N-1
	$sql_n_1_CA = 'SELECT WEEKOFYEAR(date_valid) AS `week`, sum(total) as total_ht
					FROM llx_facture
					WHERE YEAR(date_valid) = '.$year_n_1.'
					GROUP BY `week` 
					ORDER BY `week` ASC';
		
	$sql_n_1_heures = 'SELECT WEEKOFYEAR(task_date) AS `week`, SUM(task_duration) AS total_time
					FROM llx_projet_task_time
					WHERE YEAR(task_date) = '.$year_n_1.'
					GROUP BY `week` 
					ORDER BY `week` ASC';
	
	$resql = $db->query($sql_n_1_CA);
	if ($resql)
	{
		$cumulCA = 0;
		while ($line = $db->fetch_object($resql))
		{
			$cumulCA += $line->total_ht / $rapport_ca_ht;
			$TData[$line->week]['CA'.$year_n_1] = $cumulCA;
		}
	}

	$resql = $db->query($sql_n_1_heures);
	if ($resql)
	{
		$cumulsecondes = 0;
		while ($line = $db->fetch_object($resql))
		{
			$nb_heures = $line->total_time / 3600;
			$TData[$line->week]['heures'.$year_n_1] = $nb_heures;
			
			// Calcul du CA / heures
			$TData[$line->week]['CA'.$year_n_1] = $TData[$line->week]['CA'.$year_n_1] / $nb_heures;
		}
	}
	// FIN N-1
	
	// ANNEE N
	$sql_n_CA = 'SELECT WEEKOFYEAR(date_valid) AS `week`, sum(total) as total_ht
					FROM llx_facture
					WHERE YEAR(date_valid) = '.$year_n.'
					GROUP BY `week` 
					ORDER BY `week` ASC';
		
	$sql_n_heures = 'SELECT WEEKOFYEAR(task_date) AS `week`, SUM(task_duration) AS total_time
					FROM llx_projet_task_time
					WHERE YEAR(task_date) = '.$year_n.'
					GROUP BY `week` 
					ORDER BY `week` ASC';
	
	$resql = $db->query($sql_n_CA);
	if ($resql)
	{
		$cumulCA = 0;
		while ($line = $db->fetch_object($resql))
		{
			$cumulCA += $line->total_ht / $rapport_ca_ht;
			$TData[$line->week]['CA'.$year_n] = $cumulCA;
		}
	}
	
	$resql = $db->query($sql_n_heures);
	if ($resql)
	{
		$cumulsecondes = 0;
		while ($line = $db->fetch_object($resql))
		{
			$nb_heures = $line->total_time / 3600;
			$TData[$line->week]['heures'.$year_n] = $nb_heures;
			
			// Calcul du CA / heures
			$TData[$line->week]['CA'.$year_n] = $TData[$line->week]['CA'.$year_n] / $nb_heures;
		}
	}
	// FIN N
	
	for ($i =1; $i < count($TData); $i++)
	{
		//if ($TData[$i]['CA'.$year_n_1] == 0 && isset($TData[$i-1]['CA'.$year_n_1])) $TData[$i]['CA'.$year_n_1] = $TData[$i-1]['CA'.$year_n_1];
		//if ($TData[$i]['CA'.$year_n] == 0 && isset($TData[$i-1]['CA'.$year_n])) $TData[$i]['CA'.$year_n] = $TData[$i-1]['CA'.$year_n];
		//if ($TData[$i]['heures'.$year_n_1] == 0 && isset($TData[$i-1]['heures'.$year_n_1])) $TData[$i]['heures'.$year_n_1] = $TData[$i-1]['heures'.$year_n_1];
		//if ($TData[$i]['heures'.$year_n] == 0 && isset($TData[$i-1]['heures'.$year_n])) $TData[$i]['heures'.$year_n] = $TData[$i-1]['heures'.$year_n];
	}
	
	//var_dump($TData);
	
	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphCACumuleHoraire'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphCACumule');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphCACumuleHoraire')
			)
			,'title'=>array(
				'year' => 'Année'
				,'week' => 'Semaine'
			)
			,'xaxis'=>'week'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphCACumuleHoraire'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphCACumuleHoraire', $rapport_ca_ht))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();