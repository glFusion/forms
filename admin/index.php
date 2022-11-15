<?php
/**
 * Entry point to administration functions for the Forms plugin.
 * This module isn't exclusively for site admins.  Regular users may
 * be given administrative privleges for certain forms, so they'll need
 * access to this file.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.5.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion libraries */
require_once '../../../lib-common.php';

// Make sure the plugin is installed and enabled
if (
    !in_array('forms', $_PLUGINS) ||
    !plugin_isadmin_forms()
) {
    COM_404();
}

$Request = Forms\Models\Request::getInstance();
$action = 'listforms';      // Default view
$expected = array(
    'edit','updateform','editfield', 'updatefield', 'savecat', 'deletecat',
    'save', 'print', 'editresult', 'updateresult', 'reorder', 'reset',
    'editform', 'editcat', 'copyform', 'delbutton_x', 'showhtml', 'delresult',
    'moderate', 'moderationapprove', 'moderationdelete',
    'deleteFrmDef', 'deleteFldDef', 'cancel', 'action', 'view',
    'results', 'preview', 'listcats', 'export',
);
list ($action, $actionval) = $Request->getAction($expected);

$view = $Request->getString('view',  $action);
$frm_id = COM_sanitizeID($Request->getString('frm_id'));
$msg = $Request->getString('msg');
$content = '';

switch ($action) {
case 'action':      // Got "?action=something".
    switch ($actionval) {
    case 'bulkfldaction':
        $id = $Request->getString('frm_id');
        $cb = $Request->getArray('cb');
        if (empty($cb) || empty($frm_id)) {
            break;
        }
        $fldaction = $Request->getString('fldaction');

        switch ($fldaction) {
        case 'rmfld':
        case 'killfld':
            $deldata = $fldaction = 'killfld' ? true : false;
            foreach ($cb as $varname=>$val) {
                $F = Field::getByName($varname, $frm_id);
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
    $fld_id = $Request->getInt('fld_id');
    $where = $Request->getString('where');
    if ($frm_id != '' && $fld_id > 0 && $where != '') {
        $msg = Forms\Field::Move($frm_id, $fld_id, $where);
    }
    $view = 'editform';
    break;

case 'moderationapprove':
case 'updateresult':
    $F = new Forms\Form($Request->getString('frm_id'));
    $R = new Forms\Result($Request->getInt('res_id'));
    // Clear the moderation flag when saving a moderated submission
    $R->SaveData($Request->getString('frm_id'), $F->getFields(), $_POST, $R->getUid());
    $R->Approve();
    if ($action == 'moderationapprove') {
        $post_save = $Request->getString('post_save');
        if (function_exists($post_save)) {
            $post_save($R->getUid());
        }
        COM_refresh($_CONF['site_admin_url'] . '/moderation.php');
    }
    $view = 'results';
    break;

case 'moderationdelete':
    if (isset($Request['res_id'])) {
        plugin_moderationdelete_forms($Request->getInt('res_id'));
    }
    COM_refresh($_CONF['site_admin_url'] . '/moderation.php');
    break;

case 'updatefield':
    $fld_id = $Request->getInt('fld_id');
    if ($fld_id > 0) {
        $Field = Forms\Field::getById($fld_id);
    } else {
        $Field = Forms\Field::create($Request->getString('type'));
    }
    $msg = $Field->SaveDef($Request);
    echo COM_refresh(FRM_ADMIN_URL . '/index.php?editform=x&frm_id=' . $frm_id . '#frm_fldlist');
    break;

case 'delbutton_x':
    $delfrm = $Request->getArray('delfrm');
    $delfld = $Request->getArray('delfield');
    $delres = $Request->getArray('delresmulti');
    if (!empty($delfrm)) {
        foreach ($delfrm as $frm_id) {
            Forms\Form::getInstance($frm_id)->DeleteDef();
        }
        echo COM_refresh(FRM_ADMIN_URL . '/index.php');
    } elseif (!empty($delfld)) {
        // Deleting one or more fields
        foreach ($delfld as $key=>$value) {
            Forms\Field::Delete($value);
        }
    } elseif (!empty($delres)) {
        foreach ($delres as $key=>$value) {
            Forms\Result::Delete($value);
        }
    }
    echo COM_refresh(FRM_ADMIN_URL . '/index.php?results=x&frm_id=' . $Request->getString('frm_id'));
    CTL_clearCache();   // so the autotags will pick it up.
    break;

case 'delresult':
    Forms\Result::Delete($actionval);
    echo COM_refresh(FRM_ADMIN_URL . '/index.php?results=x&frm_id=' . $Request->getString('frm_id'));
    break;

case 'copyform':
    $Form = new Forms\Form($frm_id);
    $msg = $Form->Duplicate();
    if (empty($msg)) {
        echo COM_refresh(
            FRM_ADMIN_URL . '/index.php?editform=x&amp;frm_id=' . $Form->getID()
        );
        exit;
    } else {
        echo COM_refresh(FRM_ADMIN_URL . '/index.php');
    }
    break;

case 'deletecat':
    $Cat = Forms\Category::getInstance((int)$actionval);
    if ($Cat->getId() > 1) {
        $Cat->Delete();
    }
    echo COM_refresh(FRM_ADMIN_URL . '/index.php?listcats');
    break;

case 'savecat':
    $Cat = Forms\Category::getInstance($Request->getInt('cat_id'));
    $status = $Cat->save($Request);
    if (!$status) {                   // save operation failed
        $view = 'editcat';
    } else {
        echo COM_refresh(FRM_ADMIN_URL . '/index.php?listcats');
    }
    break;

case 'updateform':
    $old_id = $Request->getString('old_id');
    $Form = new Forms\Form($old_id);
    $msg = $Form->SaveDef($Request);
    if ($msg > 0) {                   // save operation failed
        $view = 'editform';
    } elseif (empty($old_id) || count($Form->getFields()) == 0) {
        // New form, return to add fields
        COM_setMsg($LANG_FORMS['now_add_fields']);
        echo COM_refresh(FRM_ADMIN_URL . '/index.php?editform=x&frm_id=' . $Form->getID() . '#frm_fldlist');
    } else {
        echo COM_refresh(FRM_ADMIN_URL . '/index.php');
    }
    break;

case 'deleteFrmDef':
    // Delete a form definition.  Also deletes user values.
    $id = $Request->getString('frm_id');
    $msg = Forms\Form::getInstance($id)->DeleteDef();
    echo COM_refresh(FRM_ADMIN_URL . '/index.php');
    break;

case 'deleteFldDef':
    // Delete a field definition.  Also deletes user values.
    $msg = Forms\Field::Delete($Request->getInt('fld_id'));
    $view = 'editform';
    break;
}

// Select the page to display
switch ($view) {
case 'results':
    $instance_id = $Request->getString('instance_id');
    if (!empty($instance_id)) {
        $other_text = sprintf($LANG_FORMS['showing_instance'], $instance_id) .
            ' <a href="' . FRM_ADMIN_URL . '/index.php?results=x&frm_id=' . $frm_id .
            '">' . $LANG_FORMS['clear_instance'] . '</a>';
    } else {
        $other_text = '';
    }
    $content .= Forms\Menu::Admin($view, 'hder_form_results');
    $content .= Forms\Result::adminList($frm_id, $instance_id);
    break;

case 'export':
    $Frm = Forms\Form::getInstance($frm_id);
    $content = $Frm->exportResultsCSV();
    if (!empty($content)) {
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="'.$frm_id.'.csv"');
        echo $content;
        exit;
    } else {
        COM_setMsg($LANG_FORMS['no_results']);
        COM_refresh(FRM_ADMIN_URL);
    }
    break;

case 'preview':
    $content .= Forms\Menu::Admin($view, 'hdr_form_preview');
    if ($frm_id != '') {
        $Form = new Forms\Form($frm_id);
        $T = new Template($_CONF['path'] . '/plugins/forms/templates/');
        $T->set_file('header', 'preview_header.thtml');
        $T->set_var(array(
            'frm_name'      => $Form->getName(),
            'frm_id'        => $Form->getID(),
            'frm_link'      => FRM_PI_URL . '/index.php?frm_id=' . $Form->getID(),
        ) );
        $T->parse('output', 'header');
        $content .= $T->finish($T->get_var('output'));
        $content .= $Form->Render('preview');
    }
    break;

case 'showhtml':
    if ($frm_id != '') {
        $F = new Forms\Form($frm_id);
        header('Content-type: text/html');
        echo '<html><body><pre>' .
            htmlentities($F->Render('preview')) .
            '</pre></body></html>';
        exit;
    }
    break;

case 'print':
    $res_id = $Request->getInt('res_id');
    if ($frm_id != '' && $res_id > 0) {
        $Form = new Forms\Form($frm_id);
        $content .= $Form->Prt($res_id, true);
        echo $content;
        exit;
    }
    break;

case 'editresult':
case 'moderate':
    $Result = new Forms\Result($Request->getInt('res_id'));
    if (!$Result->isNew()) {
        $Form = Forms\Form::getInstance($Result->getFormID());
        if (!$Form->isNew()) {
            $Form->ReadData($Result->getID());
            if ($action == 'moderate') {
                $mode = 'moderation';
            } else {
                $mode = 'edit';
            }
            $content .= $Form->Render($mode, $Result->getID());
        }
    }
    break;

case 'editcat':
    $Cat = Forms\Category::getInstance((int)$actionval);
    $content .= Forms\Menu::Admin($view, 'hlp_edit_form');
    $content .= $Cat->edit();
    break;

case 'editform':
    // Edit a single definition
    $Form = new Forms\Form($frm_id);
    $content .= Forms\Menu::Admin($view, 'hlp_edit_form');
    $content .= $Form->EditForm();

    // Allow adding/removing fields from existing forms
    if ($frm_id != '') {
        $content .= "<br /><hr />\n";
        $content .= Forms\Field::adminList($frm_id);
    }
    break;

case 'editfield':
    $fld_id = $Request->getInt('fld_id');
    $frm_id = $Request->getString('frm_id');
    $Field = Forms\Field::getById($fld_id);
    if ($Field === NULL) {
        $Field = new Forms\Field();
        $Field->setFormId($frm_id);
    }
    $content .= Forms\Menu::Admin($view, 'hdr_field_edit');
    $content .= $Field->EditDef();
    break;

case 'reset':
    Forms\Form::getInstance($frm_id)->Reset();
    COM_refresh(FRM_ADMIN_URL . '/index.php?listforms');
    break;

case 'resetpermform':
    $content .= Forms\Menu::Admin($view, 'hdr_field_list');
    $content .= FRM_permResetForm();
    break;

case 'none':
    // In case any modes create their own content
    break;

case 'fields':
    $content .= Forms\Menu::Admin($view, 'hdr_field_list');
    $content .= Forms\Field::adminList();
    break;

case 'listcats':
    $content .= Forms\Menu::Admin('listcats', 'hdr_form_list');
    $content .= Forms\Category::adminList();
    break;

case 'listforms':
default:
    $content .= Forms\Menu::Admin('listforms', 'hdr_form_list');
    $content .= Forms\Form::adminList();
    break;
}

$display = COM_siteHeader();
if (isset($msg) && !empty($msg)) {
    $display .= COM_showMessage(
        COM_applyFilter($msg, true), $_CONF_FRM['pi_name']
    );
}
$display .= COM_startBlock(
    $LANG_FORMS['admin_title'] . ' (Ver. ' . $_CONF_FRM['pi_version'] . ')',
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);
$display .= $content;
$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
$display .= COM_siteFooter();
echo $display;
exit;

?>
