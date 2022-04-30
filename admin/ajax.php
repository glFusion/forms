<?php
/**
 * AJAX functions for the Forms plugin administration.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.6.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 *  Include required glFusion common functions
 */
require_once '../../../lib-common.php';

// Make sure this is called via Ajax
if (!COM_isAjax()) {
    COM_404();
}

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
    if (isset($_POST['type'])) {
        switch ($_POST['type']) {
        case 'form':
            $Frm = Forms\Form::getInstance($_POST['id']);
            if ($Frm->isOwner()) {
                $newval = \Forms\Form::toggle($_POST['id'], 'enabled', $_POST['oldval']);
            }
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

default:
    exit;
}

