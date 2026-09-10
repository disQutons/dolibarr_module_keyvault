<?php
/* Copyright (C) 2025		François Brichart			<francois@disqutons.fr>
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
 * \file        core/triggers/interface_99_modKeyVault_KeyVaultTriggers.class.php
 * \ingroup     keyvault
 * \brief       Trigger file for KeyVault module
 */

// Il écoute les événements déclenchés par class/key.class.php (création, modification, suppression,
// validation, mise en brouillon, désactivation, réactivation) et enregistre un événement d'agenda
// (ActionComm) pour chaque clé. Ces événements sont ensuite visibles dans l'onglet "Events" de la clé,
// déjà fourni par key_agenda.php (fonction show_actions_done()), ce qui donne l'historique demandé.
//
// Important : pour que ce trigger soit pris en compte, le module KeyVault doit être désactivé puis
// réactivé après ce correctif (le flag module_parts['triggers'] n'est relu qu'à l'activation du module).

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggered functions for the KeyVault module
 */
class InterfaceKeyVaultTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "keyvault";
		$this->description = "Triggers of the KeyVault module log an agenda event on each key for every creation, modification, deletion or status change (validate, set draft, disable, enable).";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'key';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 *
	 * @param	string			$action	Event action code
	 * @param	CommonObject	$object	Object
	 * @param	User			$user	Object user
	 * @param	Translate		$langs	Object langs
	 * @param	Conf			$conf	Object conf
	 * @return	int						Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('keyvault') || !isModEnabled('agenda')) {
			return 0;
		}
		if (empty($object->element) || $object->element != 'key') {
			return 0;
		}

		// Trigger action codes that must produce a history entry, and the translation key of their label.
		// Note: 'MYOBJECT_VALIDATE' and 'KEYVAULT_MYOBJECT_xxx' are the exact (and inconsistently prefixed)
		// trigger names actually called by class/key.class.php's validate()/setDraft()/cancel()/reopen().
		$labels = array(
			strtoupper(get_class($object)).'_CREATE' => 'KeyVaultHistoCreated',
			strtoupper(get_class($object)).'_MODIFY' => 'KeyVaultHistoModified',
			strtoupper(get_class($object)).'_DELETE' => 'KeyVaultHistoDeleted',
			'MYOBJECT_VALIDATE' => 'KeyVaultHistoValidated',
			'KEYVAULT_MYOBJECT_UNVALIDATE' => 'KeyVaultHistoSetDraft',
			'KEYVAULT_MYOBJECT_CANCEL' => 'KeyVaultHistoDisabled',
			'KEYVAULT_MYOBJECT_REOPEN' => 'KeyVaultHistoEnabled',
		);

		if (!isset($labels[$action])) {
			return 0;
		}

		$langs->load("keyvault@keyvault");

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$label = $langs->transnoentities($labels[$action], $object->ref);

		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = 'AC_'.$action;
		$actioncomm->label = $label;
		$actioncomm->note_private = $label;
		$actioncomm->datep = dol_now();
		$actioncomm->datef = dol_now();
		$actioncomm->percentage = -1;
		$actioncomm->authorid = $user->id;
		$actioncomm->userownerid = $user->id;
		$actioncomm->fk_element = $object->id;
		$actioncomm->elementid = $object->id;
		$actioncomm->elementtype = 'key@keyvault';

		$result = $actioncomm->create($user);
		if ($result < 0) {
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
			return -1;
		}

		return 1;
	}
}
