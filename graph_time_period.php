<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	
	
	
	llxHeader('',$langs->trans('RapportCapaciteProduction'));
	
	
	_print_tab_header();
	
	print dol_get_fiche_head($langs->trans('RapportCapaciteProduction'));
	print_fiche_titre($langs->trans("Capacité de production par période"));


	_print_filtres();
	print_rapport();
		
	
	
	function print_rapport(){
		global $langs;
		
		
		$date_d=preg_replace('/\//','-',GETPOST('date_deb'));
		$date_f=preg_replace('/\//','-',GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		if(empty(GETPOST('date_deb')))$date_deb=date('Y-m-d', strtotime(date('Y-m-d'))-(60*60*24*7));
		if(empty(GETPOST('date_fin')))$date_fin=date('Y-m-d');
	
	
		
		$PDOdb = new TPDOdb;
		
		$interval = (strtotime($date_fin) - strtotime($date_deb))/3600/24;
		$TDataBrut = get_user_capacity_period($date_deb, $date_fin);
		$TData = array();
		$total_temps_saisi =0;
		
		$hours_to_work = _get_hours_to_work($interval);
		$capacity = $hours_to_work/$interval;
		foreach ($TDataBrut as $ligne){
			$TData[]=array(
				'task_date'  => $ligne['task_date'],
				'duree'      => $ligne['duree'],
				'capacity'   => $capacity
			);
			$total_temps_saisi = $ligne['total'];
		}
				
		$explorer = new stdClass();
		$explorer->actions = array("dragToZoom", "rightClickToReset");
		
		$listeview = new TListviewTBS('graphProject');
		print $listeview->renderArray($PDOdb, $TData
			,array(
				'type' => 'chart'
				,'chartType' => 'ColumnChart'
				,'liste'=>array(
					'titre'=> $langs->transnoentities('timeInput')
				)
				,'hAxis'=>array('title'=> 'Date')
				,'vAxis'=>array('title'=> 'Temps')
				,'explorer'=>$explorer
			)
		);
		
		print_fiche_titre($langs->trans("Temps saisis/capacité de production"));
		
		
		$percentage = round(($total_temps_saisi/$hours_to_work) * 100, 2);
		?>
			<div class="tabBar">
				<table>
					<tbody>
						<tr>
							<td style="font-weight: bold">Pourcentage heures saisies/capacité de production :</td>
							<td <?php echo $percentage>80 ? 'style="font-weight : bold; color : green;"' : 'style="font-weight : bold; color : red;"' ?>><?php echo $percentage ?> %</td>
						</tr>						
					</tbody>			
				</table>
			</div>
		<?php
		
		}
	
	
	function get_user_capacity_period($date_deb, $date_fin){
		global $db;
		
		$userId = GETPOST('id');
		$TData=array();
		$Total_saisi = 0;
		
		
		$sql = 'SELECT ptt.rowid as rowid, ptt.task_date as "task_date", pt.ref as "task_ref", 
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
				$TData[$line->rowid] = array(
						'task_date'  =>date('d/m/Y', strtotime($line->task_date))
						,'duree'     => $line->task_duration/3600
						,'total'     => $Total_saisi
					);
				
			}
		}
		return $TData;
	}
	
	
	function _print_tab_header(){
		global $db, $langs;
		
		
		$form  = new Form($db);
		$object=new User($db);
		$userId=GETPOST('id');
		$object->fetch($userId);
		
	    if ($dolversion>=3.9) dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);
		
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
	
	
	function _get_hours_to_work($interval){
		global $db;
		
		
		$sql = 'SELECT usr.rowid FROM '.MAIN_DB_PREFIX.'user usr WHERE usr.statut=1';
		$resql = $db->query($sql);
		$total_hourstowork = 0;
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$user = new User($db);
				$user->fetch($line->rowid);
				$total_hourstowork += ($user->weeklyhours/7)*$interval;	
			}
		}
		return $total_hourstowork;
	}
