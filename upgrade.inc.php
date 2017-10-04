<?php
/**
*   Upgrade routines for the Forms plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2014 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.2.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

// Required to get the config values
global $_CONF, $_CONF_FRM;

/**
 *  Make the installation default values available to these functions.
 */
require_once FRM_PI_PATH . '/install_defaults.php';

global $_DB_dbms;
require_once FRM_PI_PATH . "/sql/{$_DB_dbms}_install.php";

/**
*   Perform the upgrade starting at the current version.
*
*   @param  string  $current_ver    Current installed version to be upgraded
*   @return integer                 Error code, 0 for success
*/
function FRM_do_upgrade()
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

    if (!COM_checkVersion($current_ver, '0.0.5')) {
        $current_ver = '0.0.5';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_upgrade_0_0_5()) return false;
    }

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
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.2.0')) {
        $current_ver = '0.2.0';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '0.2.2')) {
        $current_ver = '0.2.2';
        COM_errorLog("Updating Plugin to $current_ver");
        if (!FRM_do_upgrade_sql($current_ver)) return false;
        if (!FRM_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, $code_ver)) {
        if (!FRM_do_set_version($code_ver)) return false;
    }
    COM_errorLog('Successfully updated the Forms plugin');
    return true;
}


/**
*   Actually perform any sql updates.
*   If there are no SQL statements, then SUCCESS is returned.
*
*   @param  string  $version    Version being upgraded TO
*   @param  array   $sql        Array of SQL statement(s) to execute
*   @return boolean     True for success, False for failure
*/
function FRM_do_upgrade_sql($version, $sql='')
{
    global $_TABLES, $_CONF_FRM, $_FRM_UPGRADE_SQL;

    // If no sql statements passed in, return success
    if (!isset($_FRM_UPGRADE_SQL[$version]) || 
            !is_array($_FRM_UPGRADE_SQL[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating Forms to version $version");
    foreach ($_FRM_UPGRADE_SQL[$version] as $sql) {
        COM_errorLOG("Forms Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Forms plugin update",1);
            return false;
        }
    }
    return true;
}


/**
*   Update the plugin version number in the database.
*   Called at each version upgrade to keep up to date with
*   successful upgrades.
*
*   @param  string  $ver    New version to set
*   @return boolean         True on success, False on failure
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


function FRM_upgrade_0_0_5()
{
    global $_TABLES, $_FRM_DEFAULT, $_CONF_FRM;

    if (!FRM_do_upgrade_sql('0.0.5')) return false;

    // Move fields to their forms. In case orphaned frmXfld records are
    // laying around, make sure we only handle actual forms.
    $sql = "SELECT id FROM {$_TABLES['forms_frmdef']}";
    //COM_errorLog($sql);
    $res1 = DB_query($sql);
    while ($Frm = DB_fetchArray($res1, false)) {
  
        $sql = "SELECT * FROM {$_TABLES['forms_frmXfld']}
            WHERE frm_id={$Frm['id']}";
        //COM_errorLog($sql);
        $res2 =  DB_query($sql);
        while ($A = DB_fetchArray($res2, false)) {

            $sql = "SELECT *
                FROM {$_TABLES['forms_flddef']}
                WHERE name='{$A['fld_name']}' AND frm_id='0'";
            //COM_errorLog($sql);
            $F = DB_fetchArray(DB_query($sql), false);
            if (empty($F)) continue;
            $ins = "INSERT INTO {$_TABLES['forms_flddef']} 
                    (frm_id, name, type, enabled, required, 
                    prompt, options, orderby)
                VALUES
                    ('{$A['frm_id']}', '" . 
                    DB_escapeString($F['name']) . "', '" .
                    DB_escapeString($F['type']) . "', 
                    '{$F['enabled']}', '{$F['required']}', '" .
                    DB_escapeString($F['prompt']) . "', '" . 
                    DB_escapeString($F['options']) . "', '{$A['orderby']}')";
            //COM_errorLog($ins);
            DB_query($ins);
            if (DB_error()) {
                COM_errorLog("SQL error in Forms 0.0.5 update: $sql");
                return false;
            }
        }

        // Update the form values table to use the field ID 
        // instead of field name
        $sql = "SELECT DISTINCT
                        d.id as frm_id, v.fld_name, v.results_id,
                        f.fld_id as new_fld_id
                    FROM {$_TABLES['forms_frmdef']} d
                    LEFT JOIN {$_TABLES['forms_results']} r 
                        ON r.frm_id=d.id 
                    LEFT JOIN {$_TABLES['forms_values']} v 
                        ON v.results_id=r.id 
                    LEFT JOIN {$_TABLES['forms_flddef']} f 
                        ON (f.name=v.fld_name and f.frm_id=r.frm_id)
                    WHERE d.id='{$Frm['id']}'";
        //COM_errorLog($sql);
        $res3 = DB_query($sql);
        while ($B = DB_fetchArray($res3, false)) {
            $sql = "UPDATE {$_TABLES['forms_values']}
                        SET fld_id='{$B['new_fld_id']}' 
                        WHERE fld_name='{$B['fld_name']}'
                        AND results_id='{$B['results_id']}'";
            //COM_errorLog($sql);
            DB_query($sql, 1);
            if (DB_error()) {
                COM_errorLog("Forms 0.0.5 update error: $sql");
                return false;
            }
        }
    }

    $sql = "ALTER TABLE gl_forms_values DROP fld_name";
    //COM_errorLog($sql);
    DB_query($sql, 1);

    // Delete any leftover, unassigned fields
    DB_delete($_TABLES['forms_flddef'], 'frm_id', 0);
    DB_query("DROP TABLE {$_TABLES['forms_frmXfld']}", 1);

    // Now add new configuration items
    $c = config::get_instance();
    if ($c->group_exists($_CONF_FRM['pi_name'])) {
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
    }
    return FRM_do_set_version('0.1.0');
}


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


function FRM_upgrade_0_1_1()
{
    global $_FRM_DEFAULT, $_CONF_FRM;

    // Add new configuration items
    $c = config::get_instance();
    if ($c->group_exists($_CONF_FRM['pi_name'])) {
        $c->add('def_calc_format', $_FRM_DEFAULT['def_calc_format'], 
                'text', 0, 2, 2, 50, true, $_CONF_FRM['pi_name']);
        $c->add('def_date_format', $_FRM_DEFAULT['def_date_format'], 
                'text', 0, 2, 2, 60, true, $_CONF_FRM['pi_name']);
    }
    return FRM_do_set_version('0.1.1');
}


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

?>
