<?php
/**
 * Services provided by the Forms plugin.
 * This allows other plugins to request data and forms.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2017 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
use glFusion\Database\Database;
use glFusion\Log\Log;
use Forms\Collectoins\ResultCollection;


/**
 * Create a rendered form and return the HTML along with the title.
 *
 * @param   array   $args       Array of arguments (form id)
 * @param   array   &$output    Array to receive output
 * @param   string  &$svc_msg   To receive service message
 * @return  integer     Status: PLG_RET_OK or PLG_RET_ERROR
 */
function service_renderForm_forms($args, &$output, &$svc_msg)
{
    if (!isset($args['frm_id'])) return PLG_RET_ERROR;
    $F = new Forms\Form($args['frm_id']);
    if ($F->isNew()) {
        return PLG_RET_ERROR;
    }

    $Coll = new ResultCollection;
    $res_id = 0;
    if (isset($args['res_id']) && $args['res_id'] > 0) {
        $Coll->withResultId($args['res_id']);
        //$res_id = (int)$args['res_id'];
    } elseif (isset($args['uid']) && $args['uid'] > 0) {
        $Coll->withUserId($args['uid']);
        //$res_id = Forms\Result::findResult($args['frm_id'], $args['uid']);
    }
    $Results = $Coll->getObjects();
    if (!empty($Results)) {
        $Result = current($Result);
    } else {
        $Result = new Forms\Result;
    }
    if (isset($args['instance_id'])) {
        if (!isset($args['pi_name'])) {
            // Make sure something is set
            $args['pi_name'] = 'unknown';
        }
        $F->setInstanceId($args['instance_id'], $args['pi_name']);
    }
    $F->withResult($Result);
    //if (isset($args['pi_name'])) $F->setPluginName($args['pi_name']);
    if (isset($args['mode'])) {
        $mode = $args['mode'];
    } elseif (isset($args['nobuttons'])) {
        $mode = 'inline';
    } else {
        //$mode = 'edit';
        $mode = 'normal';
    }
    $output = array(
        'content'   =>   $F->Render($mode, $res_id, $args),
        'title'     =>  $F->getName(),
    );
    return PLG_RET_OK;
}


/**
 * Create a form from an array of values.
 * This requires intimate knowledge of the layout of $_POST used to update
 * forms and fields.
 *
 * @param   array   $args       Array of data.  'form'=>form_data_arr, 'fld'=>field
 * @param   mixed   $output     Output data returned, if any
 * @param   mixed   $svc_msg    Service message, if any
 * @return  integer             Status, PLG_RET_OK or PLG_RET_ERROR
 */
function service_createForm_forms($args, &$output, &$svc_msg)
{
    $frm = $args['form'];
    if (empty($frm['frm_id'])) $frm['frm_id'] = COM_makeSid();

    // Create the form.  Return an error if the form ID already exists.
    // This is meant to create new forms only.
    $F = new \Forms\Form($frm['frm_id']);
    if (!$F->isNew) return PLG_RET_NOACCESS;
    $F->SaveDef($frm);

    // Create form fields
    foreach ($args['fields'] as $fld) {
        $Fld = \Forms\Field(0, $frm['frm_id']);
        $Fld->SaveDef($fld);
    }

    return PLG_RET_OK;
}


/**
 * Get a printable version of a form.
 *
 * @param   array   $args       Array of form information
 * @param   string  $output     Receives the printed form HTML
 * @param   mixed   $svc_msg    Not used
 * @return  integer     PLG_RET_OK or PLG_RET_ERROR
 */
function service_printForm_forms($args, &$output, &$svc_msg)
{
    global $_USER, $_TABLES, $LANG_FORMS, $LANG01, $_CONF;

    if ($args['frm_id'] == '') return PLG_RET_ERROR;
    if ($args['viewtype'] != 'prt') $args['viewtype'] = 'view';

    if (isset($args['res_id'])) {
        $res_id = (int)$args['res_id'];
    } elseif (isset($args['uid'])) {
        // If no result ID given, then use the user ID.  Just have to grab
        // the last form updated by the user.
        $res_id = \Forms\Result::findResult($args['frm_id'], $args['uid']);
    }
    if ($res_id < 1) {
        return PLG_RET_ERROR;
    }

    $content = '';
    $F = new \Forms\Form($args['frm_id']);
    $F->ReadData($res_id);
    if ($F->Result->uid == $_USER['uid'] || plugin_isadmin_forms()) {
        $content .= $F->Prt($res_id);
        $content .= '<hr />' . LB;
        if ($args['viewtype'] == 'view') {
            $content .= '<center><a href="' . FRM_PI_URL . 
                '/index.php?print=x&res_id=' . $res_id .
                '&frm_id=' . $args['frm_id'] .
                '" target="_blank">' .
                Forms\Icon::getHTML('print', 'tooltip', array(
                    'title' => $LANG01[65]
                ) ) .
                '</a></center>';
        }
    }
    $output = $content;
}


/**
 * Get the values of a form submission.
 * The output includes an array of:
 * - the form's prompt
 * - the raw value of the submission
 * - the formatted value
 *
 * @param   array   $args       Array of form information
 * @param   string  $output     Receives the form values
 * @param   mixed   $svc_msg    Not used
 * @return  integer     PLG_RET_OK or PLG_RET_ERROR
 */
function service_getValues_forms($args, &$output, &$svc_msg)
{
    global $_USER, $_TABLES, $LANG_FORMS, $LANG01, $_CONF;

    if (!isset($args['frm_id']) || empty($args['frm_id'])) {
        return PLG_RET_ERROR;
    }
    $viewtypes = array('display', 'raw');
    if (!isset($args['viewtype']) || !in_array($viewtypes, $args['viewtype'])) {
        $viewtype = 'display';
    } else {
        $viewtype = $args['viewtype'];
    }
    if (isset($args['res_id'])) {
        $res_id = (int)$args['res_id'];
    } elseif (isset($args['uid'])) {
        // If no result ID given, then use the user ID.  Just have to grab
        // the last form updated by the user.
        $res_id = \Forms\Result::findResult($args['frm_id'], $args['uid']);
    }
    if ($res_id < 1) {
        return PLG_RET_ERROR;
    }

    $output = array();
    $F = new Forms\Form($args['frm_id']);
    $F->ReadData($res_id);
    if ($F->getResult()->getUid() == $_USER['uid'] || plugin_isadmin_forms()) {
        foreach ($F->getFields() as $Fld) {
            if ($Fld->getType() == 'static') {
                $Fld->setPrompt('');
            }
            $output[$Fld->getName()] = array(
                'prompt' => $Fld->getPrompt(),
                'value' => $Fld->getValue(),
                'displayvalue' => $Fld->DisplayValue($F->getFields()),
            );
        }
    }
    return PLG_RET_OK;
}


/**
 * Get the result ID for a given form submission.
 * If a result ID is given, then just return that in $output. Otherwise, get
 * the latest submission for the given user. Assume the current user if no 
 * user ID is passed either.
 *
 * @param   array   $args   Array of args. 'res_id' or 'uid' are used.
 * @param   mixed   &$output    Pointer to results data.
 * @param   mixed   &$svc_msg   Not used
 * @return  integer     PLG_RET_ERROR if no result found, or PLG_RET_OK
 */
function service_resultId_forms($args, &$output, &$svc_msg)
{
    global $_USER, $_TABLES, $LANG_FORMS, $LANG01, $_CONF;

    if (!isset($args['frm_id']) || empty($args['frm_id'])) {
        return PLG_RET_ERROR;
    }
    if (isset($args['res_id'])) {
        $res_id = (int)$args['res_id'];
    } else {
        $res_id = 0;
        // If no result ID given, then use the user ID.  Just have to grab
        // the last form updated by the user. Assume the current user if none.
        if (!isset($args['uid'])) {
            $args['uid'] = $_USER['uid'];
        }
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT res_id FROM {$_TABLES['forms_results']}
                WHERE uid = ? AND frm_id = ?
                ORDER BY dt DESC LIMIT 1",
                array($args['uid'], $args['frm_id']),
                array(Database::INTEGER, Database::STRING)
            )->fetchAssociative();
            if (is_array($data)) {
                $res_id = (int)$data['res_id'];
            }
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
    }
    $output = (int)$res_id;
    if ($output < 1) {
        return PLG_RET_ERROR;
    } else {
        return PLG_RET_OK;
    }
}


/**
 * Get information about a form.
 *
 * @param   array   $args       May include form ID and perms
 * @param   array   $output     Receives results
 * @param   mixed   $svc_msg    Service message, not used
 * @return  integer     PLG_RET_OK on success
 */
function service_getFormInfo_forms($args, &$output, &$svc_msg)
{
    global $_TABLES;

    $db = Database::getInstance();
    $qb = $db->conn->createQueryBuilder();
    $qb->select('*')
       ->from($_TABLES['forms_frmdef'])
       ->where('1 = 1');
    if (array_key_exists('frm_id', $args)) {
        $qb->andWhere('frm_id = :frm_id')
           ->setParameter('frm_id', $args['frm_id'], Database::STRING);
    }
    if (isset($args['perm']) && (int)$args['perm'] > 0) {
        $qb->andWhere($db->getAccessSql('', 'fill_gid'));
    }
    try {
        $output = $qb->execute()->fetchAllAssociative();
    } catch (\Exception $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $output = false;
    }
    if (!is_array($output)) {
        $output = array();
    }
    return PLG_RET_OK;
}


/**
 * Get an array of form IDs and names.
 * This allows a plugin to retrieve a selection of forms that pertain to it by
 * finding all forms with an ID that starts with $arg['basename']
 *
 * @param   array   $args       Must include `basename` element
 * @param   array   $output     Receives form IDs
 * @param   mixed   $svc_msg    Not used
 * @return  integer     PLG_RET_OK, or Error if basename is not set
 */
function service_getMyForms_forms($args, &$output, &$svc_msg)
{
    global $_TABLES;

    $output = array();
    if (!isset($args['basename'])) {
        return PLG_RET_ERROR;
    }

    $key = $args['basename'] . '%';
    $db = Database::getInstance();
    try {
        $data = $db->conn->executeQuery(
            "SELECT frm_id, frm_name FROM {$_TABLES['forms_frmdef']}
            WHERE frm_id like ?",
            array($key),
            array(Database::STRING)
        )->fetchAllAssociative();
    } catch (\Exception $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return PLG_RET_ERROR;
    }
    if (is_array($data)) {
        foreach ($data as $A) {
            $output[$A['frm_id']] = $A['frm_name'];
        }
    }
    return PLG_RET_OK;
}


/**
 * Save a form submission.
 * This allows a plugin to render the form and have its data saved.
 *
 * @param   array   $args       Must include `basename` element
 * @param   array   $output     Receives form IDs
 * @param   mixed   $svc_msg    Not used
 * @return  integer     PLG_RET_OK, or Error if basename is not set
 */
function service_saveData_forms($args, &$output, &$svc_msg)
{
    if (!isset($args['data']) || !isset($args['data']['frm_id'])) {
        return PLG_RET_PRECONDITION_FAILED;
    }
    $F = new Forms\Form($args['data']['frm_id']);
    $output = $F->SaveData($args['data']);
    if ($output === '') {
        return PLG_RET_OK;
    } else {
        return PLG_RET_ERROR;
    }
}


/**
 * Verify that a user's submission is valid.
 * Checks each field using the field's validData function and sets $output
 * to an array of field names with invalid values.
 * Returns OK if the form is valid, ERROR if there are any errors.
 *
 * @param   array       $args       Must include `uid` element
 * @param   mixed       $output     Output array - gets error field names
 * @param   mixed       $svc_msg    Service messages (not used)
 * @return  integer     Result code
 */
function service_validate_forms($args, &$output, &$svc_msg)
{
    $uid = $args['uid'];
    $frm_id = $args['frm_id'];
    $res_id = $args['res_id'];
    $output = array();
    $Res = new Forms\Result($res_id);
    if ($Res->isNew()) {
        $output[] = 'No result fount';
        return PLG_PRECONDITION_FAILED;
    }
    $Frm = Forms\Form::getInstance($Res->getFormID());
    $vals = $Res->getValues($Frm->getFields());
    foreach ($Frm->getFields() as $Fld) {
        $msg = $Fld->Validate($vals);
        if (!empty($msg)) {
            $output[] = $msg;
        }
    }
    return empty($output) ? PLG_RET_OK : PLG_RET_ERROR;
}

