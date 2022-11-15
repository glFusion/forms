<?php
/**
 * Common Guest-Facing AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v.0.6.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../lib-common.php';

use Forms\Models\Request;
use Forms\Models\DataArray;
$Request = Request::getInstance();

// Make sure this is called via Ajax
if (!$Request->isAjax()) {
    COM_404();
}

$output = new DataArray(array(
    'status' => false,
    'message' => '',
    'newval' => 0,
));

switch ($Request->getString('action')) {
case 'toggleEnabled':
    // Regular users and admins can toggle the enabled flag of their forms.
    $oldval = $Request->getInt('oldval');
    $newval = $oldval;
    $type = $Request->getString('type');
    $id = $Request->getString('id');
    switch ($type) {
    case 'form':
        $frm_id = $Request->getString('id');
        $Frm = Forms\Form::getInstance($frm_id);
        if ($Frm->isOwner() || plugin_isadmin_forms()) {
            $newval = Forms\Form::toggle($id, 'enabled', $oldval);
        }
        break;
    }
    $output['status'] = $newval == $oldval ? false : true;
    $output['message'] = $newval == $oldval ? $LANG_FORMS['toggle_failure'] :
                $LANG_FORMS['toggle_success'];
    $output['newval'] = $newval;
    break;

case 'ajax_fld_post':
    // Save a single field from an AJAX form. Expects form and field IDs
    // representing previously defined items.
    $frm_id = $Request->getString('frm_id');
    $fld_id = $Request->getInt('fld_id');
    $elem_id = $Request->getString('elem_id');
    $fld_type = $Request->getString('fld_type');
    if (empty($frm_id) || empty($fld_id) || empty($elem_id) || empty($fld_type)) {
        $msg = "missing form element";
        $status = 1;
        break;
    }
    switch ($fld_type) {
    case 'radio':
    case 'select-one':
    case 'text':
        $value = $Request->getString('fld_value');
        break;
    default:
        $value = $Request->getString('fld_set', 'false');
        break;
    }
    $sess_id = \Forms\Field::sessID($frm_id, $fld_id);
    SESS_setVar($sess_id, $value);
    $status = 0;
    $msg = $LANG_FORMS['field_updated'];
    $output['status'] = $status;
    $output['message'] = $msg;
    break;

case 'ajax_autotag_post':
    // Save a single autotag checkbox or radio button submission.
    // There is no form or field ID here so use the HTML element ID
    $fld_type = $Request->getString('fld_type', 'checkbox');
    $fld_name = $Request->getString('fld_name');
    $elem_id = $Request->getString('elem_id');
    $fld_set = $Request->getString('fld_set', 'false');
    $fld_value = $Request->getInt('fld_value');
    if (empty($fld_name) || empty($elem_id)) {
        $msg = "missing form element";
        $status = 1;
    } else {
        $F = new \Forms\Fields\autotag($fld_type, $fld_name, $fld_value);
        $F->SaveData($fld_value);
        $status = 0;
        $msg = $LANG_FORMS['field_updated'];
    }
    $output['status'] = $status;
    $output['message'] = $msg;
    break;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
//A date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $output->json_encode();
