<?php
/**
 *  Common AJAX functions
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2010 Lee Garner <lee@leegarner.com>
 *  @package    forms
 *  @version    0.0.1
 *  @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 *  @filesource
 */

/**
 *  Include required glFusion common functions
 */
require_once '../../../lib-common.php';

// This is for administrators only
if (!plugin_isadmin_forms()) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the forms AJAX functions.");
    exit;
}

$base_url = FRM_ADMIN_URL;

switch ($_POST['action']) {
case 'toggleEnabled':
    $oldval = $_POST['oldval'] == 0 ? 0 : 1;
    $newval = 99;
    $var = trim($_POST['var']);  // sanitized via switch below
    $id = DB_escapeString($_POST['id']);
    if (isset($_POST['type'])) {
        switch ($_POST['type']) {
        case 'form':
            USES_forms_class_form();
            $newval = \Forms\Form::toggle($_POST['id'], 'enabled', $_POST['oldval']);
            /*$type = 'form';
            $table = 'forms_frmdef';
            $field = 'id';*/
            break;
        case 'field':
            switch ($var) {
            case 'readonly':
            case 'required':
            case 'enabled':
            case 'user_reg':
                USES_forms_class_field();
COM_errorLog("here");
                $newval = \Forms\Field::toggle($_POST['id'], $var, $_POST['oldval']);
                break;
            }
            /*$type = 'field';
            $table = 'forms_flddef';
            $field = 'fld_id';*/
            break;
        default:
            break;
        }
    }

    $result = array(
        'status' => $newval == $oldval ? false : true,
        'statusMessage' => $newval == $oldval ? $LANG_FORMS['toggle_failure'] :
                $LANG_FORMS['toggle_success'],
        'newval' => $newval,
    );
    
    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    echo json_encode($result);
    break;

/*case 'toggleFormEnabled':
    $newval = $_REQUEST['newval'] == 1 ? 1 : 0;
    $id = (int)$_REQUEST['id'];
    $type = 'enabled';

    // Toggle the flag between 0 and 1
    DB_query("UPDATE {$_TABLES['forms_frmdef']}
            SET $type = '$newval'
            WHERE id='$id'");

    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    echo '<?xml version="1.0" encoding="ISO-8859-1"?>
    <info>'. "\n";
    echo "<newval>$newval</newval>\n";
    echo "<id>{$id}</id>\n";
    echo "<type>{$type}</type>\n";
    echo "<baseurl>{$base_url}</baseurl>\n";
    echo "</info>\n";
    break;
*/
default:
    exit;
}

?>
