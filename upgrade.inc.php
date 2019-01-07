<?php
/**
 * Upgrade routines for the Forms plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

/** Required to get the config values */
global $_CONF, $_CONF_FRM;

/** Include SQL definitions */
require_once __DIR__ . "/sql/mysql_install.php";

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if development update to ignore errors
 * @return  boolean         True for success, False on failure
 */
function FRM_do_upgrade($dvlp=false)
{
    global $_CONF_FRM, $_PLUGIN_INFO;

    if (isset($_PLUGIN_INFO[$_CONF_FRM['pi_name']])) {
        if (is_array($_PLUGIN_INFO[$_CONF_FRM['pi_name']])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[$_CONF_FRM['pi_name']]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$_CONF_FRM['pi_name']];
        }
    } else {
        return false;
    }
    $code_ver = plugin_chkVersion_forms();

    if (!COM_checkVersion($current_ver, '0.1.0')) {
        $current_ver = '0.1.0';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_upgrade_0_1_0()) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.1')) {
        $current_ver = '0.1.1';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_upgrade_0_1_1()) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.2')) {
        $current_ver = '0.1.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.3')) {
        $current_ver = '0.1.3';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.5')) {
        $current_ver = '0.1.5';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.6')) {
        $current_ver = '0.1.6';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.7')) {
        $current_ver = '0.1.7';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_upgrade_0_1_7()) return false;
    }

    if (!COM_checkVersion($current_ver, '0.1.8')) {
        $current_ver = '0.1.8';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!FRM_do_set_version($current_ver, $dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.2.0')) {
        $current_ver = '0.2.0';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!FRM_do_set_version($current_ver, $dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.2.2')) {
        $current_ver = '0.2.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!FRM_do_set_version($current_ver, $dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.3.1')) {
        $current_ver = '0.3.1';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!FRM_do_set_version($current_ver, $dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.4.0')) {
        $current_ver = '0.4.0';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!FRM_do_set_version($current_ver, $dvlp)) return false;
    }

    // Final version setting and cleanup
    if (!COM_checkVersion($current_ver, $code_ver)) {
        if (!FRM_do_set_version($code_ver)) return false;
    }

    // Update the configuration items
    global $formsConfigData;
    require_once __DIR__ . '/install_defaults.php';
    USES_lib_install();
    _update_config('forms', $formsConfigData);

    FRM_remove_old_files();
    \Forms\Cache::clear();
    COM_errorLog('Successfully updated the Forms plugin to ' . $code_ver);
    return true;
}


/**
 * Actually perform any sql updates.
 * If there are no SQL statements, then SUCCESS is returned.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $dvlp       True to ignore SQL errors
 * @return  boolean     True for success, False for failure
 */
function FRM_do_upgrade_sql($version, $dvlp=false)
{
    global $_TABLES, $_CONF_FRM, $_FRM_UPGRADE_SQL;

    // If no sql statements passed in, return success
    if (!isset($_FRM_UPGRADE_SQL[$version]) || 
            !is_array($_FRM_UPGRADE_SQL[$version])) {
            return true;
    }

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating Forms to version $version");
    $errmsg = 'SQL Error during Forms plugin update';
    if ($dvlp) $errmsg .= ' - ignored';
    foreach ($_FRM_UPGRADE_SQL[$version] as $sql) {
        COM_errorLOG("Forms Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog($errmsg, 1);
            if (!$dvlp) return false;
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function FRM_do_set_version($ver)
{
    global $_TABLES, $_CONF_FRM;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '{$_CONF_FRM['pi_version']}',
            pi_gl_version = '{$_CONF_FRM['gl_version']}',
            pi_homepage = '{$_CONF_FRM['pi_url']}'
        WHERE pi_name = '{$_CONF_FRM['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_CONF_FRM['pi_display_name']} Plugin version",1);
        return false;
    } else {
        return true;
    }
}


/**
 * Update to 0.1.0
 * - Changes the format of values in the field definition
 *
 * @return  boolean     True on success, False on failure
 */
function FRM_upgrade_0_1_0()
{
    global $_TABLES, $_FRM_DEFAULT, $_CONF_FRM;

    // Switch method of storing values
    $sql = "SELECT * FROM {$_TABLES['forms_flddef']}
                WHERE type in ('multicheck', 'radio', 'select')";
    $res = DB_query($sql);
    while ($F = DB_fetchArray($res, false)) {
        COM_errorLog("Processing field {$F['name']}");
        $options = unserialize($F['options']);
        if (!$options) {
            $options = array();
        } else {
            if (is_array($options['values'])) {
                // Update existing values with new text value
                $sql1 = "SELECT * FROM {$_TABLES['forms_values']}
                        WHERE fld_id = '{$F['fld_id']}'";
                $res1 = DB_query($sql1);
                while ($V = DB_fetchArray($res1, false)) {
                    $value = isset($options['values'][$V['value']]) ?
                        $options['values'][$V['value']] : '';
                    $upd_sql = "UPDATE {$_TABLES['forms_values']}
                            SET value='" . DB_escapeString($value) . "'
                            WHERE id='{$V['id']}'";
                    DB_query($upd_sql);
                }

                // Now update the field definitions with the new value format
                $new_values = array();
                foreach ($options['values'] as $value=>$text) {
                    $new_values[] = $text;
                }
                // Get the text version of the value
                $default = isset($options['values'][$options['default']]) ?
                    $options['values'][$options['default']] : '';
                if ($F['type'] == 'multicheck') {
                    $default = array($default);
                }
                $options['default'] = $default;
                $options['values'] = $new_values;
                $new_opts = serialize($options);
                $upd_sql = "UPDATE {$_TABLES['forms_flddef']}
                        SET options = '" . DB_escapeString($new_opts) . "'
                        WHERE fld_id='{$F['fld_id']}'";
                DB_query($upd_sql);
            }
        }
    }

    if (!FRM_do_upgrade_sql('0.1.0')) return false;
    return FRM_do_set_version('0.1.0');
}


/**
 * Update to version 0.1.7.
 * - Set field group access to match the form
 *
 * @return  boolean     True on success, False on failure
 */
function FRM_upgrade_0_1_7()
{
    global $_TABLES;

    if (!FRM_do_upgrade_sql('0.1.7')) return false;

    // Update the new field group ID's to match the forms
    $sql = "SELECT id, fill_gid, results_gid FROM {$_TABLES['forms_frmdef']}";
    $res = DB_query($sql, 1);
    while ($A = DB_fetchArray($res, false)) {
        DB_query("UPDATE {$_TABLES['forms_flddef']} SET
                fill_gid = {$A['fill_gid']},
                results_gid = {$A['results_gid']}
            WHERE frm_id = '{$A['id']}'", 1);
        if (DB_error()) return false;
    }
    return FRM_do_set_version('0.1.7');
}


/**
 * Remove deprecated files
 */
function FRM_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/forms
        __DIR__ => array(
            // Deprecated 0.4.0.
            'classes/Field_autotag.class.php',
            'classes/Field_calc.class.php',
            'classes/Field_checkbox.class.php',
            'classes/Field_date.class.php',
            'classes/Field_dateclass.php',
            'classes/Field_hidden.class.php',
            'classes/Field_multicheck.class.php',
            'classes/Field_numeric.class.php',
            'classes/Field_radio.class.php',
            'classes/Field_select.class.php',
            'classes/Field_static.class.php',
            'classes/Field_textarea.class.php',
            'classes/Field_text.class.php',
            'classes/Field_time.class.php',
            'templates/admin/editfield.uikit.thtml',
            'templates/admin/editform.uikit.thtml',
            'templates/form.uikit.thtml',
        ),
        // public_html/forms
        $_CONF['path_html'] . 'forms' => array(
        ),
        // admin/plugins/forms
        $_CONF['path_html'] . 'admin/plugins/forms' => array(
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
}

?>
