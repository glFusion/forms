<?php
/**
*   Common Guest-Facing AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once '../lib-common.php';

switch ($_POST['action']) {
case 'ajax_fld_post':
    // Save a single field from an AJAX form. Expects form and field IDs
    // representing previously defined items.
    $frm_id = isset($_POST['frm_id']) ? $_POST['frm_id'] : '';
    $fld_id = isset($_POST['fld_id']) ? $_POST['fld_id'] : '';
    $elem_id = isset($_POST['elem_id']) ? $_POST['elem_id'] : '';
    $fld_type = isset($_POST['fld_type']) ? $_POST['fld_type'] : '';
    if (empty($frm_id) || empty($fld_id) || empty($elem_id) || empty($fld_type)) {
        $msg = "missing form element";
        $status = 1;
        break;
    }
    switch ($fld_type) {
    case 'radio':
    case 'select-one':
    case 'text':
        $value = isset($_POST['fld_value']) ? $_POST['fld_value'] : '';
        break;
    default:
        $value = isset($_POST['fld_set']) && $_POST['fld_set'] == 'true' ? true : false;
        break;
    }
    $sess_id = Forms\Field::sessID($frm_id, $fld_id);
    SESS_setVar($sess_id, $value);
    $status = 0;
    $msg = $LANG_FORMS['field_updated'];
    break;

case 'ajax_autotag_post':
    // Save a single autotag checkbox or radio button submission.
    // There is no form or field ID here so use the HTML element ID
    $fld_type = isset($_POST['fld_type']) ? $_POST['fld_type'] : 'checkbox';
    $fld_name = isset($_POST['fld_name']) ? $_POST['fld_name'] : '';
    $elem_id = isset($_POST['elem_id']) ? $_POST['elem_id'] : '';
    $fld_set = isset($_POST['fld_set']) && $_POST['fld_set'] == 'true' ? true : false;
    $fld_value = isset($_POST['fld_value']) ? $_POST['fld_value'] : 0;
    if (empty($fld_name) || empty($elem_id)) {
        $msg = "missing form element";
        $status = 1;
    } else {
        $F = new Forms\Field_autotag($fld_type, $fld_name, $fld_value);
        $F->SaveData($fld_value);
        $status = 0;
        $msg = $LANG_FORMS['field_updated'];
    }
    break;
}

$retval = array(
    'status' => $status,
    'msg' => $msg,
);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
//A date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo json_encode($retval);

?>
