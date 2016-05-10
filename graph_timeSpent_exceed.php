<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	
	if(!$user->rights->mandarin->graph->project) accesfobidden();
	
	
	llxHeader('',$langs->trans('ReportExceed'));
	print dol_get_fiche_head('Projets en dépassement');
	print_fiche_titre($langs->trans("Projets en dépassement"));
	printRapport();
	
	
	function printRapport(){
		global $db;
		
		dol_include_once('/core/lib/date.lib.php');
		$fk_statut=GETPOST("fk_statut");
		
		
		?>
		<style type="text/css">
		table#rapport_depassement td,table#rapport_depassement th {
			white-space: nowrap;
			border-right: 1px solid #D8D8D8;
			border-bottom: 1px solid #D8D8D8;
		}
		</style>
		
		<div style="padding-bottom: 25px;">
			<table id="rapport_depassement" class="noborder" width="100%">
				<thead>
					<tr style="text-align:left;" class="liste_titre nodrag nodrop">
						<th class="liste_titre">Projet</th>
						<th class="liste_titre">Société</th>
						<th class="liste_titre">Date début</th>
						<th class="liste_titre">Date Fin</th>
						<th class="liste_titre">Temps prévu</th>
						<th class="liste_titre">Temps passé</th>
						<th class="liste_titre">Dépassement</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$TProjet=getProjectExceeded($fk_statut);
					
					
					foreach ($TProjet as $proj){
						
						$projet=$proj['projet'];
						$societe = new Societe($db);
						$societe->fetch($projet->socid);
						?>
						<tr>
							<td><?php echo $projet->getNomUrl(1) ?></td>
							<td><?php echo $societe->getNomUrl(1) ?></td>
							<td><?php echo date('d/m/Y', strtotime($projet->date_start)) ?></td>
							<td><?php echo date('d/m/Y', strtotime($projet->date_end)) ?></td>
							<td <?php echo ($proj['percentage'] < 100) ? ' style="color:#FF8000;font-weight: bold"" ' : ' style="color:red;font-weight: bold" ' ?> ><?php echo convertSecondToTime($proj['timeplanned']) ?></td>
							<td <?php echo ($proj['percentage'] < 100) ? ' style="color:#FF8000;font-weight: bold"" ' : ' style="color:red;font-weight: bold" ' ?> ><?php echo convertSecondToTime($proj['timespent']) ?></td>
							<td <?php echo ($proj['percentage'] < 100) ? ' style="color:#FF8000;font-weight: bold"" ' : ' style="color:red;font-weight: bold" ' ?> ><?php echo convertSecondToTime($proj['exceed']) ?></td>
						</tr>
					<?php	
					}
					?>
				</tbody>
			</table>
		</div>

<?php
	}
	
	
	function getProjectExceeded($statut){
		global $db;
		
		$sql = "SELECT rowid as id ";
		$sql .= "FROM ".MAIN_DB_PREFIX."projet p ";
		$sql .= "WHERE p.fk_statut=1 ";
		
		$TProject = array();
		
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($line = $db->fetch_object($resql))
			{
				$timespent=0;
				$timeplanned=0;
				$exceed=0;
				
				$projet = new Project($db);
				$projet->fetch($line->id);
				$TTask = getTaskFromProjet($projet->id);
				
				foreach ($TTask as $task){
					$timespent+=$task->duration_effective;
					$timeplanned+=$task->planned_workload;
					$exceed+=$timespent-$timeplanned;
				}
				
				if ($timeplanned != 0)$percentage = ($timespent/$timeplanned)*100;
				else if($timespent > $timeplanned)$percentage = 100;
				else $percentage = 0;
				
				if($percentage >= 80){
					$TProject[]= array(
						'projet'       => $projet,
						'timespent'    => $timespent,
						'timeplanned'  => $timeplanned,
						'exceed'       => $exceed > 0 ? $exceed : 0,
						'percentage'   => $percentage
					);
				}					
			}
		}
		
		//var_dump($sql);
		return $TProject;
	}


	function getTaskFromProjet($idProjet){
		global $db;
		
		$TTask==array();
		
		$sql = "SELECT rowid ";
		$sql .= "FROM ".MAIN_DB_PREFIX."projet_task ";
		$sql .= "WHERE fk_projet=".$idProjet;
		$resql = $db->query($sql);
		if ($resql)
		{ 
			while ($line = $db->fetch_object($resql))
			{
				$task = new Task($db);
				$task->fetch($line->rowid);
				//TODO ajouter cette putain de tache au tableau TTaches
				$TTask[]=$task;
			}
		}
		return $TTask;
		
		
	}
	