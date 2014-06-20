<?php
/**
*   Configuration defaults for the Custom Profile plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
 *  Default settings for the Forms plugin.
 *
 *  Initial Installation Defaults used when loading the online configuration
 *  records. These settings are only used during the initial installation
 *  and not referenced any more once the plugin is installed
 *
 *  @global array $_FRM_DEFAULT
 *
 */
global $_FRM_DEFAULT, $_CONF_FRM;
$_FRM_DEFAULT = array();

// Set the default permissions
$_FRM_DEFAULT['fill_gid'] = 13;      // logged-in users
$_FRM_DEFAULT['results_gid'] = 1;
$_FRM_DEFAULT['displayblocks'] = 3;     // show left & right blocks

// Default values for field definitions
$_FRM_DEFAULT['def_text_size'] = 25;
$_FRM_DEFAULT['def_text_maxlen'] = 255;
$_FRM_DEFAULT['def_textarea_rows'] = 5;
$_FRM_DEFAULT['def_textarea_cols'] = 60;
$_FRM_DEFAULT['def_date_format'] = '%Y-%M-%d %H:%m';
$_FRM_DEFAULT['def_calc_format'] = '%8.2f';

/**
 *  Initialize Profile plugin configuration
 *
 *  Creates the database entries for the configuation if they don't already
 *  exist. Initial values will be taken from $_CONF_FRM if available (e.g. from
 *  an old config.php), uses $_FRM_DEFAULT otherwise.
 *
 *  @param  integer $group_id   Group ID to use as the plugin's admin group
 *  @return boolean             true: success; false: an error occurred
 */
function plugin_initconfig_forms($group_id = 0)
{
    global $_CONF, $_CONF_FRM, $_FRM_DEFAULT;

    if (is_array($_CONF_FRM) && (count($_CONF_FRM) > 1)) {
        $_FRM_DEFAULT = array_merge($_FRM_DEFAULT, $_CONF_FRM);
    }

    // Use configured default if a valid group ID wasn't presented
    if ($group_id == 0)
        $group_id = $_FRM_DEFAULT['group_id'];

    $c = config::get_instance();

    if (!$c->group_exists($_CONF_FRM['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, $_CONF_FRM['pi_name']);

        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true, $_CONF_FRM['pi_name']);
        $c->add('displayblocks', $_FRM_DEFAULT['displayblocks'],
                'select', 0, 0, 13, 10, true, $_CONF_FRM['pi_name']);
        $c->add('fill_gid', $_FRM_DEFAULT['fill_gid'],
                'select', 0, 0, 0, 20, true, $_CONF_FRM['pi_name']);
        $c->add('results_gid', $_FRM_DEFAULT['results_gid'],
                'select', 0, 0, 0, 30, true, $_CONF_FRM['pi_name']);

        $c->add('fs_flddef', NULL, 'fieldset', 0, 2, NULL, 0, true, 
                $_CONF_FRM['pi_name']);
        $c->add('def_text_size', $_FRM_DEFAULT['def_text_size'], 
                'text', 0, 2, 2, 10, true, $_CONF_FRM['pi_name']);
        $c->add('def_text_maxlen', $_FRM_DEFAULT['def_text_maxlen'], 
                'text', 0, 2, 2, 20, true, $_CONF_FRM['pi_name']);
        $c->add('def_textarea_rows', $_FRM_DEFAULT['def_textarea_rows'], 
                'text', 0, 2, 2, 30, true, $_CONF_FRM['pi_name']);
        $c->add('def_textarea_cols', $_FRM_DEFAULT['def_textarea_cols'], 
                'text', 0, 2, 2, 40, true, $_CONF_FRM['pi_name']);
        $c->add('def_calc_format', $_FRM_DEFAULT['def_calc_format'], 
                'text', 0, 2, 2, 50, true, $_CONF_FRM['pi_name']);
        $c->add('def_date_format', $_FRM_DEFAULT['def_date_format'], 
                'text', 0, 2, 2, 60, true, $_CONF_FRM['pi_name']);
    }

    return true;
}

?>
