<?php
/**
 * Configuration defaults for the Custom Profile plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
 * Default settings for the Forms plugin.
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and not referenced any more once the plugin is installed
 *
 * @global array $_FRM_DEFAULT
 *
 */
/** @var global config data */
global $formsConfigData;
$formsConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'displayblocks',
        'default_value' => 3,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 13,
        'sort' => 10,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'fill_gid',
        'default_value' => 13,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0, // helper function
        'sort' => 20,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'results_gid',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0, // helper function
        'sort' => 30,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_spamx',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 40,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'use_real_ip',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 50,
        'set' => true,
        'group' => 'forms',
    ),

    // Field defaults
    array(
        'name' => 'fs_flddef',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 10,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_text_size',
        'default_value' => '25',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_text_maxlen',
        'default_value' => '255',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_textarea_rows',
        'default_value' => '5',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_textarea_cols',
        'default_value' => '60',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_calc_format',
        'default_value' => '%8.2f',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'forms',
    ),
    array(
        'name' => 'def_date_format',
        'default_value' => '%Y-%M-%d %H:%m',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'forms',
    ),
);

/**
 * Initialize Profile plugin configuration.
 *
 * Creates the database entries for the configuation if they don't already
 * exist. Initial values will be taken from $_CONF_FRM if available (e.g. from
 * an old config.php), uses $_FRM_DEFAULT otherwise.
 *
 * @param  integer $group_id   Group ID to use as the plugin's admin group
 * @return boolean             true: success; false: an error occurred
 */
function plugin_initconfig_forms($group_id = 0)
{
    global $formsConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('forms')) {
        USES_lib_install();
        foreach ($formsConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Paypal config group already exists');
    }
    return true;
}

?>
