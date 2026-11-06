<?php
/* Copyright (C) 2025		François Brichart			<francois@disqutons.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    keyvault/lib/keyvault.lib.php
 * \ingroup keyvault
 * \brief   Library files with common functions for KeyVault
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function keyvaultAdminPrepareHead()
{
	global $langs, $conf, $db;

	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('keyvault_key');

	$langs->load("keyvault@keyvault");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/keyvault/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/keyvault/admin/key_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = (isset($extrafields->attributes['keyvault_key']['label']) && is_countable($extrafields->attributes['keyvault_key']['label'])) ? count($extrafields->attributes['keyvault_key']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'key_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/keyvault/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@keyvault:/keyvault/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@keyvault:/keyvault/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'keyvault@keyvault');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'keyvault@keyvault', 'remove');

	return $head;
}
