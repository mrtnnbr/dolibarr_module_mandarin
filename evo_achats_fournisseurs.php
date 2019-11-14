<?php

require 'config.php';

dol_include_once('/core/class/html.form.class.php');
dol_include_once('/fourn/class/fournisseur.facture.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

if (! empty($conf->categorie->enabled))
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (! empty($conf->stock->enabled)) $langs->load("stocks");
if (! empty($conf->facture->enabled)) $langs->load("bills");

$langs->load("products");
$langs->load("suppliers");
$langs->load("companies");

// extra column because CSV export ignores the last column of the list -> it will ignore this dummy column instead of a real data column.
$dummyColTd = '<td style="display: none"></td>';
$dummyColTh = '<th style="display: none"></th>';

$mode = GETPOST('mode');
$fk_soc = GETPOST('fk_soc');
$search_categ = GETPOST('search_categ', 'array');
$date_start=dol_mktime(0,0,0,GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
$date_end=dol_mktime(0,0,0,GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
	$fk_soc="";
	$search_categ=0;
	$date_start = strtotime(date("01-01-Y"));
	$date_end = strtotime(date("31-12-Y"));
}

if (empty($date_start)) $date_start = strtotime(date("01-01-Y"));
if (empty($date_end)) $date_end = strtotime(date("31-12-Y"));

$TYear = array();
$year = (int) date('Y', $date_start);
$yearend = (int) date('Y', $date_end);
if ($date_start < $date_end)
{
    while ($year <= $yearend){
        $TYear[] = $year;
        $year++;
    }
}
else
{
    while ($year >= $yearend){
        $TYear[] = $year;
        $year--;
    }

    sort($TYear);
}

$TMonth = array();
$month = (int) date('m', $date_start);
$numyear = count($TYear);
$monthend = (int) date('m', $date_end);

foreach ($TYear as $k => $year)
{
    $end = 12;
    if ($k == 0) $start = $month;
    else $start = 1;

    if ($k == $numyear -1) $end = $monthend;

    for ($i = $start; $i<=$end; $i++)
    {
        $TMonth[$year][$i] = $langs->trans('month'. date('M', strtotime(date('Y-'.$i.'-01'))) ).'-'.$year;
    }
    if ($i == 12) $start = 1;

}

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = (GETPOST("page",'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

$form = new Form($db);
$htmlother=new FormOther($db);

$hookmanager->initHooks(array('mandarinArticleslist'));
$extrafields = new ExtraFields($db);

$transkey = 'linkMenuReportAchatsFournisseurs';
if ($mode == "CA") $transkey = 'linkMenuReportAchatsCAFournisseurs';

llxHeader('', $langs->trans($transkey), '');

//dol_fiche_head();

$sql = "SELECT cat.rowid as cat_id, s.rowid as socid";

if ($mode == "CA") $numfield = 'd.total_ht';
else $numfield = "IF(f.type = ".FactureFournisseur::TYPE_CREDIT_NOTE." , -(d.qty), d.qty)";

foreach ($TMonth as $year => $month) {
    $start = '';
    foreach ($month as $k => $m)
    {
        if (empty($start)) $firststart = strtotime(" 01-$k-$year");
        $start = strtotime(" 01-$k-$year");
        $end = strtotime("+1 month -1 day", $start);

        $sql.= ", SUM(IF(f.datef >= '".date("Y-m-d 00:00:00",$start)."' AND f.datef <= '".date("Y-m-d 23:59:59", $end)."', ".$numfield.", 0)) as ".str_replace("-", "_", $m);
    }

	$sql.= ", SUM(IF(f.datef >= '".date("Y-m-d 00:00:00",$firststart)."' AND f.datef <= '".date("Y-m-d 23:59:59", $end)."', ".$numfield.", 0)) as total_".$year;

}
$sql.= ", SUM(IF(f.datef >= '".date("Y-m-d 00:00:00",$date_start)."' AND f.datef <= '".date("Y-m-d 23:59:59", $date_end)."', ".$numfield.", 0)) as total_global";

$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det AS d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f ON f.rowid = d.fk_facture_fourn";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_fournisseur AS cf ON cf.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie AS cat ON cat.rowid = cf.fk_categorie";

$sql.= " WHERE f.fk_statut > 0";
$sql.= " AND f.datef >= '".date('Y-m-d 00:00:00', $date_start)."'";
$sql.= " AND f.datef <= '".date('Y-m-d 23:59:59',$date_end)."'";

// filters
if (!empty($search_categ))
{
	$sql.= " AND cat.rowid IN (".implode(',', $search_categ).")";
}

if (!empty($fk_soc) && $fk_soc > 0)
{
	$sql.= " AND f.fk_soc = " . $fk_soc;
}

$sql.= " GROUP BY cat.rowid, s.rowid";
$sql.= " ORDER BY cat.rowid ASC, s.nom ASC";

//print $sql;
$resql = $db->query($sql);

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print_barre_liste($langs->trans($transkey), $page, $_SERVER["PHP_SELF"]);

print '<div class="info">';
print '<span class="fa fa-info-circle" title="Information pour les administrateurs"></span> ';
print $langs->trans($transkey.'Help');
print '</div>';

$moreforfilter='';
$moreforfilter.='<div class="divsearchfield" style="width:50%;"><table style="width:100%;">';
if (! empty($conf->categorie->enabled))
{
	$moreforfilter.='<tr><td>'.$langs->trans('Categories'). ' : </td>';
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, '', 'parent', 64, 0, 1);
	$moreforfilter.='<td colspan="2">'.$form->multiselectarray('search_categ', $cate_arbo, $search_categ, '', 0, '', 0, '50%').'</td></tr>';
}

$moreforfilter.='<tr><td>'.$langs->trans('Supplier') . ' : </td>';
$moreforfilter.='<td colspan="2">'.$form->select_company($fk_soc, 'fk_soc', 's.fournisseur = 1', 1).'</td></tr>';

$moreforfilter.='<tr><td>'.$langs->trans('DateInvoice'). ' </td>';
$moreforfilter.='<td>'.$langs->trans('From'). ' : ' .$form->select_date($date_start, 'date_start', 0,0,0,'',1,0,1) .'</td>';
$moreforfilter.='<td>'.$langs->trans('to'). ' : ' .$form->select_date($date_end, 'date_end', 0,0,0,'',1,0,1).'</td></tr>';

$moreforfilter.='</table></div>';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter.=$hookmanager->resPrint;
else $moreforfilter=$hookmanager->resPrint;

if ($moreforfilter)
{
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';

$colspan = 4;

foreach ($TMonth as $year => $month)
{
    $colspan+=count($month);
}

print '<td class="liste_titre" colspan="'.$colspan.'"></td>';
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print $dummyColTd;
print '</tr>';

print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans('Categories').'</th>';
// print '<th class="liste_titre">'.$langs->trans('Ref').'</th>';
print '<th class="liste_titre">'.$langs->trans('Supplier').'</th>';
print '<th class="liste_titre">'.$langs->trans('Ref').'</th>';
foreach ($TMonth as $year => $month) {
     foreach ($month as $m)
     {
         print '<th class="liste_titre">'.$m.'</th>';
     }
    print '<th class="liste_titre">Total '.$year.'</th>';
}
print '<th class="liste_titre">Total&nbsp;global</th>';
print $dummyColTh;
print '</tr>';

$lastcat = '';
$GlobalTabTotal = array();

while ($obj = $db->fetch_object($resql))
{
    if ($lastcat !== $obj->cat_id)
    {
        if (!empty($tabtotal))
        {
        	//var_dump($tabtotal);
            print "<tr class='liste_total'>";
            // print "<td></td>";
            print "<td> Total ".(!empty($cat->label) ? $cat->label : 'Sans catégorie')."</td>";
            print "<td></td>";
            print "<td></td>";
			foreach ($tabtotal as $tab)
			{
				if ($mode == "CA") print "<td>".price($tab)." €</td>";
				else print "<td>".$tab."</td>";
			}
            print $dummyColTd;
			print "</tr>";

			foreach ($tabtotal as $k => $v) $GlobalTabTotal[$k] += $v;
        }

        $lastcat = $obj->cat_id;
        $catprinted = false;
        $tabtotal = array();
    }

    print '<tr>';

    if ($catprinted) print '<td></td>';
    else
    {
        if (!empty($obj->cat_id))
        {
            $cat = new Categorie($db);
            $cat->fetch($obj->cat_id);

            // print "<td>".$cat->description."</td>";
            print "<td>".$cat->label."</td>";
        }
        else
        {
            print "<td>Sans catégorie</td>";
        }


        $catprinted = true;
    }

    $soc = new Societe($db);
    $soc->fetch($obj->socid);

    print "<td>".$soc->name."</td>";
    print "<td style='padding-right: 15px'>".$soc->code_fournisseur."</td>";

	$totalligne = 0;
    foreach($TMonth as $year => $month)
    {
        foreach ($month as $m)
        {
            $field = str_replace("-", "_", $m);
            $val = price2num($obj->{$field}, 'MT');

            $tabtotal[$field]+= ($mode == "CA") ? price($val) : $val;
            print '<td>'.(($mode == "CA") ? price($val) . " €" : $val).'</td>';
        }
        $field = "total_".$year;
		$val = price2num($obj->{$field}, 'MT');

        if (empty($tabtotal[$field])) $tabtotal[$field] = ($mode == "CA") ? price($val) : $val;
        else $tabtotal[$field] += ($mode == "CA") ? price($val) : $val;

		if (empty($totalligne)) $totalligne = ($mode == "CA") ? price($val) : $val;
		else $totalligne += ($mode == "CA") ? price($val) : $val;

        print '<td>'.(($mode == "CA") ? price($val) . " €" : $val).'</td>';


	}
	$tabtotal['ligne'] += price2num(($mode == "CA") ? price($totalligne) : $totalligne, 'MT');

	print '<td>'.(($mode == "CA") ? price($obj->total_global) ." €" : $obj->total_global).'</td>';

    print $dummyColTd;
    print '</tr>';

}

if (!empty($tabtotal))
{
    print "<tr class='liste_total'>";
    // print "<td></td>";
    print "<td> Total ".(!empty($cat->label) ? $cat->label : 'Sans catégorie')."</td>";
    print "<td></td>";
    print "<td></td>";
    foreach ($tabtotal as $tab)
    {
        print "<td>".(($mode == "CA") ? price($tab) . " €" : $tab)."</td>";
    }
    print $dummyColTd;
	print "</tr>";

	foreach ($tabtotal as $k => $v) $GlobalTabTotal[$k] += $v;
}

print "<tr class='liste_total'>";
print "<td> Total Général</td>";
print "<td></td>";
print "<td></td>";
foreach ($GlobalTabTotal as $tab)
{
	print "<td>".(($mode == "CA") ? price($tab) . " €" : $tab)."</td>";
}
print $dummyColTd;
print "</tr>";

print '</table>';
print '</div>';
print '</form>';

?>
<style type="text/css">
    *[field=total],tr.liste_total td {
        font-weight: bold;
    }
</style>
<?php

dol_fiche_end();

llxFooter();

