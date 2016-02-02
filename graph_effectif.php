<?php
	require('config.php');
	
	if (!$user->rights->mandarin->graph->effectif) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TData = array();
	
	$year_n_1 = GETPOST('year_n_1', 'int');
	if (empty($year_n_1)) $year_n_1 = date('Y')-1;
	$year_n = GETPOST('year_n', 'int');
	if (empty($year_n)) $year_n = date('Y');
	
	// Formatage du tableau de base
	for ($i=1; $i<=53; $i++) $TData[$i] = array('week' => $i, 'permanents'.$year_n_1 => 0, 'effectif'.$year_n_1 => 0, 'permanents'.$year_n => 0, 'effectif'.$year_n => 0);
	
	$sql_effectif = 'SELECT ue.dda, ue.dds, ue.contrat AS type_contrat
					FROM llx_user u 
					INNER JOIN llx_user_extrafields ue ON (u.rowid = ue.fk_object)
					WHERE ue.dda IS NOT NULL';
	
	$resql = $db->query($sql_effectif);
	$Tab = array();
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$time_date_entree = strtotime($line->dda);
			$year_date_entree = date('Y', $time_date_entree); 
			
			$Tab[] = array(
				'time_date_entree' => $time_date_entree
				,'year_date_entree' => $year_date_entree
				,'dda' => $line->dda
				,'dds' => $line->dds
				,'type_contrat' => $line->type_contrat
			);
		}
		
		foreach ($Tab as &$TInfo)
		{
			$skip_n = $skip_n_1 = false;
			
			// N-1
			if ($TInfo['year_date_entree'] < $year_n_1) $week_start_n_1 = 1;
			elseif ($TInfo['year_date_entree'] == $year_n_1) $week_start_n_1 = date('W', $TInfo['time_date_entree']);
			else $skip_n_1 = true;

			if (empty($TInfo['dds'])) $week_end_n_1 = 53;
			else 
			{
				$time_dds = strtotime($TInfo['dds']);
				$year_dds = date('Y', $time_dds);
				if ($year_dds < $year_n_1) $skip_n_1 = true;
				elseif ($year_dds == $year_n_1) $week_end_n_1 = date('W', $time_dds);
				else $week_end_n_1 = 53;
			}


			// N
			if ($TInfo['year_date_entree'] < $year_n) $week_start_n = 1;
			else $week_start_n = date('W', $TInfo['time_date_entree']);
			
			if (empty($TInfo['dds'])) $week_end_n = 53;
			else 
			{
				$time_dds = strtotime($TInfo['dds']);
				$year_dds = date('Y', $time_dds);
				if ($year_dds < $year_n) $skip_n = true;
				elseif ($year_dds == $year_n) $week_end_n = date('W', $time_dds);
				else $week_end_n = 53;
			}
		
			if (!$skip_n_1)
			{
				for ($i = (int) $week_start_n_1; $i <= (int) $week_end_n_1; $i++)
				{
					$TData[$i]['effectif'.$year_n_1] += 1;
					if ($TInfo['type_contrat'] != 'interim') $TData[$i]['permanents'.$year_n_1] += 1;
				}
			}
			
			if (!$skip_n)
			{
				for ($i = (int) $week_start_n; $i <= (int) $week_end_n; $i++)
				{
					$TData[$i]['effectif'.$year_n] += 1;
					if ($TInfo['type_contrat'] != 'interim') $TData[$i]['permanents'.$year_n] += 1;
				}
			}
			
		}
	}

	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphEffectif'), '');
	
	$explorer = new stdClass();
	$explorer->actions = array("dragToZoom", "rightClickToReset");
	
	$listeview = new TListviewTBS('graphCACumule');
	print $listeview->renderArray($PDOdb, $TData
		,array(
			'type' => 'chart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphEffectif')
			)
			,'title'=>array(
				'year' => $langs->transnoentitiesnoconv('Year')
				,'week' => $langs->transnoentitiesnoconv('Week')
			)
			,'xaxis'=>'week'
			,'hAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleHAxisGraphEffectif'))
			,'vAxis'=>array('title'=>$langs->transnoentitiesnoconv('subTitleVAxisGraphEffectif'))
			,'explorer'=>$explorer
		)
	);
	
	// End of page
	llxFooter();