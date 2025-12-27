<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		François Brichart			<francois@disqutons.fr>
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
 * \file    subventions/class/actions_subventions.class.php
 * \ingroup subventions
 * \brief   Example hook overload.
 *
 * TODO: Write detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
dol_include_once('/custom/keyvault/class/key.class.php');

/**
 * Class ActionsKeyVault
 */
class ActionsKeyVault extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
	
	/**
	 * Hook pour afficher des informations dans l'onglet vue d'ensemble d'un projet
	 */
	/*public function ImportInsert($parameters) {
        $arrayrecord = &$parameters['arrayrecord'];
        $array_match_file_to_database = $parameters['array_match_file_to_database'];

        // Trouver l'index de la colonne 's.pass'
        $passIndex = null;
        foreach ($array_match_file_to_database as $index => $field) {
            if ($field === 's.pass') {
                $passIndex = $index;
                break;
            }
        }

        if ($passIndex !== null && !empty($arrayrecord[$passIndex]['val'])) {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

            $originalPassword = $arrayrecord[$passIndex]['val'];
            $encryptedPassword = dolEncrypt($originalPassword);
            $arrayrecord[$passIndex]['val'] = $encryptedPassword;
        }
        return 0;
    }*/
    public function ImportInsert($parameters)
    {
        $arrayrecord = &$parameters['arrayrecord'];
        $mapping     = $parameters['array_match_file_to_database'];

        require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

        foreach ($arrayrecord as $pos => &$cell) {
            if (
                !empty($mapping[$pos]) &&
                $mapping[$pos] === 's.pass' &&
                !empty($cell['val'])
            ) {
                $cell['val'] = dolEncrypt($cell['val']);
            }
        }

        return 0;
    }

	/**
	 * Execute action completeTabsHead
	 *
	 * @param	array<string,mixed>	$parameters		Array of parameters
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			'add', 'update', 'view'
	 * @param	Hookmanager			$hookmanager	Hookmanager
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user, $db;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('keyvault@subventions');
			// used when we want to add some tabs
			
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			$identifiantsOnglets = array_column($parameters['head'], 2);


			// Verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			//if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['societe']) && !in_array('keyvault', $identifiantsOnglets)) {
				$datacount = 0;
				$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "keyvault_key WHERE fk_soc = ".$id;
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$datacount = $obj->nb;
				} else {
					dol_syslog("Erreur SQL : " . $this->db->lasterror(), LOG_ERR);
				}
				if ($datacount > 0) {
					$parameters['head'][$counter][0] = dol_buildpath('/keyvault/key_list.php', 1) . '?socid='.$id.'&search_fk_soc='.$id;
					$parameters['head'][$counter][1] = $langs->trans('KeyVault');
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'keyvault';
				$counter++;
			}
			
			if ($counter > 0 && (int) DOL_VERSION < 14) {  // @phpstan-ignore-line
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// From V14 onwards, $parameters['head'] is modifiable by reference
				return 0;
			}
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}
}
