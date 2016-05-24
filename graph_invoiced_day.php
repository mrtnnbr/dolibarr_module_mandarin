<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	
	
	
	llxHeader('',$langs->trans('RapportHeuresFacturees'));
	
	print dol_get_fiche_head($langs->trans('RapportHeuresFacturees'));
	print_fiche_titre($langs->trans("RapportHeuresFacturees"));
	
	_print_filtres();
	print_rapport();
	
	
		
	function get_factures_for_graph($date_deb, $date_fin){
		global $db;
		
		$sql = 'SELECT f.rowid, f.datef FROM '.MAIN_DB_PREFIX.'facture f ';
		$sql .= 'WHERE f.paye=1 AND f.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" ';
		//var_dump($sql);
		$facture = new Facture($db);
		$resql = $db->query($sql);
		$TData = array();
		$TTime_spent= get_timespent($date_deb, $date_fin);
		$TTime_facture = array();
		$TFact = array();

		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$facture->fetch($line->rowid);
				
				
				$timeFacture=get_time_facture($facture);
				$TTime_facture[] =  $timeFacture;
				
				$TFact[] = $facture;
			}
		}
		
		
		
		
	
		//TODO reformattage des deux tableaux sous un seul
		foreach ($TTime_facture as $facture){

			foreach ($facture as $line){
				//var_dump($line);
				if (!isset($TData[$line['datef']])){
					$time_consummed = $TData[$line['datef']]['timespent'];
					$time_billed = $TData[$line['datef']]['timebilled'];
					$TData[$line['datef']] = array(
								'date' => $line['datef'],
								'timespent' => intval($time_consummed),
								'timebilled' => intval($time_billed)
								
								
							);
							
				}else if (isset($TData[$line['datef']])){
					$time_consummed = ($TData[$line['datef']]['timespent'] +  $TTime_spent[$line['datef']]['duree']);
					$time_billed = ($TData[$line['datef']]['timebilled'] + $line['time']);
	
					//TODO ne pas rajouter les valeurs, mais les remplacer par celels calculées.
					$TData[$line['datef']]['timespent'] = intval($time_consummed);
					$TData[$line['datef']]['timebilled'] = intval($time_billed);
					
					
				}
			}		
		}
		return $TData;
		
		
	}
	
	
	function get_time_facture($facture){
		global $db;
		
		
		$facture->fetch_lines();
		$service = new Product($db);
		$TFact = array();
		$time_facture = 0;
		//var_dump($facture);
		foreach ($facture->lines as $line) { 
			if ($line->product_type == 1){
				if(!empty($line->fk_product)){
					$service->fetch($line->fk_product);
					$time_service = $service->duration_value;
					//var_dump($service->duration_unit, $time_service);
					if ($service->duration_unit == 'h') $time_service*=3600;
					else if($service->duration_unit == 'd') $time_service*=3600*24;
					else if($service->duration_unit == 'w') $time_service*=3600*24*7;
					else if($service->duration_unit == 'm') $time_service*=3600*24*30;
					else if($service->duration_unit == 'y') $time_service*=3600*24*365;
					//var_dump($time_service);
					$time_facture += $time_service;
					
				}
			}	
			$TFact[] = array(
							'time'  => $time_facture/3600,
							'datef' => date('Y-m', $facture->date)
						);
		}
		//var_dump($TFact);
		return $TFact;
	}
	
		function get_timespent($date_deb, $date_fin){
		global $db;
		
		$userId = GETPOST('id');
		$TData=array();
		$Total_saisi = 0;
		
		
		$sql = 'SELECT DISTINCT(ptt.rowid) as rowid, ptt.task_date as "task_date", pt.ref as "task_ref", 
		SUM(ptt.task_duration) as "task_duration" ';
		
		$sql .= 'FROM '.MAIN_DB_PREFIX.'projet_task_time ptt ';
		$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'projet_task pt ON (pt.rowid=ptt.fk_task) ';
		$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'projet p ON (p.rowid=pt.fk_projet) ';
		$sql .= 'WHERE ptt.task_date BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" ';
		$sql .= 'GROUP BY ptt.task_date ';
		$sql .= 'ORDER BY ptt.task_date ';
		$resql = $db->query($sql);
		
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$Total_saisi += $line->task_duration/3600; 
				$TData[date('Y-m', strtotime($line->task_date))] = array(
						'rowid'      => $line->rowid,
						'task_date'  =>date('Y-m', strtotime($line->task_date))
						,'duree'     => $line->task_duration/3600
						,'total'     => $Total_saisi
					);
				
			}
		}
		return $TData;
	}

	
	function _print_filtres(){
		global $db, $langs;
		
		$id=(int) GETPOST('id');
		
		$Tform = new TFormCore($_SERVER["PHP_SELF"],'formFiltres', 'POST');
		_get_filtre($Tform);
	}
	
	
	function _get_filtre($form){
	    
	    print '<div class="tabBar">';
	    print '<table>';
		print '<tr>';
		print '<td>Date de début : </td>';
		print '<td>'.$form->calendrier('', 'date_deb', ($_REQUEST['date_deb'])? $_REQUEST['date_deb'] : '').'</td>';
		print '</tr>';
		print '<tr>';
		print '<td>Date de fin : </td>';
		print '<td>'.$form->calendrier('', 'date_fin', ($_REQUEST['date_fin'])? $_REQUEST['date_fin'] : '').'</td>';
		print '</tr>';
	
	    print '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
	    print '</table>';
	    
	    print '</div>';
	}
	
	function transform_data($date_deb, $date_fin){
		
		$TDataBrut = get_factures_for_graph($date_deb, $date_fin);
		$TTimespent = get_timespent($date_deb, $date_fin);
		$TData = array();
		
		return $TData;
	}
	
	function print_rapport(){
		global $langs;
		
		
		$date_d=preg_replace('/\//','-',GETPOST('date_deb'));
		$date_f=preg_replace('/\//','-',GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		if(empty(GETPOST('date_deb')))$date_deb=date('Y-m-d', strtotime(date('Y-m-d'))-(60*60*24*365));
		if(empty(GETPOST('date_fin')))$date_fin=date('Y-m-d');

		$PDOdb = new TPDOdb;
		
		$TData = get_factures_for_graph($date_deb, $date_fin);
		
		$percentage = _get_percentage($TData);
		//affichage
				
		$explorer = new stdClass();
		$explorer->actions = array("dragToZoom", "rightClickToReset");
		
		$listeview = new TListviewTBS('graphProject');
		print $listeview->renderArray($PDOdb, $TData
			,array(
				'type' => 'chart'
				,'chartType' => 'ColumnChart'
				,'liste'=>array(
					'titre'=> $langs->transnoentities('InvoicedDays')
				)
				,'hAxis'=>array('title'=> 'Mois')
				,'vAxis'=>array('title'=> 'Nombre d\'heures')
				,'explorer'=>$explorer
			)
		);
		
		print_fiche_titre($langs->transnoentities("InvoicedDays/DaysSpent"));
		
		
		
		?>
			<div class="tabBar">
				<table>
					<tbody>
						<tr>
							<td style="font-weight: bold">Pourcentage des heures facturées sur le heures saisies :</td>
							<td <?php echo $percentage>80 ? 'style="font-weight : bold; color : green;"' : 'style="font-weight : bold; color : red;"' ?>><?php echo $percentage ?> %</td>
						</tr>						
					</tbody>			
				</table>
			</div>
		<?php
		
		}

	function _get_percentage($TData){
		$total_passe = 0;
		$total_facture = 0;
		
		
		foreach ($TData as $ligne){
				//var_dump($ligne);
				$total_facture += $ligne['timebilled'];
				$total_passe += $ligne['timespent'];
		}
		
		
		$percentage = round(($total_facture/$total_passe) * 100, 2);
		
		return $percentage;
	}
