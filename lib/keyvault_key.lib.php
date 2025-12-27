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
 * \file    lib/keyvault_key.lib.php
 * \ingroup keyvault
 * \brief   Library files with common functions for Key
 */

/**
 * Prepare array of tabs for Key
 *
 * @param	Key	$object					Key
 * @return 	array<array{string,string,string}>	Array of tabs
 */
function keyPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("keyvault@keyvault");

	$showtabofpagecontact = 1;
	$showtabofpagenote = 1;
	$showtabofpagedocument = 1;
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/keyvault/key_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Key");
	$head[$h][2] = 'card';
	$h++;

	/*if ($showtabofpagecontact) {
		$head[$h][0] = dol_buildpath("/keyvault/key_contact.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}*/

	if ($showtabofpagenote) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dol_buildpath('/keyvault/key_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->keyvault->dir_output."/key/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/keyvault/key_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	if ($showtabofpageagenda) {
		$head[$h][0] = dol_buildpath("/keyvault/key_agenda.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		$head[$h][2] = 'agenda';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@keyvault:/keyvault/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@keyvault:/keyvault/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'key@keyvault');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'key@keyvault', 'remove');

	return $head;
}

/**
 * Crypte tous les champs sensibles non cryptés dans la table des clés
 *
 * @return int Nombre de champs cryptés, ou -1 en cas d'erreur
 */
function encryptAllKeys()
{
    global $db;

    $encryptedCount = 0;
    $db->begin();

    // Sélectionner tous les enregistrements
    $sql = "SELECT rowid, pass FROM " . MAIN_DB_PREFIX . "keyvault_key";
    $result = $db->query($sql);

    if ($result) {
        while ($obj = $db->fetch_object($result)) {
            $rowid = $obj->rowid;
            $fieldsToUpdate = [];

            // Cryptage du mot de passe
            if (!empty($obj->pass) && !isFieldEncrypted($obj->pass)) {
                $fieldsToUpdate['pass'] = dolEncrypt($obj->pass);
                $encryptedCount++;
            }

            // Mise à jour en base
            if (!empty($fieldsToUpdate)) {
                $updateSql = "UPDATE " . MAIN_DB_PREFIX . "keyvault_key SET ";
                $updates = [];
                foreach ($fieldsToUpdate as $field => $value) {
                    $updates[] = "$field = '" . $db->escape($value) . "'";
                }
                $updateSql .= implode(', ', $updates);
                $updateSql .= " WHERE rowid = " . (int)$rowid;

                if (!$db->query($updateSql)) {
                    dol_syslog("Erreur lors de la mise à jour des champs cryptés pour la clé ID " . $rowid, LOG_ERR);
                    $db->rollback();
                    return -1;
                }
            }
        }
    } else {
        dol_syslog("Erreur lors de la sélection des clés pour cryptage", LOG_ERR);
        $db->rollback();
        return -1;
    }

    $db->commit();
    return $encryptedCount;
}

/**
 * Vérifie si un champ semble déjà crypté
 *
 * @param string $value Valeur du champ
 * @return bool True si le champ semble crypté
 */
function isFieldEncrypted($value)
{
    return (base64_encode(base64_decode($value, true)) === $value && !ctype_digit($value));
}

