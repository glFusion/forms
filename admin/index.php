<?php
/**
*   Entry point to administration functions for the Forms plugin.
*   This module isn't exclusively for site admins.  Regular users may
*   be given administrative privleges for certain forms, so they'll need
*   access to this file.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.1.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion libraries */
require_once '../../../lib-common.php';

// Make sure the plugin is installed and enabled
if (!in_array('forms', $_PLUGINS)) {
    COM_404();
}

//require_once '../../auth.inc.php';

// Flag to indicate if this user is a "real" administrator for the plugin.
// Some functions, like deleting definitions, are only available to 
// plugin admins.
$isAdmin = SEC_hasRights('forms.admin') ? true : false;

// Import administration functions
USES_lib_admin();
USES_forms_functions();

$action = 'listforms';      // Default view
$expected = array('edit','updateform','editfield', 'updatefield',
    'save', 'print', 'editresult', 'updateresult', 'reorder',
    'editform', 'copyform', 'delbutton_x', 'showhtml',
    'deleteFrmDef', 'deleteFldDef', 'cancel', 'action', 'view');
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : $action;
$frm_id = isset($_REQUEST['frm_id']) ? COM_sanitizeID($_REQUEST['frm_id']) : '';
$msg = isset($_GET['msg']) && !empty($_GET['msg']) ? $_GET['msg'] : '';

// Get the permission SQL once, since it's used in a couple of places.
// This determines if the current user is an admin for a particular form
if (SEC_hasRights('forms.admin')) {
//echo "herE";die;
    $perm_sql = '';
} else {
    $perm_sql = " AND (owner_id='". (int)$_USER['uid'] . "'
            OR group_id IN (" . implode(',', $_GROUPS). "))";
}

if ($frm_id != '') {
    // Check user's access, make sure they're admin for at least one form
    $x = DB_fetchArray(DB_query("SELECT count(*) as c
            FROM {$_TABLES['forms_frmdef']}
            WHERE id='$frm_id' $perm_sql"), false);
    if (!$x || $x['c'] < 1) {
        COM_404();
    }
}


switch ($action) {
case 'action':      // Got "?action=something".
    switch ($actionval) {
    case 'bulkfldaction':
        USES_forms_class_field();
        if (!isset($_POST['cb']) || !isset($_POST['frm_id']))
            break;
        $id = $_POST['frm_id'];    // Override the usual 'id' parameter
        $fldaction = isset($_POST['fldaction']) ? $_POST['fldaction'] : '';

        switch ($fldaction) {
        case 'rmfld':
        case 'killfld':
            $deldata = $fldaction = 'killfld' ? true : false;
            $F = new frmField();
            foreach ($_POST['cb'] as $varname=>$val) {
                $F->Read($varname);
                if (!empty($F->id)) {
                    $F->Remove($id, $deldata);
                }
            }
            break;
        }
        $view = 'editform';
        break;

    default:
        $view = $actionval;
        break;
    }
    break;

case 'reorder':
    $fld_id = isset($_GET['fld_id']) ? $_GET['fld_id'] : 0;
    $where = isset($_GET['where']) ? $_GET['where'] : '';
    if ($frm_id != '' && $fld_id > 0 && $where != '') {
        USES_forms_class_field();
        $msg = frmField::Move($frm_id, $fld_id, $where);
    }
    $view = 'editform';
    break;

case 'updateresult':
    USES_forms_class_form();
    USES_forms_class_result();
    $F = new frmForm($_POST['frm_id']);
    $R = new frmResult($_POST['res_id']);
    $R->SaveData($_POST['frm_id'], $F->fields, $_POST, $R->uid);
    $view = 'results';
    break;

case 'updatefield':
    USES_forms_class_field();
    $fld_id = isset($_POST['fld_id']) ? $_POST['fld_id'] : 0;
    $F = new frmField($fld_id, $frm_id);
    $msg = $F->SaveDef($_POST);
    $view = 'editform';
    break;

case 'delbutton_x':
    if (isset($_POST['delfield']) && is_array($_POST['delfield'])) {
        // Deleting one or more fields
        USES_forms_class_field();
        foreach ($_POST['delfield'] as $key=>$value) {
            frmField::Delete($value);
        }
    } elseif (isset($_POST['delresmulti']) && is_array($_POST['delresmulti'])) {
        USES_forms_class_result();
        foreach ($_POST['delresmulti'] as $key=>$value) {
            frmResult::Delete($value);
        }
        $view = 'results';
    }
    CTL_clearCache();   // so the autotags will pick it up.
    break;
    
case 'copyform':
    USES_forms_class_form();
    $F = new frmForm($frm_id);
    $msg = $F->Duplicate();
    if (empty($msg)) {
        echo COM_refresh(FRM_ADMIN_URL . '/index.php?editform=x&amp;frm_id=' .
            $F->id);
        exit;
    } else {
        $view = 'listforms';
    }
    break;

case 'updateform':
    USES_forms_class_form();
    $F = new frmForm($_POST['old_id']);
    $msg = $F->SaveDef($_POST);
    if ($msg != '') {                   // save operation failed
        $view = 'editform';
    } elseif (empty($_POST['old_id'])) {    // New form, return to add fields
        $frm_id = $F->id;
        $view = 'editform';
        $msg = 6;
    } else {
        $view = 'listforms';
    }
    break;

case 'deleteFrmDef':
    // Delete a form definition.  Also deletes user values.
    if (!$isAdmin) COM_404();
    $id = $_REQUEST['frm_id'];
    USES_forms_class_form();
    $msg = frmForm::DeleteDef($id);
    $view = 'listforms';
    break;

case 'deleteFldDef':
    if (!$isAdmin) COM_404();
    // Delete a field definition.  Also deletes user values.
    USES_forms_class_field();
    $msg = frmField::Delete($_GET['fld_id']);
    $view = 'editform';
    break;

}

// Select the page to display
switch ($view) {
case 'results':
    $instance_id = isset($_GET['instance_id']) ? $_GET['instance_id'] : '';
    if (!empty($instance_id)) {
        $other_text = sprintf($LANG_FORMS['showing_instance'], $instance_id) .
            ' <a href="' . FRM_ADMIN_URL . '/index.php?action=results&frm_id=' . $frm_id .
            '">' . $LANG_FORMS['clear_instance'] . '</a>';
    } else {
        $other_text = '';
    }
    $content .= FRM_adminMenu($view, 'hdr_form_results', $other_text);
    //$content .= FRM_ResultsTable($frm_id, true);
    $content .= FRM_listResults($frm_id, $instance_id);
    break;

case 'export':
    USES_forms_class_form();
    USES_forms_class_result();
    USES_forms_class_field();

    $Frm = new frmForm($frm_id);

    // Get the form result sets
    $sql = "SELECT r.* FROM {$_TABLES['forms_results']} r
            LEFT JOIN {$_TABLES['forms_frmdef']} f
            ON f.id = r.frm_id
            WHERE frm_id='$frm_id'
            $perm_sql
            ORDER BY dt ASC";
    $res = DB_query($sql);

    $R = new frmResult();
    $fields = array('"UserID"', '"Submitted"');
    foreach ($Frm->fields as $F) {
        if (!$F->enabled) continue;     // ignore disabled fields
        $fields[] = '"' . $F->name . '"';
    }
    $retval = join(',', $fields) . "\n";
    while ($A = DB_fetchArray($res, false)) {
        $R->Read($A['id']);
        $fields = array(
            COM_getDisplayName($R->uid),
            strftime('%Y-%m-%d %H:%M', $R->dt),
        );
        foreach ($Frm->fields as $F) {
            if (!$F->enabled) continue;     // ignore disabled fields
            $F->GetValue($R->id);
            $fields[] = '"' . str_replace('"', '""', $F->value_text) . '"';
        }
        $retval .= join(',', $fields) . "\n";
    }
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="'.$frm_id.'.csv"');
    echo $retval;
    exit;
    break;

case 'preview':
    $content .= FRM_adminMenu($view, 'hdr_form_preview');
    if ($frm_id != '') {
        USES_forms_class_form();
        $F = new frmForm($frm_id);
        $T = new Template($_CONF['path'] . '/plugins/forms/templates/');
        $T->set_file('header', 'preview_header.thtml');
        $T->set_var(array(
            'frm_name'      => $F->name,
            'frm_id'        => $F->id,
            'frm_link'      => FRM_PI_URL . '/index.php?frm_id=' . $F->id,
        ) );
        $T->parse('output', 'header');
        $content .= $T->finish($T->get_var('output'));
        $content .= $F->Render('preview');
    }
    break;

case 'showhtml':
    if ($frm_id != '') {
        USES_forms_class_form();
        $F = new frmForm($frm_id);
        header('Content-type: text/html');
        echo '<html><body><pre>' . 
            htmlentities($F->Render('preview')) . 
            '</pre></body></html>';
        exit;
    }
    break;

case 'print':
    $res_id = isset($_REQUEST['res_id']) ? (int)$_REQUEST['res_id'] : 0;
    if ($frm_id != '' && $res_id > 0) {
        USES_forms_class_form();
        $F = new frmForm($frm_id);
        $content .= $F->Prt($res_id, true);
        echo $content;
        exit;
    }
    break;

case 'editresult':
    USES_forms_class_form();
    $res_id = (int)$_GET['res_id'];
    $frm_id = DB_getItem($_TABLES['forms_results'], 'frm_id',
            "id={$res_id}"); 
    if (!empty($frm_id)) {
        $F = new frmForm($frm_id);
        $F->ReadData($res_id);
        $content .= $F->Render('edit', $res_id);
    }
    break;

case 'editform':
    // Edit a single definition
    //if (!$isAdmin) COM_404();     // don't check here?

    USES_forms_class_form();
    $F = new frmForm($frm_id);
    /*if ($msg) {
        $content .= COM_showMessageText($msg);
    }*/
    $content .= FRM_adminMenu($view, 'hlp_edit_form');
    $content .= $F->EditForm();

    // Allow adding/removing fields from existing forms
    if ($frm_id != '') {
        $content .= "<br /><hr />\n";
        $content .= FRM_listFields($frm_id);
    }
    break;

case 'editfield':
    if (!$isAdmin) COM_404();
    $fld_id = isset($_GET['fld_id']) ? (int)$_GET['fld_id'] : 0;
    USES_forms_class_field();
    $F = new frmField($fld_id, $frm_id);
    $content .= FRM_adminMenu($view, 'hdr_field_edit');
    $content .= $F->EditDef();
    break;

case 'resetpermform':
    if (!$isAdmin) COM_404();
    $content .= FRM_permResetForm();
    break;

case 'none':
    // In case any modes create their own content
    break;

case 'fields':
    if (!$isAdmin) COM_404();
    $content .= FRM_adminMenu($view, 'hdr_field_list');
    $content .= FRM_listFields();
    break;

case 'listforms':
default:
    $content .= FRM_adminMenu('listforms', 'hdr_form_list');
    $content .= FRM_listForms();
    break;

}

$display = COM_siteHeader();
if (isset($msg) && !empty($msg)) {
    $display .= COM_showMessage(
        COM_applyFilter($msg, true), $_CONF_FRM['pi_name']);
}
$display .= COM_startBlock(
    $LANG_FORMS['admin_title'] . ' (Ver. ' . $_CONF_FRM['pi_version'] . ')',
     '', COM_getBlockTemplate('_admin_block', 'header'));
$display .= $content;
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display .= COM_siteFooter();
echo $display;
exit;


/**
*   Uses lib-admin to list the forms definitions and allow updating.
*
*   @return string HTML for the list
*/
function FRM_listForms()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS, $perm_sql;

    $retval = '';

    // Make sure the items are properly ordered
    //FRM_reorderDef();

    $header_arr = array(
        array('text' => 'ID', 'field' => 'id', 'sort' => true),
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 
                'sort' => false, 'align' => 'center'),
        array('text' => $LANG_ADMIN['copy'], 'field' => 'copy', 
                'sort' => false, 'align' => 'center'),
        array('text' => $LANG_FORMS['view_html'], 'field' => 'view_html', 
                'sort' => false, 'align' => 'center'),
        array('text' => $LANG_FORMS['submissions'], 'field' => 'submissions', 
                'sort' => false, 'align' => 'center'),
        array('text' => $LANG_FORMS['name'], 'field' => 'name', 'sort' => true),
        array('text' => $LANG_FORMS['enabled'], 'field' => 'enabled', 
                'sort' => false),
        array('text' => 'Action', 'field' => 'action', 'sort' => true),
        array('text' => $LANG_ADMIN['delete'], 'field' => 'delete', 
                'sort' => false, 'align' => 'center'),
    );

    $text_arr = array();
    $query_arr = array('table' => 'forms_frmdef',
        'sql' => "SELECT *
               FROM 
                    {$_TABLES['forms_frmdef']}
                WHERE 1=1 $perm_sql",
        'query_fields' => array('name'),
        'default_filter' => ''
    );
    $defsort_arr = array('field' => 'name', 'direction' => 'ASC');

    $retval .= ADMIN_list('forms', 'FRM_getField_form', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);

    return $retval;
}


/**
*   Uses lib-admin to list the field definitions and allow updating.
*
*   @return string HTML for the list
*/
function FRM_listFields($frm_id = '')
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS;

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 'sort' => false),
        array('text' => $LANG_FORMS['name'], 'field' => 'name', 'sort' => true),
        array('text' => $LANG_FORMS['move'], 'field' => 'move', 'sort' => false),
        array('text' => $LANG_FORMS['type'], 'field' => 'type', 'sort' => true),
        array('text' => $LANG_FORMS['enabled'], 'field' => 'enabled', 'sort' => true),
        //array('text' => $LANG_FORMS['required'], 'field' => 'required', 'sort' => true),
        array('text' => $LANG_FORMS['fld_access'], 'field' => 'access', 'sort' => true),
        array('text' => $LANG_ADMIN['delete'], 'field' => 'delete', 'sort' => false),
    );

    $defsort_arr = array('field' => 'orderby', 'direction' => 'asc');
    $text_arr = array('form_url' => FRM_ADMIN_URL . '/index.php');
    $options_arr = array('chkdelete' => true, 'chkname' => 'delfield',
                'chkfield' => 'fld_id');
    $query_arr = array('table' => 'forms_flddef',
        'sql' => "SELECT * FROM {$_TABLES['forms_flddef']}",
        'query_fields' => array('name', 'type', 'value'),
        'default_filter' => ''
    );
    if ($frm_id != '') {
        $query_arr['sql'] .= " WHERE frm_id='" . DB_escapeString($frm_id) . "'";
    }
    /*$retval .= ADMIN_list('forms', 'FRM_getField_field', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', 
                    $options_arr, $form_arr);*/

    $T = new Template(FRM_PI_PATH . '/templates/admin');
    $T->set_file('formfields', 'formfields.thtml');
    $T->set_var(array(
        'action_url'    => FRM_ADMIN_URL . '/index.php',
        'frm_id'        => $frm_id,
        'pi_url'        => FRM_PI_URL,
        'field_adminlist' => ADMIN_list('forms', 
                    'FRM_getField_field', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', 
                    $options_arr, $form_arr),
    ) );

    $T->parse('output', 'formfields');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}


/**
*   Determine what to display in the admin list for each form.
*
*   @param  string  $fieldname  Name of the field, from database
*   @param  mixed   $fieldvalue Value of the current field
*   @param  array   $A          Array of all name/field pairs
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML for the field cell
*/
function FRM_getField_form($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_FORMS, $_TABLES;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = 
            COM_createLink(
                $icon_arr['edit'],
                FRM_ADMIN_URL . "/index.php?editform=x&amp;frm_id={$A['id']}"
            );
        break;

    case 'copy':
        $retval = 
            COM_createLink(
                $icon_arr['copy'],
                FRM_ADMIN_URL . "/index.php?copyform=x&amp;frm_id={$A['id']}"
            );
        break;

    case 'view_html':
        $retval = 
            COM_createLink(
                '<img src="' . FRM_PI_URL . '/images/show_html.png"
                height="16" width="16" border="0">',
                FRM_ADMIN_URL . "/index.php?showhtml=x&amp;frm_id={$A['id']}",
                array('target'=>'_new')
            );
        break;

    case 'delete':
            $retval = COM_createLink(
                "<img src=\"{$_CONF['layout_url']}/images/admin/delete.png\" 
                    height=\"16\" width=\"16\" border=\"0\"
                    onclick=\"return confirm('{$LANG_FORMS['confirm_delete']}?');\"
                    >",
                FRM_ADMIN_URL . "/index.php?deleteFrmDef=x&frm_id={$A['id']}"
            );
       break;

    case 'enabled':
        if ($A[$fieldname] == 1) {
            $chk = ' checked ';
            $enabled = 1;
        } else {
            $chk = '';
            $enabled = 0;
        }
        $retval = 
                "<input name=\"{$fieldname}_{$A['id']}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='FRMtoggleEnabled(this, \"{$A['id']}\", \"form\", \"{$fieldname}\", \"" . FRM_ADMIN_URL . "\");' " .
                "/>\n";
    break;

    case 'name':
        $retval = COM_createLink($fieldvalue, 
            FRM_ADMIN_URL . '/index.php?action=preview&frm_id=' . $A['id']);
        break;

    case 'submissions':
        $retval = (int)DB_count($_TABLES['forms_results'], 'frm_id', $A['id']);
        break;

    case 'action':
        $retval = '<select name="action"
            onchange="javascript: document.location.href=\'' .
            FRM_ADMIN_URL . '/index.php?frm_id=' . $A['id'] . 
            '&action=\'+this.options[this.selectedIndex].value">'. "\n";
        $retval .= '<option value="">--Select Action--</option>'. "\n";
        $retval .= '<option value="preview">Preview</option>'. "\n";
        $retval .= '<option value="results">Results</option>'. "\n";
        $retval .= '<option value="export">Export CSV</option>'. "\n";
        $retval .= "</select>\n";
        break;

    default:
        $retval = $fieldvalue;
        break;

    }

    return $retval;
}


/**
*   Determine what to display in the admin list for each field.
*
*   @param  string  $fieldname  Name of the field, from database
*   @param  mixed   $fieldvalue Value of the current field
*   @param  array   $A          Array of all name/field pairs
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML for the field cell
*/
function FRM_getField_field($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $LANG_FORMS;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink($icon_arr['edit'],
            FRM_ADMIN_URL . "/index.php?editfield=x&amp;fld_id={$A['fld_id']}");
        break;

    case 'delete':
            $retval = COM_createLink(
                "<img src=\"{$_CONF['layout_url']}/images/admin/delete.png\" 
                    height=\"16\" width=\"16\" border=\"0\"
                    onclick=\"return confirm('{$LANG_FORMS['confirm_delete']}');\"
                    >",
                FRM_ADMIN_URL . '/index.php?deleteFldDef=x&fld_id=' .
                    $A['fld_id'] . '&frm_id=' . $A['frm_id']
            );

       break;

    case 'access':
        $retval = 'Unknown';    
        switch ($fieldvalue) {
        case FRM_FIELD_NORMAL:
            $retval = $LANG_FORMS['normal'];
            break;
        case FRM_FIELD_READONLY:
            $retval = $LANG_FORMS['readonly'];
            break;
        case FRM_FIELD_HIDDEN:
            $retval = $LANG_FORMS['hidden'];
            break;
        case FRM_FIELD_REQUIRED:
            $retval = $LANG_FORMS['required'];
            break;
        }
        break;
    case 'enabled':
    case 'required':
        if ($A[$fieldname] == 1) {
            $chk = ' checked ';
            $enabled = 1;
        } else {
            $chk = '';
            $enabled = 0;
        }
        $retval = 
                "<input name=\"{$fieldname}_{$A['fld_id']}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='FRMtoggleEnabled(this, \"{$A['fld_id']}\", \"field\", \"{$fieldname}\", \"" . FRM_ADMIN_URL . "\");' ".
                "/>\n";
    break;

    case 'id':
    case 'fld_id':
        return '';
        break;

    case 'move':
        $retval = '<a href="' . FRM_ADMIN_URL . '/index.php' . 
            "?frm_id={$A['frm_id']}&reorder=x&where=up&fld_id={$A['fld_id']}\"><img src=\"" . FRM_PI_URL . 
            '/images/up.png" height="16" width="16" border="0" /></a>'."\n";
        $retval .= '<a href="' . FRM_ADMIN_URL . '/index.php' . 
            "?frm_id={$A['frm_id']}&reorder=x&where=down&fld_id={$A['fld_id']}\"><img src=\"" . FRM_PI_URL .
            '/images/down.png" height="16" width="16" border="0" /></a>' . "\n";
        break;

    default:
        $retval = $fieldvalue;
        break;

    }

    return $retval;
}


/**
*   Uses lib-admin to list the form results.
*
*   @param  string  $frm_id         ID of form
*   @param  string  $instance_id    Optional form instance ID
*   @return string          HTML for the list
*/
function FRM_listResults($frm_id, $instance_id='')
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS, $perm_sql;

    $retval = '';

    if ($frm_id == '') return $retval;

    $header_arr = array(
        array('text' => $LANG_FORMS['action'], 'field' => 'action', 
                    'sort' => false),
        array('text' => $LANG_FORMS['instance'], 'field' => 'instance_id', 
                    'sort' => true),
        array('text' => $LANG_FORMS['submitter'], 'field' => 'uid', 
                    'sort' => true),
        array('text' => $LANG_FORMS['submitted'], 'field' => 'submitted', 
                    'sort' => true),
        array('text' => $LANG_FORMS['ip_addr'], 'field' => 'ip', 
                    'sort' => true),
    );

    $defsort_arr = array('field' => 'submitted', 'direction' => 'desc');

    $text_arr = array(
        'form_url'  => FRM_ADMIN_URL . '/index.php?frm_id=' . $frm_id,
    );

    $sql = "SELECT *, FROM_UNIXTIME(dt) as submitted
            FROM {$_TABLES['forms_results']}
            WHERE frm_id = '" . DB_escapeString($frm_id) . "'";
    if (!empty($instance_id)) {
        $sql .= " AND instance_id = '" . DB_escapeString($instance_id) . "'";
    }

    $query_arr = array('table' => 'forms_results',
        'sql' =>  $sql,
        //'query_fields' => array(''),
        'default_filter' => ''
    );

    $options_arr = array(
        'chkdelete' => true,
        'chkname' => 'delresmulti',
        'chkfield' => 'id',
    );
    $retval .= ADMIN_list('forms', 'FRM_getField_results', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', 
                    $options_arr, $form_arr);

    return $retval;
}


/**
*   Determine what to display in the admin list for each results field.
*
*   @param  string  $fieldname  Name of the field, from database
*   @param  mixed   $fieldvalue Value of the current field
*   @param  array   $A          Array of all name/field pairs
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML for the field cell
*/
function FRM_getField_results($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF;

    $retval = '';

    switch($fieldname) {
    case 'action':
        $retval = '<a href="' . FRM_ADMIN_URL . '/index.php?print=x&frm_id=' .
            $A['frm_id'] . '&res_id=' . $A['id'] . 
            '" target="_blank"><img src="' . $_CONF['layout_url'] . 
            '/images/print.png" border="0"></a>
            <a href="' . FRM_ADMIN_URL . '/index.php?editresult=x&res_id=' .
            $A['id'] . '"><img src="' .
            $_CONF['layout_url'] . '/images/edit.png" border="0"></a>';
        break;

    case 'instance_id':
        $url = FRM_ADMIN_URL . '/index.php?action=results&frm_id=' . $A['frm_id'];
        $retval = '<a href="' . $url . '&instance_id=' . $fieldvalue . '">' . $fieldvalue . '</a>';
        /*if (isset($_GET['instance_id'])) {
            $retval .= '<a href="' . $url . '"><img src="' . $_CONF['layout_url'] .
                '/images/admin/delete.png"></a>';
        }*/
        break;

    case 'uid':
        $retval = COM_getDisplayName($fieldvalue);
        break;

    default:
        $retval = $fieldvalue;
        break;

    }

    return $retval;
}


/**
*   Create the admin menu at the top of the list and form pages.
*
*   @param  string  $view   Current view, used to select menu options
*   @param  string  $help_text  Text to display below menu
*   @return string      HTML for admin menu section
*/
function FRM_adminMenu($view ='', $help_text = '', $other_text='')
{
    global $_CONF, $LANG_FORMS, $_CONF_FRM, $LANG01, $isAdmin;

    $menu_arr = array ();
    if ($help_text == '')
        $help_text = 'admin_text';

    if ($view == 'listforms' && $isAdmin) {
        $menu_arr[] = array('url' => FRM_ADMIN_URL . '/index.php?action=editform',
            'text' => $LANG_FORMS['add_form']);
    } else {
        $menu_arr[] = array('url' => FRM_ADMIN_URL . '/index.php?view=listforms',
            'text' => $LANG_FORMS['list_forms']);
    }

    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
            'text' => $LANG01[53]);

    $text = $LANG_FORMS[$help_text];
    if (!empty($other_text)) $text .= '<br />' . $other_text;
    $retval .= ADMIN_createMenu($menu_arr, $text, plugin_geticon_forms());
    return $retval;

}


?>
