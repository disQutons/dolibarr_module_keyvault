<?php
/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *    \file       key_card.php
 *    \ingroup    keyvault
 *    \brief      Page to create/edit/view key
 */


//FBR récupération des erreurs php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSESSION'))                define('NOSESSION', '1');						// On CLI mode, no need to use web sessions
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)


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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
dol_include_once('/keyvault/class/key.class.php');
dol_include_once('/keyvault/lib/keyvault_key.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("keyvault@keyvault", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOSTINT('lineid');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

// Initialize a technical objects
$object = new Key($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->keyvault->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array
$soc = null;

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);


$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = getDolGlobalInt('KEYVAULT_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('keyvault', 'key', 'read');
	$permissiontoadd = $user->hasRight('keyvault', 'key', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('keyvault', 'key', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('keyvault', 'key', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('keyvault', 'key', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->keyvault->multidir_output[isset($object->entity) ? $object->entity : 1].'/key';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled($object->module)) {
	accessforbidden("Module ".$object->module." not enabled");
}
if (!$permissiontoread) {
	accessforbidden();
}

// Vérification des droits de l'utilisateur
if ($id > 0) {
    $object = new Key($db);
    $object->fetch($id);

    $accessAllowed = false;
	

    // Vérification des utilisateurs autorisés
    if (!empty($object->rights_user)) {
        $userIds = explode(',', $object->rights_user);
        if (in_array($user->id, $userIds)) {
            $accessAllowed = true;
        }
    }

    // Vérification des groupes autorisés
    if (!$accessAllowed && !empty($object->rights_group)) {
        $groupIds = explode(',', $object->rights_group);

        // Instanciation de UserGroup pour appeler listGroupsForUser
        $userGroup = new UserGroup($db);
        $userGroups = $userGroup->listGroupsForUser($user->id);

        if (is_array($userGroups)) {
            foreach ($groupIds as $groupId) {
                if (array_key_exists($groupId, $userGroups)) {
                    $accessAllowed = true;
                    break;
                }
            }
        } else {
            dol_syslog("Erreur lors de la récupération des groupes de l'utilisateur.", LOG_ERR);
        }
    }

    // Accès refusé si non autorisé
    if (!$accessAllowed) {
        accessforbidden($langs->trans("UnauthorizedAccess"), 0, 0, 0);
        exit;
    }
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/keyvault/key_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/keyvault/key_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'KEYVAULT_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Traitement des groupes autorisés
	if (GETPOSTISSET('rights_group')) {
		$object->setAuthorizedGroups(GETPOST('rights_group', 'array'));
	}
	// Traitement des utilisateurs autorisés
	if (GETPOSTISSET('rights_user')) {
		$object->setAuthorizedUsers(GETPOST('rights_user', 'array'));
	}

	if ($action == 'add' || $action == 'update') {
		// If form submitted for add/update: set values from posted fields.
		// If the select is empty, the browser may not send the param, so explicitly clear the value.
		if (isset($_POST['usergroup'])) {
			$object->rights_group = implode(',', $_POST['usergroup']);
		} else {
			$object->rights_group = '';
		}
		if (isset($_POST['userlist'])) {
			$object->rights_user = implode(',', $_POST['userlist']);
		} else {
			$object->rights_user = '';
		}
	}
	
	/*if ($action == 'create' || $action == 'edit') {
		$usergroup = GETPOST('usergroup', 'array');
		$object->rights_group = implode(',', $usergroup);
	}*/
	
	// Avant d'appeler la routine standard d'ajout/mise à jour, chiffrer le mot de passe provenant du formulaire
	if (GETPOSTISSET('pass')) {
		// Récupérer la valeur brute envoyée (utilise $_POST directement pour ne pas altérer les caractères)
		$plainPass = isset($_POST['pass']) ? $_POST['pass'] : GETPOST('pass', 'alpha');
		if ($plainPass !== null && $plainPass !== '') {
			$enc = dolEncrypt($plainPass);
			if (!empty($enc)) {
				// Remplacer $_POST afin que la logique d'enregistrement utilise la valeur chiffrée
				$_POST['pass'] = $enc;
				// Mettre aussi à jour l'objet si le code utilise $object->pass
				$object->pass = $enc;
			}
		} else {
			// Si le champ est vide dans le formulaire, s'assurer qu'il soit vidé en base
			$_POST['pass'] = '';
			$object->pass = '';
		}
	}
	
	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', null, 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'KEYVAULT_MYOBJECT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid = 'key'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Key")." - ".$langs->trans('Card');
//$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("Key"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-keyvault page-card');

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($title, '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');


	print '<table class="border centpercent tableforfieldcreate">'."\n";

	
	unset($object->fields['rights_group']);
	unset($object->fields['rights_user']);
	
	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	// Ajout des groupes d'utilisateurs autorisés
	if (!empty($object->rights_group)) {
		$usergroup = explode(',', $object->rights_group);
	} else {
		$usergroup = array();
	}
	// Ajout des utilisateurs autorisés
	if (!empty($object->rights_user)) {
		$userlist = explode(',', $object->rights_user);
	} else {
		$userlist = array();
	}
	
	print '<tr><td>'.$langs->trans('Groupes d\'utilisateurs autorisés').'</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print img_object('', 'group', 'class="paddingrightonly"');
	print $form->select_dolgroups($usergroup, 'usergroup', 1, '', !$permissiontoadd, '', array(), '0', true, 'minwidth100 maxwidth250 widthcentpercentminusx');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Utilisateurs autorisés').'</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print img_object('', 'user', 'class="paddingrightonly"');
	print $form->select_dolusers($userlist, 'userlist', 1, '', !$permissiontoadd, '', '1', '0', '0', '0','','0','', 'minwidth100 maxwidth500 widthcentpercentminusx','','','1','');
	print '</td></tr>';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Key"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Décrypter le mot de passe pour affichage et copy-to-clipboard si nécessaire
	if (!empty($object->pass)) {
		$object->pass = dolDecrypt($object->pass);
	}

	unset($object->fields['rights_group']);
	unset($object->fields['rights_user']);

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	// Ajout des utilisateurs et groupes autorisés	
	$usergroup = !empty($object->rights_group) ? explode(',', $object->rights_group) : array();
	$userlist = !empty($object->rights_user) ? explode(',', $object->rights_user) : array();

	print '<tr>';
	print '<td>'.$langs->trans('Groupes d\'utilisateurs autorisés').'</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print img_object('', 'group', 'class="paddingrightonly"');
	print $form->select_dolgroups($usergroup, 'usergroup', 1, '', 0, '', array(), '0', true, 'minwidth100 maxwidth250 widthcentpercentminusx');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Utilisateurs autorisés').'</td>';
	print '<td colspan="3" class="maxwidthonsmartphone">';
	print img_object('', 'user', 'class="paddingrightonly"');
	print $form->select_dolusers($userlist, 'userlist', 1, '', !$permissiontoadd, '', '1', '0', '0', '0','','0','', 'minwidth100 maxwidth500 widthcentpercentminusx','','','1','');
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	// Décrypter le mot de passe pour affichage et copy-to-clipboard si nécessaire
	if (!empty($object->pass)) {
		$object->pass = dolDecrypt($object->pass);
	}

	$head = keyPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Key"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteKey'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/keyvault/key_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '<span>'.$object->label.'</span>';

	// TODO Thirdparty
	/*$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
	if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/custom/keyvault/key_list.php?search_fk_soc='.$object->thirdparty->id.'">'.$langs->trans("OthersKeys").'</a>)';
	}*/
	
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fk_categ';	// We change column just before this field
	//unset($object->fileds['fk_categ']);
	//unset($object->fields['fk_soc']);
	unset($object->fields['rights_group']);
	unset($object->fields['rights_user']);

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	
	// TODO Récupération de la catégorie
	/*if (!empty($object->fk_categ)) {
		$object->category->fetch($object->fk_categ);
		$categoryName = $object->category->name;
	} else {
		$categoryName = $langs->trans("None");
	}*/
	
	// Récupération des groupes autorisés
	if (!empty($object->rights_group)) {
		$groupIds = explode(',', $object->rights_group);
		$groupLinks = array();
		foreach ($groupIds as $groupId) {
			$group = new UserGroup($db);
			$group->fetch($groupId);
			// Construction du lien vers la fiche du groupe
			$groupLink = '<a href="'.DOL_URL_ROOT.'/custom/keyvault/key_list.php?search_rights_group='.$group->nom.'">'.$group->nom.'</a>';
			$groupLinks[] = $groupLink;
		}
		$groupDisplay = implode(', ', $groupLinks);
	} else {
		$groupDisplay = $langs->trans("None");
	}

	// Récupération des utilisateurs autorisés
	if (!empty($object->rights_user)) {
		$userIds = explode(',', $object->rights_user);
		$userLinks = array();
		foreach ($userIds as $userId) {
			$user = new User($db);
			$user->fetch($userId);
			// Construction du lien vers la fiche de l'utilisateur
			$userLink = '<a href="'.DOL_URL_ROOT.'/custom/keyvault/key_list.php?search_rights_user='.$user->getFullName($langs).'">'.$user->getFullName($langs).'</a>';
			$userLinks[] = $userLink;
		}
		$userDisplay = implode(', ', $userLinks);
	} else {
		$userDisplay = $langs->trans("None");
	}


	// Affichage dans la vue
	print '<tr><td>'.$langs->trans("AuthorizedGroups").'</td>';
	print '<td>'.$groupDisplay.'</td></tr>';

	print '<tr><td>'.$langs->trans("Utilisateurs autorisés").'</td>';
	print '<td>'.$userDisplay.'</td></tr>';
 	
	print '</table>';
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			// Modify
			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}
			
			// TODO Disable / Enable
			/*if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}*/
			

			// Delete (with preloaded confirm popup)
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}
}

// End of page
llxFooter();
$db->close();
