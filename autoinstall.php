<?php
/**
 * Provides automatic installation of the Forms plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
use glFusion\Database\Database;
use glFusion\Log\Log;

/** @global string $_DB_dbms */
global $_DB_dbms;
/** @global array $FRM_sampledata */
global $FRM_sampledata;

$pi_dir = dirname(__FILE__);
/** Include plugin functions */
require_once "$pi_dir/functions.inc";
/** Include database definitions */
require_once "$pi_dir/sql/{$_DB_dbms}_install.php";

/** Plugin installation options
*   @global array $INSTALL_plugin['forms']
*/
$INSTALL_plugin['forms'] = array(
    'installer' => array(
        'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),
    'plugin' => array(
        'type'      => 'plugin',
        'name'      => $_CONF_FRM['pi_name'],
        'ver'       => $_CONF_FRM['pi_version'],
        'gl_ver'    => $_CONF_FRM['gl_version'],
        'url'       => $_CONF_FRM['pi_url'],
        'display'   => $_CONF_FRM['pi_display_name'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['forms_frmdef'],
        'sql'   => $_SQL['forms_frmdef'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['forms_flddef'],
        'sql'   => $_SQL['forms_flddef'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['forms_results'],
        'sql'   => $_SQL['forms_results'],
    ),
    array(
        'type'  => 'table',
        'table' => $_TABLES['forms_values'],
        'sql'   => $_SQL['forms_values'],
    ),
    array(
        'type'  => 'group',
        'group' => 'forms Admin',
        'desc'  => 'Users in this group can administer the Forms plugin',
        'variable' => 'admin_group_id',
        'admin' => true,
        'addroot' => true,
    ),
    array(
        'type'  => 'feature',
        'feature' => 'forms.admin',
        'desc'  => 'Forms Administration access',
        'variable' => 'admin_feature_id',
    ),
    array(
        'type'  => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'admin_feature_id',
        'log'   => 'Adding Admin feature to the admin group',
    ),
);


/**
 * Puts the datastructures for this plugin into the glFusion database.
 * Note: Corresponding uninstall routine is in functions.inc.
 *
 * @return  boolean     True if successful False otherwise
 */
function plugin_install_forms()
{
    global $INSTALL_plugin, $_CONF_FRM;

    COM_errorLog("Attempting to install the {$_CONF_FRM['pi_display_name']} plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$_CONF_FRM['pi_name']]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
 * Perform post-installation functions.
 */
function plugin_postinstall_forms()
{
    global $FRM_sampledata, $_TABLES, $_CONF_FRM;

    // Install sample data
    if (is_array($FRM_sampledata)) {
        $db = Database::getInstance();
        foreach ($FRM_sampledata as $sql) {
            try {
                $db->conn->executeUpdate($sql);
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }

        // Set the correct default Group ID
        $gid = (int)$db->getItem(
            $_TABLES['groups'],
            'grp_id',
            array('grp_name' => "{$_CONF_FRM['pi_name']} Admin'")
        );
        if ($gid > 0) {
            try {
                $db->conn->executeUpdate(
                    "UPDATE {$_TABLES['forms_frmdef']} SET group_id = ?",
                    array($gid),
                    array(Database::INTEGER)
                );
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
    }

}


/**
 * Loads the configuration records for the Online Config Manager.
 *
 * @return  boolean     True = proceed with install, false = an error occured
 */
function plugin_load_configuration_forms()
{
    global $_CONF, $_CONF_FRM, $_TABLES;

    require_once __DIR__ . '/install_defaults.php';

    // Get the admin group ID that was saved previously.
    $group_id = (int)Database::getInstance()->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => "{$_CONF_FRM['pi_name']} Admin'")
    );
    return plugin_initconfig_forms($group_id);
}

