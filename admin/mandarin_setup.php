<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/mandarin.php
 * 	\ingroup	mandarin
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/mandarin.lib.php';

// Translations
$langs->load("mandarin@mandarin");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code) == 'yes' ? 1 : 0, 'entier', 0, '', $conf->entity) > 0)
	{
		if($code == 'MANDARIN_TRACE_COST_PRICE' &&  GETPOST($code) == 'yes') {
			define('INC_FROM_DOLIBARR',true);
			dol_include_once('/mandarin/config.php');
			dol_include_once('/mandarin/class/costpricelog.class.php');
			$o=new TProductCostPriceLog;
			$PDOdb=new TPDOdb;
//			$PDOdb->debug =true;
			$o->init_db_by_vars($PDOdb);
			
		}
		
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "mandarinSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = mandarinAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104996Name"),
    0,
    "mandarin@mandarin"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Example with a yes / no select
/*
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONSTNAME">';
print $form->selectyesno("CONSTNAME",$conf->global->CONSTNAME,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';
*/

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MANDARIN_OBJECTIF_COUT_INTERIM").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MANDARIN_OBJECTIF_COUT_INTERIM">';
print '<input type="text" name="MANDARIN_OBJECTIF_COUT_INTERIM" value="'.$conf->global->MANDARIN_OBJECTIF_COUT_INTERIM.'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MANDARIN_HORAIRE_MOYEN_NOMAL").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MANDARIN_HORAIRE_MOYEN_NOMAL">';
print '<input type="text" name="MANDARIN_HORAIRE_MOYEN_NOMAL" value="'.$conf->global->MANDARIN_HORAIRE_MOYEN_NOMAL.'" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_MANDARIN_GRAPH_TIME_ON_PERIOD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MANDARIN_GRAPH_TIME_ON_PERIOD">';
echo ajax_constantonoff('MANDARIN_GRAPH_TIME_ON_PERIOD');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_MANDARIN_TRACE_COST_PRICE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MANDARIN_TRACE_COST_PRICE">';
echo $form->selectyesno('MANDARIN_TRACE_COST_PRICE',$conf->global->MANDARIN_TRACE_COST_PRICE); // attention pas ajax car je dois passer en haut

print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';


print '</table>';

llxFooter();

$db->close();
