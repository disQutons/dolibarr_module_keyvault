<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		François Brichart			<francois@disqutons.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       keyvault/keyvaultindex.php
 *	\ingroup    keyvault
 *	\brief      Home page of keyvault top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/custom/keyvault/class/key.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("keyvault@keyvault"));

$action = GETPOST('action', 'aZ09');
$userid = GETPOSTINT('userid');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check - Protection if external user
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
if (!isModEnabled('keyvault')) {
	accessforbidden('Module not enabled');
}

$enablepermissioncheck = getDolGlobalInt('KEYVAULT_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$permissiontoinspect = $user->hasRight('keyvault', 'key', 'inspect');
} else {
	$permissiontoinspect = 1;
}

if (!$permissiontoinspect) {
	accessforbidden();
}



/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$keystatic = new Key($db);

llxHeader("", $langs->trans("KeyVault"), '', '', 0, 0, '', '', '', 'mod-keyvault page-index');

print load_fiche_titre($langs->trans("KeyVaultInspection"), '', 'keyvault.png@keyvault');

print '<div class="fichecenter"><div class="fichethirdleft">';


// Afficher le filtre
// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';

print '<tr><td>'.$langs->trans("User").'</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid ? $userid : -1, 'userid', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Check").'"></td></tr>';
print '</table>';
print '</form>';

// Permission user
if ($userid > 0) {
	$sql = "SELECT k.rowid, k.ref, k.label, k.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."keyvault_key as k";
	$sql .= " WHERE k.rights_user = ".$userid;
	$sql .= " ORDER BY k.label ASC";
	//$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
		
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("idkey", $max).'</th>';
		print '<th class="left">'.$langs->trans("keyname").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$keystatic->id=$obj->rowid;
				$keystatic->ref=$obj->ref;

				print '<tr class="oddeven">';
				print '<td colspan="2"class="nowrap">'.$keystatic->getNomUrl(1).'</td>';
				print '<td class="nowrap"><p>'.$obj->label.'</p></td>';
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoKeyUserRight").'</td></tr>';
		}
		print "</table><br>";
	}
}

print '</div><div class="fichetwothirdright">';

// Group user
if ($userid > 0) {
    // Récupérer les groupes de l'utilisateur
    $sql_groups = "SELECT fk_usergroup FROM " . MAIN_DB_PREFIX . "usergroup_user WHERE fk_user = " . $userid;
    $resql_groups = $db->query($sql_groups);

    $group_ids = array();
    if ($resql_groups) {
        while ($obj_group = $db->fetch_object($resql_groups)) {
            $group_ids[] = $obj_group->fk_usergroup;
        }
    }

    // Si l'utilisateur appartient à au moins un groupe
    if (!empty($group_ids)) {
		$group_conditions = array();
        foreach ($group_ids as $group_id) {
            $group_conditions[] = "FIND_IN_SET('" . $group_id . "', k.rights_group) > 0";
        }
        $group_condition = implode(" OR ", $group_conditions);

		// Requête pour récupérer les clés
		$sql = "SELECT k.rowid, k.ref, k.label, k.status";
		$sql .= " FROM " . MAIN_DB_PREFIX . "keyvault_key as k";
		$sql .= " WHERE FIND_IN_SET('" . $group_id . "', k.rights_group) > 0";
		//$sql .= " WHERE k.rights_group IN (" . implode(",", $group_ids) . ")";
		$sql .= " ORDER BY k.label ASC";

		$resql = $db->query($sql);

		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th colspan="2">' . $langs->trans("idkey", $max) . '</th>';
			print '<th class="left">' . $langs->trans("keyname") . '</th>';
			print '</tr>';

			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$keystatic->id = $obj->rowid;
					$keystatic->ref = $obj->ref;

					print '<tr class="oddeven">';
					print '<td colspan="2" class="nowrap">' . $keystatic->getNomUrl(1) . '</td>';
					print '<td class="left"><p>' . $obj->label . '</p></td>';
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoKeyGroupRight") . '</td></tr>';
			}

			print "</table><br>";
			$db->free($resql);
		}
	}
}
print '</div></div>';

// End of page
llxFooter();
$db->close();
