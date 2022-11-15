<?php
/**
 * Home page for the Forms plugin.
 * Used to either display a specific form, or to save the user-entered data.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2020 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once '../lib-common.php';
if (!in_array('forms', $_PLUGINS)) {
    COM_404();
}

use Forms\Models\Request;
$Request = Request::getInstance();
$content = '';
$action = '';
$actionval = '';
$expected = array(
    'savedata', 'reset', 'myresult', 'mode', 'print', 'showform',
    'listforms', 'results', 'export', 'preview',
);
list ($action, $actionval) = $Request->getAction($expected);
if (empty($action)) {
    $action = 'showform';
    COM_setArgNames(array('frm_id'));
    $frm_id = COM_getArgument('frm_id');
} else {
    $frm_id = $Request->getString('frm_id');
}

if ($action == 'mode') $action = $actionval;

switch ($action) {
case 'savedata':
    $F = new Forms\Form($frm_id);
    if ($F->isNew()) {
        COM_refresh($_CONF['site_url']);
    }
    $redirect = str_replace('{site_url}', $_CONF['site_url'], $F->getRedirect());
    $errmsg = $F->SaveData($Request);
    if (empty($errmsg)) {
        // Success
        if ($F->getSubmitMsg() != '') {
            COM_setMsg($F->getSubmitMsg());
            $msg = '';
        } else {
            $msg = $Request->getInt('submit_msg');
        }
        if (empty($redirect)) {
            if (isset($Request['_referrer'])) {
                $redirect = $Request->getString('_referrer');
            } elseif ($F->getOnsubmit() & FRM_ACTION_DISPLAY) {
                $redirect = FRM_PI_URL . '/index.php?myresult=x&res_id=' .
                    $F->getResultID();
                $redirect .= '&token=' . $F->getResult()->getToken();
            } elseif (empty($redirect)) {
                $redirect = $_CONF['site_url'];
            }
            $u = parse_url($redirect);
            $q = array();
            if (!empty($u['query'])) {
                parse_str($u['query'], $q);
            }
            $q['msg'] = $msg;
            $q['plugin'] = $_CONF_FRM['pi_name'];
            $q['frm_id'] = $F->getID();
            if (isset($u['scheme'])) {
                $redirect = $u['scheme'] . '://' . $u['host'];
                if (isset($u['path'])) {
                    $redirect .= $u['path'];
                }
            }
            $redirect .= '?';
            $q_arr = array();
            foreach($q as $key=>$value) {
                $q_arr[] = "$key=" . urlencode($value);
            }
            $redirect .= http_build_query($q);
        }
        echo COM_refresh($redirect);
    } else {
        $msg = '2';
        if ($Request->getString('referrer') == '') {
            $Request['referrer'] = $_SERVER['HTTP_REFERER'];
        }
        $Request['forms_error_msg'] = $errmsg;
        FRM_showForm($Request->getInt('frm_id'));
    }
    exit;
    break;

case 'myresult':
    $res_id = $Request->getInt('res_id');
    $token  = $Request->getString('token');
    $Result = new Forms\Result($res_id);
    if (!$Result->isNew()) {
        $Form = Forms\Form::getInstance($Result->getFormID());
        echo COM_siteHeader();
        $Form->ReadData($res_id);
        if (
            $Form->isOwner()
            || plugin_isadmin_forms()
        ) {
            $Form->setToken($token);
            $content .= '<h1>';
            $content .= $Form->getSubmitMsg() == '' ?
                $LANG_FORMS['def_submit_msg'] :
                $Form->getSubmitMsg();
            $content .= '</h1>';
            $content .= $Form->Prt($res_id);
            $content .= '<hr />' . LB;
            $content .= '<center><a href="' . FRM_PI_URL .
                '/index.php?print=x&res_id=' . $res_id . '&frm_id=' . $frm_id .
                '" target="_blank">' .
                '<img src="' . $_CONF['layout_url'] .
                '/images/print.png" border="0" title="' .
                $LANG01[65] . '"></a></center>';
        }
        echo $content;
        echo COM_siteFooter();
        exit;
    }
    break;

case 'print':
    $res_id = $Request->getInt('res_id');
    if ($res_id > 0) {
        $Result = new Forms\Result($res_id);
        $Form = Forms\Form::getInstance($Result->getFormID());
        $Form->ReadData($res_id);
        if (plugin_isadmin_forms() || $F->getResult()->getUid() == $_USER['uid']) {
            // Make sure user is an admin or is viewing their own result
            $content .= $Form->Prt($res_id, true);
        }
        echo $content;
        exit;
    }
    break;

case 'export':
    $Frm = Forms\Form::getInstance($frm_id);
    if ($Frm->isOwner()) {
        $content = $Frm->exportResultsCSV();
        if (!empty($content)) {
            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename="'.$frm_id.'.csv"');
            echo $content;
            exit;
        }
    } else {
        COM_setMsg($LANG_FORMS['no_results']);
        COM_refresh(FRM_ADMIN_URL);
    }
    break;

case 'reset':
    $Frm = Forms\Form::getInstance($frm_id);
    if ($Frm->isOwner()) {
        $Frm->Reset();
    }
    COM_refresh(FRM_PI_URL . '/index.php?listforms');
    break;

case 'listforms':
    echo COM_siteHeader();
    echo Forms\Menu::User('myforms', '');
    echo Forms\Form::adminList(false);
    echo COM_siteFooter();
    break;

case 'preview':
    // allow a form owner to preview their own form
    $content = '';
    if ($frm_id != '') {
        $Form = new Forms\Form($frm_id);
        if ($Form->isOwner()) {
            $content .= Forms\Menu::User($action, 'hdr_form_preview');
            $content .= $Form->Preview();
        }
    }
    if ($content == '') {
        COM_404();
    } else {
        echo COM_siteHeader();
        echo $content;
        echo COM_siteFooter();
    }
    break;

case 'results':
    $instance_id = $Request->getString('instance_id');
    $Form = Forms\Form::getInstance($frm_id);
    if ($Form->isOwner()) {
        echo COM_siteHeader();
        echo Forms\Menu::User($action, 'hder_form_results');
        echo Forms\Result::adminList($frm_id, $instance_id, false);
        echo COM_siteFooter();
    } else {
        COM_404();
    }
    break;

case 'showform':
default:
    if ($frm_id == '') {
        // Missing form ID, we don't know what to do.
        echo COM_refresh($_CONF['site_url']);
        exit;
    } else {
        $modal = $Request->getBool('modal');
        echo FRM_showForm($frm_id, $modal);
    }
    break;
}


/**
 * Display a form.
 * The form can be displayed by itself on the normal web page or in a modal popup.
 *
 * @param   integer $frm_id     Form ID
 * @param   boolean $modal      True to show in a popup, False for a regular page
 * @return  string              HTML for the displayed form
 */
function FRM_showForm($frm_id, $modal = false)
{
    global $_CONF_FRM, $_CONF;

    // Instantiate the form and make sure the current user has access
    // to fill it out
    $F = new Forms\Form($frm_id, FRM_ACCESS_FILL);
    $Request = Request::getInstance();

    $blocks = $modal ? 0 : -1;
    echo Forms\Menu::siteHeader($F->getName(), '', $blocks);
    $msg = $Request->getString('msg');
    if (!empty($msg)) {
        echo COM_showMessage(
            COM_applyFilter($msg, true), $_CONF_FRM['pi_name']
        );
    }
    if ($F->getID() != '' && $F->hasAccess(FRM_ACCESS_FILL) && $F->isEnabled()) {
        echo $F->Render();
    } else {
        $msg = $F->getNoAccessMsg();
        if (!empty($msg)) {
            echo $msg;
        } else {
            echo COM_refresh($_CONF['site_url']);
        }
    }
    $blocks = $modal ? 0 : -1;
    echo Forms\Menu::siteFooter($blocks);
}
