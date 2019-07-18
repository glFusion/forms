<?php
/**
 * Public API functions for the Forms plugin for glFusion.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2010-2017 Lee Garner <lee@leegarner.com>
 * @package    forms
 * @version    v0.4.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Load the plugin configuration */
require_once $_CONF['path_system'] . 'classes/config.class.php';
$frm_config = config::get_instance();
$_CONF_FRM = $frm_config->get_config('forms');

/** Include static plugin config values */
require_once 'forms.php';

/** Define the absolute path to the plugin's directory */
define('FRM_PI_PATH', $_CONF['path'] . 'plugins/' . $_CONF_FRM['pi_name']);
/** Define the base public url for the plugin */
define('FRM_PI_URL', $_CONF['site_url'] . '/' . $_CONF_FRM['pi_name']);
/** Define the administration url for the plugin */
define('FRM_ADMIN_URL',
            $_CONF['site_admin_url'] . '/plugins/' . $_CONF_FRM['pi_name']);

define('FRM_ACTION_NONE', 0);
define('FRM_ACTION_STORE', 1);
define('FRM_ACTION_MAILOWNER', 2);
define('FRM_ACTION_MAILGROUP', 4);
define('FRM_ACTION_MAILADMIN', 8);
define('FRM_ACTION_DISPLAY', 16);
define('FRM_ACTION_MAILUSER', 32);

define('FRM_ACCESS_FILL', 1);   // access to fill out form
define('FRM_ACCESS_VIEW', 2);   // access to view results
define('FRM_ACCESS_ADMIN', 4);  // admin access

define('FRM_AUTOGEN_NONE', 0);
define('FRM_AUTOGEN_FILL', 1);
define('FRM_AUTOGEN_SAVE', 2);

// Though it's probably never necessary, these values allow access attributes
// to be combined.
define('FRM_FIELD_NORMAL', 0);
define('FRM_FIELD_REQUIRED', 1);
define('FRM_FIELD_HIDDEN', 2);
define('FRM_FIELD_READONLY', 4);

// Define flags for multiple-entry, onetime and onetime-edit forms
define('FRM_LIMIT_MULTI', 0);
define('FRM_LIMIT_EDIT', 1);
define('FRM_LIMIT_ONCE', 2);

if (file_exists(__DIR__ . '/config.php')) {
/** Include local config values and overrides */
    include_once __DIR__ . '/config.php';
}

/** Determine the correct language file, falling back to English as default. */
$langpath = __DIR__ . '/language';
$langfile = $_CONF['language'] . '.php';
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english.php';
}

/** Include plugin language file */
require_once "$langpath/$langfile";
if (file_exists("$langpath/custom/$langfile")) {
    /** Include custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Forms\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

/** Import plugin-specific functions. */
function USES_forms_functions()
{   require_once __DIR__ . '/forms_functions.inc.php';  }

/** Include service functions */
require_once __DIR__ . '/services.inc.php';


/**
 * Called by the plugin Editor to display the current plugin code version.
 * This may be different than the version installed and registered currently.
 * If newer then you may want to run the update.
 *
 * @return  string  version number
 */
function plugin_chkVersion_forms()
{
    global $_CONF_FRM;
    return $_CONF_FRM['pi_version'];
}


/**
 * This will put an option for forms administration in the command and control area.
 *
 * @return  mixed   array(title, url, icon), or false when not allowed
 */
function plugin_cclabel_forms()
{
    global $LANG_FORMS;

    if (plugin_isadmin_forms()) {
        return array($LANG_FORMS['menu_title'],
                FRM_ADMIN_URL . '/index.php',
                plugin_geticon_forms());
    } else {
        return false;
    }
}


/**
 * Check if the current user is a plugin admin.
 *
 * @see     SEC_hasRights()
 * @return  boolean     True if an admin, False if not
 */
function plugin_isadmin_forms()
{
    static $retval = NULL;
    if ($retval === NULL) {
        $retval = SEC_hasRights('forms.admin');
    }
    return $retval;
}


/**
 * Put an option for forms management on the admin-only menu.
 *
 * @return  mixed   array(title, url, num. banner), or void when not allowed
 */
function plugin_getadminoption_forms()
{
    global  $LANG_FORMS;

    if (plugin_isadmin_forms()) {
        return array ($LANG_FORMS['menu_title'],
                FRM_ADMIN_URL . '/index.php',
                );
    } else {
        return false;
    }
}


/**
 * Get the user menu option for this plugin.
 * Returns an option to administer the user's forms, if they own any.
 *
 * @return  array   Array of menu items or blank if user has no forms
 */
function plugin_getuseroption_forms()
{
    global $LANG_FORMS, $_CONF, $_CONF_FRM, $_USER, $_GROUPS, $_TABLES;

    $uid = (int)$_USER['uid'];
    if ($uid < 2) return '';
    $groups = implode(',', $_GROUPS);

    $form_cnt = (int)DB_getItem($_TABLES['forms_frmdef'], 'COUNT(*)',
                    "owner_id='{$uid}' OR group_id IN ({$groups})");
    if ($form_cnt < 1) return '';

    $menu = array($LANG_FORMS['menu_title'],
            FRM_ADMIN_URL . '/index.php?view=listforms',
            $form_cnt);
    return $menu;
}


/**
 * Update the Forms plugin.
 *
 * @return  boolean     True on success, False on failure
 */
function plugin_upgrade_forms()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return FRM_do_upgrade();
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_forms()
{
    global $_CONF;
    return FRM_PI_URL . '/images/forms.png';
}


/**
 * Automatic uninstall function for plugins.
 *
 * This code is automatically uninstalling the plugin.
 * It passes an array to the core code function that removes
 * tables, groups, features and php blocks from the tables.
 * Additionally, this code can perform special actions that cannot be
 * foreseen by the core code (interactions with other plugins for example)
 *
 * @return  array       Elements to be removed
 */
function plugin_autouninstall_forms()
{
    global $_CONF_FRM;
    $out = array (
        // give the name of the tables, without $_TABLES[]
        'tables' => array(
                'forms_frmdef',
                'forms_flddef',
                'forms_results',
                'forms_values',
        ),
        // give the full name of the group, as in the db
        'groups' => array('forms Admin'),
        // give the full name of the feature, as in the db
        'features' => array('forms.admin'),
        // give the full name of the block, including 'phpblock_', etc
        'php_blocks' => array(),
        // give all vars with their name
        'vars'=> array()
    );
    \Forms\Cache::clear();
    return $out;
}


/**
 * Get additional Javascript into the site header.
 *
 * @return  array   List of paths to Javascript files
 */
function plugin_getheaderjs_forms()
{
    return array(
        __DIR__ . '/js/toggleEnabled.js',
        __DIR__ . '/js/date.js',
        __DIR__ . '/js/ajax.js',
    );
}


/**
 * Get additional CSS into the site header.
 * Checks first for a layout-specific version, then a custom plugin version,
 * and finally the default if nothing else found.
 *
 * @return  array   List of paths to Javascript files
 */
function plugin_getheadercss_forms()
{
    global $_CONF, $_CONF_FRM;

    $styles = array();
    $files = array('style.css');

    $layoutpath = $_CONF['path_layout'].'plugins/'.$_CONF_FRM['pi_name'].'/';
    foreach ($files as $file) {
        if (@file_exists($layoutpath . $file)) {
            // check for custom layout first
            $styles[] = $layoutpath . $file;
        } elseif (@file_exists(__DIR__ . '/css/custom/' . $file)) {
            // then custom plugin style
            $styles[] = __DIR__ . '/css/custom/' . $file;
        } else {
            // finally use the default plugin style
            $styles[] = __DIR__ . '/css/' . $file;
        }
    }
    return $styles;
}


/**
 * Returns a name=>value array of options broken out from the given string.
 *
 * @param   mixed   $str    Array, or serialized array, of options
 * @return  array           Associative array of option=>value pairs
 */
function FRM_getOpts($str)
{
    $A = array();
    if (is_string($str)) {
        // convert to an array
        $A = @unserialize($str);
        if (!$A)
            $A = array();
    } elseif (is_array($str)) {
        // already an array, return unchanged
        $A = $str;
    } else {
        // invalid argument
        $A = NULL;
    }
    return $A;
}


/**
 * Serializes an array of options and returns the resulting string.
 * An empty string is returned on error.
 * Options must already be validated for the type of record to be saved.
 *
 * @param   array   $A      Array of name=>value pairs
 * @return  string          Serialized string representing the array
 */
function FRM_setOpts($A)
{
    $str = '';
    if (is_array($A)) {
        $str = @serialize($A);
        if (!$str) $str = '';
    }
    return $str;
}


/**
 * Implements autotags for forms.
 *
 * @param   string  $op         Name of operation
 * @param   string  $content    Content of the autotag
 * @param   string  $autotag    Complete autotag
 * @return  string              HTML replacement
 */
function plugin_autotags_forms($op, $content = '', $autotag = '')
{
    global $_TABLES, $LANG_FORMS;

    if ($op == 'tagname' ) {
        return array('forms', 'formlink');
    } else if ($op == 'parse') {
        $retval = $content;
        $HTML = '';
        $frm_id = '';
        $template = '';
        $popup = false;
        // default to false to control admin true/false in results table
        $fldnames = false;
        $target = '_self';
        $instance_id = '';

        switch ($autotag['tag']) {
        case 'forms':
            $option = COM_applyFilter($autotag['parm1']);
            $px = explode(' ', trim($autotag['parm2']));
            $skip = 0;
            if (is_array($px)) {
                foreach($px as $part) {
                    $A = explode(':', $part);
                    $parm = $A[0];
                    $val = isset($A[1]) ? $A[1] : NULL;
                    switch ($parm) {
                    case 'form':
                    case 'id':
                        $frm_id = COM_sanitizeID($val);
                        $skip++;
                        break;
                    case 'fields':
                        $fldnames = explode(',', $val);
                        $skip++;
                        break;
                    case 'template':
                        $template = $val;
                        $skip++;
                        break;
                    case 'target':      // for the "link" option
                        $target = htmlspecialchars($val);
                        $skip++;
                        break;
                    case 'instance':
                        $instance_id = $val;
                        $skip++;
                        break;
                    case 'popup':
                        // "popup:true" or just "popup" to get a popup form
                        $popup = empty($val) ? true : $val == true ? true : false;
                        $skip++;
                        break;
                    case 'value':
                        $fld_value = $val;
                        $skip++;
                        break;
                    case 'name':
                        $fld_name = $val;
                        $skip++;
                        break;
                    case 'check':
                        $is_default = true;
                        break;
                     }
                }

                // Any leftover parts become the caption.  Borrowed from
                // Mediagallery's caption handling.
                if ($skip > 0) {
                    if (count($px) > $skip) {
                        for ($i = 0; $i < $skip; $i++) {
                            // Skip to the end of the processed directives
                            array_shift($px);
                        }
                        $caption = trim(implode(' ', $px));
                    } else {
                        $caption = '';
                    }
                } else {
                    // There weren't any control parameters, so all of parm2 is
                    // the caption.
                    $caption = trim($autotag['parm2']);
                }
            }

            switch ($option) {
            case 'show':
                if ($frm_id != '') {
                    // Don't insert a form into an e-mailed article.
                    if (isset($_POST['what']) && $_POST['what'] == 'sendstory') {
                        break;
                    }

                    $F = new \Forms\Form($frm_id, FRM_ACCESS_FILL);
                    $F->setInstance($instance_id);
                    if ($F->enabled) {
                        $HTML = $F->Render($template);
                    } else {
                        $HTML = $F->noaccess_msg;
                    }
                }
                break;

            case 'results':
                if ($frm_id != '') {
                    USES_forms_functions();
                    $HTML = \Forms\FRM_ResultsTable($frm_id, $fldnames, $instance_id);
                }
                break;

            case 'link':
                if ($frm_id != '') {
                    $url = FRM_PI_URL . '/index.php?frm_id=' . $frm_id;
                    if (!empty($instance_id))
                        $url .= '&instance_id=' . urlencode($instance_id);
                    if (empty($caption)) {
                        $caption = DB_getItem($_TABLES['forms_frmdef'], 'name',
                                "id='$frm_id'");
                    }
                    $opts = array(
                        'title' => $caption,
                        'target' => $target,
                    );
                    if ($popup) {
                        $url .= '&modal=x';
                        $opts['onclick'] = "popupWindow('$url', '', 640, 480, 1); return false;";
                        //$opts['data-uk-lightbox'] = '';
                        //$url = '';
                    }
                    // Need a caption for the link text
                    if (!empty($caption)) {
                        $HTML = COM_createLink($caption, $url, $opts);
                    }
                }
                break;

            case 'radio':       // radio button field
                if (!empty($fld_value)) {
                    if (empty($fld_name)) $fld_name = 'radio1';
                    //$F = new \Forms\Field_autotag('radio', $fld_name, $fld_value);
                    $F = new \Forms\Fields\autotag('radio', $fld_name, $fld_value);
                    $chk = isset($is_default) ? true : false;
                    $HTML = $F->Render($chk);
                }
                break;

            case 'checkbox':
                if (!empty($fld_name)) {
                    if (!isset($fld_value) || empty($fld_value)) {
                        $fld_value = 1;
                    }
                    //$F = new \Forms\Field_autotag('checkbox', $fld_name, $fld_value);
                    $F = new \Forms\Fields\autotag('checkbox', $fld_name, $fld_value);
                    $chk = isset($is_default) ? true : false;
                    $HTML = $F->Render($chk);
                }
                break;

            }   // switch ($option)
            break;

        case 'formlink':    // DEPRECATED
            $frm_id = COM_sanitizeID($autotag['parm1']);
            if (!empty($frm_id)) {  // contains the form ID
                // Create a link to the form's page using either the form's
                // title or a user-supplied string
                $url = COM_buildUrl(FRM_PI_URL . '/index.php?frm_id=' . $frm_id);
                $caption = $autotag['parm2'];
                if (empty($caption)) {
                    $caption = DB_getItem($_TABLES['forms_frmdef'], 'name',
                                "id='$frm_id'");
                }
                // Have to have a caption, otherwise there's no text to show
                if (!empty($caption)) {
                    $HTML = COM_createLink($caption, $url,
                                    array('title'=>$caption));
                }
            }
            break;

        }   // switch($autotag['tag'])

        // Perform the text substitution with the final version of $HTML
        $content = str_replace($autotag['tagstr'], $HTML, $content);

    }   // if op == 'parse'
    return $content;
}


/**
 * Get the URL to the localized configuration help file.
 *
 * @param   string  $option     ID of help field
 * @param   string  $doclang    Desired language for document
 * @return  array   Array of (url, window_type)
 */
function plugin_getconfigelementhelp_forms($option, $doclang='english')
{
    if (version_compare(GVERSION, '1.6.0', '<')) {
        $file = 'config.legacy.html';
    } else {
        $file = 'config.html';
    }
    $doc_url = FRM_getDocURL($file, $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }

    return array($doc_url, 0);
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @see     plugin_getconfigelementhelp_subscription()
 * @param   string  $file       Optional HTML filename
 * @param   string  $doclang    Documentation language, defaults to english
 * @return  string              URL to document directory or file
 */
function FRM_getDocURL($file = '', $doclang='')
{
    global $_CONF, $_CONF_FRM;

    if ($doclang == '') $doclang = $_CONF['language'];
    $doc_path = $_CONF['path_html'] . $_CONF_FRM['pi_name'] .
                "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = FRM_PI_URL . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = FRM_PI_URL . "/docs/$doclang/";
    }

    return $doc_url;
}


/**
 * Select group for "default user group" config variable.
 *
 * @uses    FRM_GetGroups()
 * @return  array   Array of groups
 */
function plugin_configmanager_select_fill_gid_forms()
{
    global $_CONF_FRM;
    return FRM_GetGroups($_CONF_FRM['fill_gid']);
}


/**
 * Select groups for "default results group" config variable.
 *
 * @uses    FRM_GetGroups()
 * @return  array       Array of groups
 */
function plugin_configmanager_select_results_gid_forms()
{
    global $_CONF_FRM;
    return FRM_GetGroups($_CONF_FRM['results_gid']);
}


/**
 * Support merging local accounts into remote accounts,
 *
 * @param  integer $origUID    Original (remote) user ID
 * @param  integer $destUID    Merged (local) user ID
 */
function plugin_user_move_forms($origUID, $destUID)
{
    global $_TABLES;

    $origUID = (int)$origUID;
    $destUID = (int)$destUID;

    DB_query("UPDATE {$_TABLES['forms_results']}
            SET uid = $destUID WHERE uid = $origUID", 1);
}


/**
 * Returns list of moderation values.
 *
 * The array returned contains (in order): the row 'id' label, main plugin
 * table, moderation fields (comma seperated), and plugin submission table
 *
 * @return  array   Returns array of useful moderation values
 */
function plugin_moderationvalues_forms()
{
    global $_TABLES;

    return array(
        'res_id',
        $_TABLES['forms_results'],
        '',
        '',
    );
}


/**
 * Performs plugin exclusive work for items approved by moderation.
 * Notifies users of approval/denial, and subscribers if approved.
 *
 * @param   string  $id         ID of the ad
 * @return  string              Empty string.
 */
function plugin_moderationapprove_forms($id)
{
    \Forms\Result::Approve($id);
    return '';
}


/**
 * Performs plugin exclusive work for items deleted by moderation.
 *
 * @param   string  $res_id     ID of the deleted result submission.
 * @return  string          Any wanted HTML output
 */
function plugin_moderationdelete_forms($res_id)
{
    global $_TABLES;

    $res_id = (int)$res_id;
    $sql = "DELETE FROM {$_TABLES['forms_values']}
            WHERE results_id = $res_id";
    DB_query($sql);
    DB_delete($_TABLES['forms_results'], 'id', $res_id);
    return '';
}


/**
 * Show items for moderation on submissions page.
 *
 * Uses the Plugin class to return data required by moderation.php to list
 * plugin objects that need to be moderated.
 *
 * @return  object  Plugin object containing submission data
 */
function plugin_itemlist_forms()
{
    global $_TABLES, $LANG_FORMS;

    if (plugin_isadmin_forms()) {
        $plugin = new Plugin();
        $plugin->submissionlabel = $LANG_FORMS['menu_title'];
        $plugin->getsubmissionssql = "SELECT
                    res.id, frm.name as subject,
                    FROM_UNIXTIME(res.dt) as date,
                    'n/a', res.uid
                FROM {$_TABLES['forms_results']} res
                LEFT JOIN {$_TABLES['forms_frmdef']} frm
                    ON res.frm_id = frm.id
                WHERE approved = 0";
        $plugin->addSubmissionHeading('Form Name');
        $plugin->addSubmissionHeading('Date');
        return $plugin;
    }
}


/**
 * Perform actions when a user is deleted
 * - Delete result sets submitted by the user
 *
 * @param   integer $uid    User ID
 */
 function plugin_user_deleted_forms($uid)
{
    global $_TABLES;

    $uid = (int)$uid;
    $sql = "SELECT id FROM {$_TABLES['forms_results']}
            WHERE uid = '$uid'";
    $res = DB_query($sql);
    $A = DB_fetchAll($res, false);
    foreach ($A as $id=>$res) {
        \Forms\Result::Delete($res['id']);
    }
}


/**
 * Export user data when requested.
 *
 * @param   integer $uid        User ID
 * @param   string  $email      User Email Address (not used)
 * @param   string  $username   User Name (not used)
 * @param   string  $ip         IP address (not used)
 * @return  string              XML-formatted data
 */
function plugin_privacy_export_forms($uid, $email='', $username='', $ip='')
{
    global $_TABLES, $_USER, $_CONF_FRM;

    $retval = '';
    $sql = "SELECT id FROM {$_TABLES['forms_results']} WHERE uid = '$uid'";
    $res = DB_query($sql);
    $A = DB_fetchAll($res, false);
    foreach ($A as $id=>$res_id) {
        $R = new \Forms\Result($res_id['id']);
        $F = \Forms\Form::getInstance($R->frm_id);
        $R->GetValues($F->fields);
        $form_name = str_replace(' ', '_', $F->name);
        $retval .= "<$form_name>\n";
        $dt = new Date($R->dt, $_USER['tzid']);
        $retval .= '<date>' . $dt->format($dt->getUserFormat(),true) . "</date>\n";
        $retval .= "<ip>{$R->ip}</ip>\n";
        foreach ($F->fields as $fld) {
            $retval .= $fld->XML();
        }
        $retval .= "</$form_name>\n";
    }
    if (!empty($retval)) {
        $retval = "<{$_CONF_FRM['pi_name']}>\n$retval</{$_CONF_FRM['pi_name']}>\n";
        if (function_exists('tidy_repair_string')) {
            $retval = tidy_repair_string($retval, array('input-xml' => 1));
        }
    }
    return $retval;
}


/**
 * Enable service functions.
 *
 * @return  boolean     True to enable web service functions
 */
function plugin_wsEnabled_forms()
{
    return true;
}


/**
 * Create an array of groups.
 *
 * @param   integer $selected   ID of selected group
 * @return  array               Array of groups as name=>ID
 */
function FRM_GetGroups($selected=0)
{
    global $_TABLES;

    $A = array();

    $query = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}";
    $result = DB_query($query, 1);
    if ($result && DB_numRows($result) > 0) {
        while ($row = DB_fetchArray($result)) {
            $row['grp_name'] = htmlspecialchars($row['grp_name']);
            $A[$row['grp_name']] = $row['grp_id'];
        }
    }
    return $A;
}


/**
 * Convert the hour from 12-hour time to 24-hour.
 * This is meant to convert incoming values from forms to 24-hour format.  If
 * the site uses 24-hour time, the form values should already be that way
 * (and there will be no am/pm indicator), so the hour is returned unchanged.
 *
 * @param   integer $hour   Hour to check (0 - 23)
 * @param   string  $ampm   Either 'am' or 'pm'
 * @return  integer         Hour after switching it to 24-hour time.
 */
function FRM_12to24($hour, $ampm='')
{
    global $_CONF;

    $hour = (int)$hour;

    if ($hour < 0 || $hour > 23) $hour = 0;
    if ($_CONF['hour_mode'] == 24) return $hour;

    if ($ampm == 'am' && $hour == 12) $hour = 0;
    if ($ampm == 'pm' && $hour < 12) $hour += 12;

    return $hour;
}

?>
