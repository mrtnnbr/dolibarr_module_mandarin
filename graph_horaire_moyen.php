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
	
	$prefix_n = 'horaire moyen';
	$prefix_n_1 = 'horaire moyen';
	
	// Formatage du tableau de base
	for ($i=1; $i<=53; $i++) 
	{
		$TData[$i] = array('week' => $i, $prefix_n.$year_n_1 => 0, $prefix_n_1.$year_n => 0);
		if (!empty($conf->global->MANDARIN_HORAIRE_MOYEN_NOMAL)) $TData[$i]['horaire normal'] = $conf->global->MANDARIN_HORAIRE_MOYEN_NOMAL;
	}
	
	// N-1
	$sql_n_1 = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600) / COUNT(DISTINCT ppt.fk_user)) AS horaire_moyen
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
			$TData[$line->week][$prefix_n_1.$year_n_1] = $line->horaire_moyen;
		}
	}
	// FIN N-1
	
	// N
	$sql_n = 'SELECT WEEKOFYEAR(ppt.task_date) AS `week`, ((SUM(ppt.task_duration) / 3600) / COUNT(DISTINCT ppt.fk_user)) AS horaire_moyen
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
			$TData[$line->week][$prefix_n.$year_n] = $line->horaire_moyen;
		}
	}
	// FIN N

	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphHoraireMoyen'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
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
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();