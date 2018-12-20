<?php
/**
 * Table definitions and other static config variables.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 * Global array of table names from glFusion.
 * @global array $_TABLES
 */
global $_TABLES;

/**
 * Global table name prefix.
 * @global string $_DB_table_prefix
 */
global $_DB_table_prefix;

$_TABLES['forms_frmdef']    = $_DB_table_prefix . 'forms_frmdef';
$_TABLES['forms_flddef']    = $_DB_table_prefix . 'forms_flddef';
$_TABLES['forms_results']   = $_DB_table_prefix . 'forms_results';
$_TABLES['forms_values']    = $_DB_table_prefix . 'forms_values';

// Deprecated version 0.0.5
$_TABLES['forms_frmXfld']   = $_DB_table_prefix . 'forms_frmXfld';

/**
 * Global configuration array.
 * @global array $_CONF_FRM
 */
global $_CONF_FRM;
$_CONF_FRM['pi_name']           = 'forms';
$_CONF_FRM['pi_version']        = '0.4.0';
$_CONF_FRM['gl_version']        = '1.7.0';
$_CONF_FRM['pi_url']            = 'http://www.leegarner.com';
$_CONF_FRM['pi_display_name']   = 'Forms';

?>
