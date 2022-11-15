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
$Request = Forms\Models\Request::getInstance();

// Make sure this is called via Ajax
if (!$Request->isAjax()) {
    COM_404();
}

// This is for administrators only
if (!plugin_isadmin_forms()) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the forms AJAX functions.");
    exit;
}

$base_url = FRM_ADMIN_URL;
$result = array();

switch ($Request->getString('action')) {
case 'toggleEnabled':
    $oldval = $Request->getInt('oldval');
    $newval = 99;
    $var = trim($Request->getString('var'));  // validated via switch below
    $type = $Request->getString('type');
    $id = $Request->getString('id');    // use a string, cast as needed
    switch ($type) {
    case 'form':
        $Frm = Forms\Form::getInstance($id);
        if ($Frm->isOwner()) {
            $newval = \Forms\Form::toggle($id, 'enabled', $oldval);
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
            $newval = \Forms\Field::toggle((int)$id, $var, $oldval);
            break;
        }
        /*$type = 'field';
        $table = 'forms_flddef';
        $field = 'fld_id';*/
        break;
    default:
        break;
    }

    $result = array(
        'status' => $newval == $oldval ? false : true,
        'statusMessage' => $newval == $oldval ? $LANG_FORMS['toggle_failure'] :
                $LANG_FORMS['toggle_success'],
        'newval' => $newval,
    );
    break;

case 'chgcategory':
    $cat_id = $Request->getInt('cat_id');
    $result = array(
        'catuid_name' => '',
        'catgid_name' => '',
    );
    if ($cat_id > 0) {
        $Cat = Forms\Category::getInstance($cat_id);
        if (!$Cat->isNew()) {
            $result = $Cat->getEmailNames();
        }
    }
    break;

default:
    exit;
}

header('Content-Type: text/xml');
header("Cache-Control: no-cache, must-revalidate");
//A date in the past
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
echo json_encode($result);

