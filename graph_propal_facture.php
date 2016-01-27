<?php
	require('config.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/core/lib/functions.lib.php');
	
	if (!$user->rights->mandarin->graph->propal_facture) accessforbidden();
	$langs->load('mandarin@mandarin');
	
	$PDOdb = new TPDOdb;
	$TDataPropal = array();
	
	$year_n = GETPOST('year_n', 'int');
	if (empty($year_n)) $year_n = date('Y');
	
	// Info sur année N-2, N-1 et N par Commercial (user)
	for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
	{
		$sql = 'SELECT u.firstname, u.lastname, p.rowid AS fk_propal, p.fk_statut, p.fk_soc, s.nom AS name
				FROM '.MAIN_DB_PREFIX.'user u
				INNER JOIN '.MAIN_DB_PREFIX.'propal p ON (u.rowid = p.fk_user_author)
				INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (p.fk_soc = s.rowid)
				WHERE YEAR(p.date_cloture) = '.$current_year;
				
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($line = $db->fetch_object($resql))
			{
				$index = $line->firstname.' '.$line->lastname;
				$TDataPropal[$index]['nbPropal'][$current_year]++;
				
				if ($line->fk_statut == Propal::STATUS_NOTSIGNED)
				{
					$TDataPropal[$index]['nbPropalNotSigned'][$current_year]++;
				}
				
				$TDataPropal[$index]['client'][$current_year][$line->name]++;
			}
		}
	}

	foreach ($TDataPropal as $commercial => &$TStat)
	{
		// Calcul du %tage de non signé
		foreach ($TStat['nbPropal'] as $year => $nb)
		{
			if (!isset($TStat['nbPropalNotSigned'])) $TStat['nbPropalNotSignedPercent'][$year] = 0;
			else 
			{
				$nbNotSign = $TStat['nbPropalNotSigned'][$year];
				if ($nbNotSign > 0) $TStat['nbPropalNotSignedPercent'][$year] = (100 * $nbNotSign) / $nb;
				else $TStat['nbPropalNotSignedPercent'][$year] = 0;
			}
		}
		
		foreach ($TStat['client'] as $year => $Tab)
		{
			$topClientName = '';
			$topClientCounter = 0;
			foreach ($Tab as $name => $nb_propal_assoc)
			{
				// Pas de test si cas d'égalité, je garde le 1er 
				if ($nb_propal_assoc > $topClientCounter)
				{
					$topClientName = $name;
					$topClientCounter = $nb_propal_assoc;
				}
			}
			
			$percent = 0;
			$percent = (100 * $topClientCounter) / $TStat['nbPropal'][$year];
			
			$TStat['topClient'][$year] = array('name' => $topClientName, 'percent_presence' => $percent);
		}
	}

	
	$TDataFacture = array();
	$TTotalFacture = array();
	
	// Info sur année N-2, N-1 et N par Commercial (user)
	for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
	{
		$sql = 'SELECT u.firstname, u.lastname, f.rowid AS fk_facture, f.fk_soc, f.total AS total_ht, s.nom AS name
				FROM '.MAIN_DB_PREFIX.'user u
				INNER JOIN '.MAIN_DB_PREFIX.'facture f ON (u.rowid = f.fk_user_author)
				INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (f.fk_soc = s.rowid)
				WHERE YEAR(f.datef) = '.$current_year.'
				AND f.date_valid IS NOT NULL
				AND f.fk_statut = '.Facture::STATUS_VALIDATED;
				
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($line = $db->fetch_object($resql))
			{
				$index = $line->firstname.' '.$line->lastname;
				$TDataFacture[$index]['CA'][$current_year] += $line->total_ht;
				$TDataFacture[$index]['client'][$current_year][$line->name] += $line->total_ht;
				$TTotalFacture['total_ca'][$current_year] += $line->total_ht; 
			}
			
			foreach ($TDataFacture as $commercial => &$TStat)
			{
				foreach ($TStat['CA'] as $year => $total)
				{
					$percent = (100 * $total) / $TTotalFacture['total_ca'][$year];
					$TStat['CAPercent'][$year] = $percent;
					//if ($year == 2014) var_dump($total, $TTotalFacture['total_ca'][$current_year], $percent);
				}
				
				foreach ($TStat['client'] as $year => $Tab)
				{
					$topClientName = '';
					$topClientTotalCA = 0;
					foreach ($Tab as $name => $totalCA)
					{
						// Pas de test si cas d'égalité, je garde le 1er 
						if ($totalCA > $topClientTotalCA)
						{
							$topClientName = $name;
							$topClientTotalCA = $totalCA;
						}
					}
					
					$percent = 0;
					$percent = (100 * $topClientTotalCA) / $TStat['CA'][$year];
					
					$TStat['topClient'][$year] = array('name' => $topClientName, 'percent_ca' => $percent);
				}
			}
		}
	}
	

	// Begin of page
	llxHeader('', $langs->trans('mandarinTitleGraphHoraireMoyen'), '');
	
	print_fiche_titre($langs->trans("mandarinPropalsPerCommercial"),'','title_commercial.png');
	
	// Print table info propals
	print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureCommercial').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureNbPropal').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFacturePropalPercentNotSigned').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFacturePropalNbClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N-2
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N-1
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N
		print '</tr>';
		print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td colspan="2">'.($year_n-2).'</td>';
			print '<td colspan="2">'.($year_n-1).'</td>';
			print '<td colspan="2">'.($year_n).'</td>';
		print '</tr>';
		
		if (count($TDataPropal) > 0)
		{
			$var = true;
			$TTotal = array('nbPropal' => array(), 'nbClient' => array());
			foreach ($TDataPropal as $commercial => &$TStat)
			{
				//var_dump($TStat);exit;
				print '<tr '.$bc[$var].'>';
					print '<td>'.$commercial.'</td>';
					
					// Nb Propals
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						$TTotal['nbPropal'][$current_year] += (double) $TStat['nbPropal'][$current_year];
						print '<td>'.(isset($TStat['nbPropal'][$current_year]) ? $TStat['nbPropal'][$current_year] : '-').'</td>'; // Nd propals par année sur 3 colonnes
					}
					
					// Nb Propals not signed
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						print '<td>'.(isset($TStat['nbPropalNotSignedPercent'][$current_year]) ? vatrate(round($TStat['nbPropalNotSignedPercent'][$current_year], 2), 1) : '-').'</td>'; // % propal no signé par année sur 3 colonnes
					}
					
					// Nb Clients
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						$TTotal['nbClient'][$current_year] += count($TStat['client'][$current_year]);
						print '<td>'.(isset($TStat['client'][$current_year]) ? count($TStat['client'][$current_year]) : '-').'</td>'; // Nd clients par année sur 3 colonnes
					}
						
					print '<td>'.(isset($TStat['topClient'][$year_n-2]['name']) ? $TStat['topClient'][$year_n-2]['name'] : '-').'</td>'; // Top client N-2
					print '<td>'.(isset($TStat['topClient'][$year_n-2]['percent_presence']) ? vatrate(round($TStat['topClient'][$year_n-2]['percent_presence'], 2), 1) : '-').'</td>';
					print '<td>'.(isset($TStat['topClient'][$year_n-1]['name']) ? $TStat['topClient'][$year_n-1]['name'] : '-').'</td>'; // Top client N-1
					print '<td>'.(isset($TStat['topClient'][$year_n-1]['percent_presence']) ? vatrate(round($TStat['topClient'][$year_n-1]['percent_presence'], 2), 1) : '-').'</td>';
					print '<td>'.(isset($TStat['topClient'][$year_n]['name']) ? $TStat['topClient'][$year_n]['name'] : '-').'</td>'; // Top client N
					print '<td>'.(isset($TStat['topClient'][$year_n]['percent_presence']) ? vatrate(round($TStat['topClient'][$year_n]['percent_presence'], 2), 1) : '-').'</td>';	
					
				print '</tr>';
				
				$var = !$var;
			}
	
			print '<tr '.$bc[$var].'>';
				print '<td><b>'.$langs->trans('Total').'</b></td>';
				foreach ($TTotal['nbPropal'] as $year => $nb)
				{
					print '<td>'.$nb.'</td>';
				}
				print '<td colspan="3"></td>';
				foreach ($TTotal['nbClient'] as $year => $nb)
				{
					print '<td>'.$nb.'</td>';
				}
				print '<td colspan="6"></td>';
			print '</tr>';
		}
		else
		{
			print '<td colspan="16"><div class="warning">'.$langs->transnoentitiesnoconv('noData').'</div></td>';
		}
	print '</table>';
	// Fin table info propal
	
	print '<p></p>';
	print_fiche_titre($langs->trans("mandarinInvoicesPerCommercial"),'','title_accountancy.png');
	
	// Print table info facture
	print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureCommercial').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureCA').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFacturePropalPercentCA').'</th>';
			print '<th colspan="3">'.$langs->transnoentitiesnoconv('mandarinTitlePropalFacturePropalNbClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N-2
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N-1
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClient').'</th>';
			print '<th>'.$langs->transnoentitiesnoconv('mandarinTitlePropalFactureTopClientPercent').'</th>'; // N
		print '</tr>';
		print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td>'.($year_n-2).'</td>';
			print '<td>'.($year_n-1).'</td>';
			print '<td>'.($year_n).'</td>';
			
			print '<td colspan="2">'.($year_n-2).'</td>';
			print '<td colspan="2">'.($year_n-1).'</td>';
			print '<td colspan="2">'.($year_n).'</td>';
		print '</tr>';
		
		if (count($TDataFacture) > 0)
		{
			$var = true;
			foreach ($TDataFacture as $commercial => &$TStat)
			{
				//var_dump($TStat);exit;
				print '<tr '.$bc[$var].'>';
					print '<td>'.$commercial.'</td>';
					
					// Nb Propals
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						print '<td>'.(isset($TStat['CA'][$current_year]) ? $TStat['CA'][$current_year] : '-').'</td>'; // Nd propals par année sur 3 colonnes
					}
					
					// Nb Propals not signed
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						print '<td>'.(isset($TStat['CAPercent'][$current_year]) ? vatrate(round($TStat['CAPercent'][$current_year], 2), 1) : '-').'</td>'; // % propal no signé par année sur 3 colonnes
					}
					
					// Nb Clients
					for ($current_year=$year_n-2; $current_year<=$year_n; $current_year++)
					{
						print '<td>'.(isset($TStat['client'][$current_year]) ? count($TStat['client'][$current_year]) : '-').'</td>'; // Nd clients par année sur 3 colonnes
					}
						
					print '<td>'.(isset($TStat['topClient'][$year_n-2]['name']) ? $TStat['topClient'][$year_n-2]['name'] : '-').'</td>'; // Top client N-2
					print '<td>'.(isset($TStat['topClient'][$year_n-2]['percent_ca']) ? vatrate(round($TStat['topClient'][$year_n-2]['percent_ca'], 2), 1) : '-').'</td>';
					print '<td>'.(isset($TStat['topClient'][$year_n-1]['name']) ? $TStat['topClient'][$year_n-1]['name'] : '-').'</td>'; // Top client N-1
					print '<td>'.(isset($TStat['topClient'][$year_n-1]['percent_ca']) ? vatrate(round($TStat['topClient'][$year_n-1]['percent_ca'], 2), 1) : '-').'</td>';
					print '<td>'.(isset($TStat['topClient'][$year_n]['name']) ? $TStat['topClient'][$year_n]['name'] : '-').'</td>'; // Top client N
					print '<td>'.(isset($TStat['topClient'][$year_n]['percent_ca']) ? vatrate(round($TStat['topClient'][$year_n]['percent_ca'], 2), 1) : '-').'</td>';	
					
				print '</tr>';
				
				$var = !$var;
			}
	
			print '<tr '.$bc[$var].'>';
				print '<td><b>'.$langs->trans('Total').'</b></td>';
				foreach ($TTotalFacture['total_ca'] as $year => $nb)
				{
					print '<td>'.$nb.'</td>';
				}
				print '<td colspan="12"></td>';
			print '</tr>';
		}
		else
		{
			print '<td colspan="16"><div class="warning">'.$langs->transnoentitiesnoconv('noData').'</div></td>';
		}
	print '</table>';
	// Fin table info facture
	
	
	
	// Graph %CA par client (7 premiers et le reste dans 'autres')
	$year_n_1 = $year_n-1;
	
	$TPalmaresCAParClient = array();
	$TValue = array();
	
	$sql = 'SELECT s.nom AS name, YEAR(f.datef) AS `year`, SUM(f.total) AS total_ht 
			FROM '.MAIN_DB_PREFIX.'facture f 
			INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = f.fk_soc)
			WHERE YEAR(f.datef) = '.$year_n_1.'
			GROUP BY name';
	
	$resql = $db->query($sql);
	if ($resql)
	{
		$total_ca;
		while ($line = $db->fetch_object($resql))
		{
			$TPalmaresCAParClient[$line->name] += $line->total_ht;
			$total_ca += $line->total_ht;
		}
		
		arsort($TPalmaresCAParClient);
		
		
		$i=1;
		foreach ($TPalmaresCAParClient as $name => $total)
		{
			if ($i <= 7) $TValue[] = array('name' => $name, 'val' => $total);
			else $total_autre += $total;
			
			$i++;
		}
		
		if (isset($total_autre)) $TValue[] = array('name' => 'autres', 'val' => $total_autre);
	}
	
	//$TValue = array(array('truc'=>'machine', 'val'=>30), array('truc'=>'thomas', 'val'=>70));
	// chartType => PieChart
	print '<div style="width:50%;display:inline-block;">';
	$listeview = new TListviewTBS('graphPalmaresPercentCA');
	print $listeview->renderArray($PDOdb, $TValue
		,array(
			'type' => 'chart'
			,'chartType' => 'PieChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphPalmaresPercentCA', $year_n_1)
			)
		)
	);
	print '</div>';
	
	
	
	// Graph %CA par client (7 premiers et le reste dans 'autres')
	$TPalmaresCAParClient = array();
	$TValue = array();
	
	$sql = 'SELECT u.firstname AS name, YEAR(f.datef) AS `year`, SUM(f.total) AS total_ht 
			FROM '.MAIN_DB_PREFIX.'facture f 
			INNER JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = f.fk_user_author)
			WHERE YEAR(f.datef) = '.$year_n_1.'
			GROUP BY name';
	
	$resql = $db->query($sql);
	if ($resql)
	{
		$total_ca;
		while ($line = $db->fetch_object($resql))
		{
			$TPalmaresCAParClient[$line->name] += $line->total_ht;
			$total_ca += $line->total_ht;
		}
		
		arsort($TPalmaresCAParClient);
		
		foreach ($TPalmaresCAParClient as $name => $total)
		{
			$TValue[] = array('name' => $name, 'val' => $total);
		}
	}
	
	print '<div style="width:50%;display:inline-block;">';
	$listeview = new TListviewTBS('graphPercentCAPerCC');
	print $listeview->renderArray($PDOdb, $TValue
		,array(
			'type' => 'chart'
			,'chartType' => 'PieChart'
			,'liste'=>array(
				'titre'=>$langs->transnoentitiesnoconv('titleGraphPercentCAPerCC', $year_n_1)
			)
		)
	);
	print '</div>';
	
	// End of page
	llxFooter();