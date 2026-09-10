<?php
/* Copyright (C) 2022       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/keyvault/ajax/key.php
 *       \brief      File to return Ajax response on key list request
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
dol_include_once('/keyvault/class/key.class.php');
dol_include_once('/keyvault/lib/keyvault_key.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$mode = GETPOST('mode', 'aZ09');
$objectId = GETPOSTINT('objectId');
$field = GETPOST('field', 'aZ09');
$value = GETPOST('value', 'aZ09');

// @phan-suppress-next-line PhanUndeclaredClass
$object = new Key($db);

// Security check
if (!$user->hasRight('keyvault', 'key', 'write')) {
	accessforbidden();
}

/*
 * View
 */

dol_syslog("Call ajax keyvault/ajax/key.php");

top_httphead();

// FIX $field n'était soumis à aucune liste blanche : un POST avec field=pass permettait d'écraser le mot
//   de passe en clair, en contournant totalement keyvaultEncrypt (voir lib/keyvault_key.lib.php). On
//   n'autorise donc désormais que les champs listés ci-dessous, tous non sensibles. pass, rights_user
//   et rights_group sont explicitement exclus.
// 
// FIX Seul le droit global keyvault>key>write était vérifié, sans tenir compte des utilisateurs/groupes
//   autorisés propres à la clé (rights_user/rights_group), contrairement à key_card.php. On applique donc
//   ici la même logique d'accès que key_card.php avant toute modification.

$allowedFields = array('label', 'login', 'url', 'note_public', 'note_private', 'fk_soc', 'fk_categ');

// Update the object field with the new value
if ($objectId && $field && isset($value)) {
	$object->fetch($objectId);

	if ($object->id > 0) {
		$accessAllowed = false;

		if (empty($object->rights_user) && empty($object->rights_group)) {
			$accessAllowed = true;
		}
		if (!$accessAllowed && !empty($object->fk_user_creat) && (int) $object->fk_user_creat === (int) $user->id) {
			$accessAllowed = true;
		}
		if (!$accessAllowed && !empty($object->rights_user)) {
			$userIds = explode(',', $object->rights_user);
			if (in_array((string) $user->id, $userIds)) {
				$accessAllowed = true;
			}
		}
		if (!$accessAllowed && !empty($object->rights_group)) {
			$userGroup = new UserGroup($db);
			$userGroups = $userGroup->listGroupsForUser($user->id);
			if (is_array($userGroups)) {
				foreach (explode(',', $object->rights_group) as $groupId) {
					if (array_key_exists($groupId, $userGroups)) {
						$accessAllowed = true;
						break;
					}
				}
			}
		}

		if (!$accessAllowed) {
			print json_encode(['status' => 'error', 'message' => 'Unauthorized access to this key']);
			$db->close();
			exit;
		}

		if (!in_array($field, $allowedFields, true)) {
			print json_encode(['status' => 'error', 'message' => 'Field '.$field.' is not allowed to be edited here']);
			$db->close();
			exit;
		}

		$object->$field = $value;
	}
	$result = $object->update($user);

	if ($result < 0) {
		print json_encode(['status' => 'error', 'message' => 'Error updating '. $field]);
	} else {
		print json_encode(['status' => 'success', 'message' => $field . ' updated successfully']);
	}
}

$db->close();
