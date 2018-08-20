<?php
/**
*   Class to handle all forms items.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.4.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;


/**
*   Class for a user's custom forms.
*/
class Form
{
    /** Local properties
    *   @var array */
    var $properties = array();

    /** Form fields, an array of objects
    *   @var array */
    var $fields = array();

    /** Result object for a user submission
    *   @var object */
    var $Result;

    /** Database ID of a result record
    *   @var integer */
    var $res_id;

    var $allow_submit;  // Turn off the submit button when previewing
    var $instance_id;   // Instance of this form, for tying to a plugin entry
    var $isNew;
    var $uid;
    var $access;


    /**
    *   Constructor.  Create a forms object for the specified user ID,
    *   or the current user if none specified.  If a key is requested,
    *   then just build the forms for that key (requires a $uid).
    *
    *   @param  integer $uid    Optional user ID
    *   @param  string  $key    Optional key to retrieve
    */
    function __construct($id = '', $access=FRM_ACCESS_ADMIN)
    {
        global $_USER, $_CONF_FRM, $_TABLES;

        $this->uid = (int)$_USER['uid'];
        if ($this->uid == 0) $this->uid = 1;    // Anonymous
        $def_group = (int)DB_getItem($_TABLES['groups'], 'grp_id',
                "grp_name='forms Admin'");
        if ($def_group < 1) $def_group = 1;     // default to Root
        $this->instance_id = '';
        $this->Result = NULL;

        if (!empty($id)) {
            $id = COM_sanitizeID($id);
            $this->id = $id;
            $this->isNew = false;
            if (!$this->Read($id, $access)) {
                $this->id = COM_makeSid();
                $this->isNew = true;
            }
        } else {
            $this->isNew = true;
            $this->fill_gid = $_CONF_FRM['fill_gid'];
            $this->results_gid = $_CONF_FRM['results_gid'];
            $this->group_id = $def_group;
            $this->enabled = 1;
            $this->id = COM_makeSid();
            $this->enabled = 1;
            $this->id = COM_makeSid();
            $this->introtext = '';
            $this->submit_msg = '';
            $this->noaccess_msg = '';
            $this->noedit_msg = '';
            $this->max_submit_msg = '';
            $this->name = '';
            $this->email = '';
            $this->owner_id = 2;
            $this->onsubmit = 0;
            $this->filled_by = 0;
            $this->inblock = 0;
            $this->max_submit = 0;
            $this->onetime = 0;
            $this->moderate = 0;
            $this->captcha = 0;
            //$this->properties[$name] = $value == 0 ? 0 : 1;
            $this->redirect = '';
            $this->sub_type = 'regular';
        }
    }


    /**
     * Get an instance of a form object.
     *
     * @param   string  $frm_id     Form ID
     * @return  object      Form object
     */
    public static function getInstance($frm_id)
    {
        $key = 'form_' . $frm_id;
        $Obj = Cache::get($key);
        if ($Obj === NULL) {
            $Obj = new self($frm_id);
            Cache::set($key, $Obj);
        }
        return $Obj;
    }


    /**
    *   Set a local property
    *
    *   @param  string  $name   Name of property to set
    *   @param  mixed   $value  Value to set
    */
    function __set($name, $value)
    {
        global $LANG_FORMS;

        switch ($name) {
        case 'id':
        case 'old_id':
            $this->properties[$name] = COM_sanitizeID($value);
            break;

        case 'owner_id':
        case 'group_id':
        case 'onsubmit':
        case 'fill_gid':
        case 'results_gid':
        case 'filled_by':
        case 'inblock':
        case 'max_submit':
        case 'onetime':
            $this->properties[$name] = (int)$value;
            break;

        case 'enabled':
        case 'moderate':
        case 'captcha':
            $this->properties[$name] = $value == 0 ? 0 : 1;
            break;

        case 'introtext':
        case 'submit_msg':
        case 'noaccess_msg':
        case 'noedit_msg':
        case 'max_submit_msg':
        case 'name':
        case 'email':
        case 'redirect':
            $this->properties[$name] = trim($value);
            break;

        case 'sub_type':    // submission type, only "ajax" or "regular"
            $this->properties[$name] = $value == 'ajax' ? 'ajax' : 'regular';
            break;
        }
    }


    /**
    *   Return a property, if it exists.
    *
    *   @param  string  $name   Name of property to get
    *   @return mixed   Value of property identified as $name
    */
    function __get($name)
    {
        global $_CONF;

        // Special handling, return the site_url by default
        if ($name == 'redirect') {
            if (isset($this->properties['redirect']) &&
                    !empty($this->properties['redirect']))
                return $this->properties['redirect'];
            else
                return $_CONF['site_url'];
        }

        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else {
            return '';
        }
    }


    /**
    *   Sets the instance ID value.
    *   Normally $vals is an array of 'pi_name' and 'instance_id', but it can
    *   also be a single string.
    *
    *   @param  mixed   $vals   ID string, or array of values
    */
    public function setInstance($vals)
    {
        if (is_array($vals)) {
            $val = implode('|', $vals);
        } else {
            $val = $vals;
        }
        $this->instance_id = $val;
    }


    /**
    *   Read all forms variables into the $items array.
    *   Set the $uid paramater to read another user's forms into
    *   the current object instance.
    *
    *   @param  string  $key    Optional specific key to retrieve
    *   @param  integer $uid    Optional user ID
    */
    function Read($id = '', $access=FRM_ACCESS_ADMIN)
    {
        global $_TABLES;

        $this->id = $id;

        // Clear out any existing items, in case we're reusing this instance.
        $this->fields = array();

        $sql = "SELECT fd.* FROM {$_TABLES['forms_frmdef']} fd
            WHERE fd.id = '" . $this->id . "'";
        //echo $sql;die;
        $res1 = DB_query($sql, 1);
        if (!$res1 || DB_numRows($res1) < 1) {
            $this->access = false;
            return false;
        }

        $A = DB_fetchArray($res1, false);
        $this->SetVars($A, true);
        $this->access = $this->hasAccess($access);

        // Now get field information
        $sql = "SELECT *
                FROM {$_TABLES['forms_flddef']}
                WHERE frm_id = '{$this->id}'
                ORDER BY orderby ASC";
        //echo $sql;die;
        $res2 = DB_query($sql, 1);
        while ($A = DB_fetchArray($res2, false)) {
            $this->fields[$A['name']] = Field::getInstance($A);
        }
        return true;
    }


    /**
    *   Read a results set for this form.
    *   If no results set ID is given, then find the first set for the
    *   current user ID.
    *
    *   @param  integer $res_id     Results set to read
    */
    public function ReadData($res_id = 0, $token = '')
    {
        if ($res_id == 0) {
            $res_id = Result::FindResult($this->id, $this->uid);
        } else {
            $res_id = (int)$res_id;
        }

        if ($res_id > 0) {
            $this->Result = new Result($res_id);
            $this->Result->GetValues($this->fields);
        }
    }


    /**
    *   Set all values for this form into local variables.
    *
    *   @param  array   $A          Array of values to use.
    *   @param  boolean $fromdb     Indicate if $A is from the DB or a form.
    */
    function SetVars($A, $fromdb=false)
    {
        if (!is_array($A))
            return false;

        $this->id = $A['id'];
        $this->name = $A['name'];
        $this->introtext = $A['introtext'];
        $this->email = $A['email'];
        $this->owner_id = $A['owner_id'];
        $this->group_id = $A['group_id'];
        $this->fill_gid = $A['fill_gid'];
        $this->results_gid = $A['results_gid'];
        $this->onetime = $A['onetime'];
        $this->submit_msg = $A['submit_msg'];
        $this->noaccess_msg = $A['noaccess_msg'];
        $this->noedit_msg = $A['noedit_msg'];
        $this->max_submit_msg = $A['max_submit_msg'];
        $this->redirect = $A['redirect'];
        $this->max_submit = $A['max_submit'];
        $this->sub_type = $A['sub_type'];

        if ($fromdb) {
            // Coming from the database
            $this->enabled = $A['enabled'];
            $this->onsubmit = $A['onsubmit'];
            $this->captcha = $A['captcha'];
            $this->old_id = $A['id'];
            $this->inblock = $A['inblock'];
            $this->moderate = $A['moderate'];
        } else {
            // This is coming from a form
            $this->enabled = isset($A['enabled']) ? 1 : 0;
            $this->captcha = isset($A['captcha']) ? 1 : 0;
            $this->inblock = isset($A['inblock']) ? 1 : 0;
            $this->moderate = isset($A['moderate']) ? 1 : 0;

            $onsubmit = 0;      // start fresh
            if (isset($A['onsubmit']) && is_array($A['onsubmit'])) {
                foreach($A['onsubmit'] as $key=>$value) {
                    $onsubmit += $value;
                }
            }
            $this->onsubmit = $onsubmit;
            $this->old_id = $A['old_id'];
        }

    }


    /**
    *   Create the edit form for all the forms variables.
    *   Checks the type of edit being done to select the right template.
    *
    *   @param  string  $type   Type of editing- 'edit' or 'registration'
    *   @return string          HTML for edit form
    */
    function EditForm($type = 'edit')
    {
        global $_CONF, $_CONF_FRM, $_USER, $_TABLES, $LANG_FORMS;

        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
        } else {
            $referrer = '';
        }

        $T = FRM_getTemplate('editform', 'editform', 'admin');
        $T->set_var(array(
            'id'    => $this->id,
            'old_id' => $this->old_id,
            'name'  => $this->name,
            'introtext' => $this->introtext,
            'submit_msg' => $this->submit_msg,
            'noaccess_msg' => $this->noaccess_msg,
            'noedit_msg' => $this->noedit_msg,
            'max_submit' => $this->max_submit,
            'max_submit_msg' => $this->max_submit_msg,
            'redirect' => $this->redirect,
            'ena_chk' => $this->enabled == 1 ? 'checked="checked"' : '',
            'mod_chk' => $this->moderate == 1 ? 'checked="checked"' : '',
            'owner_dropdown' => FRM_UserDropdown($this->owner_id),
            'email' => $this->email,
            'admin_group_dropdown' =>
                    FRM_GroupDropdown($this->group_id, 3),
            'user_group_dropdown' =>
                    FRM_GroupDropdown($this->fill_gid, 3),
            'results_group_dropdown' =>
                    FRM_GroupDropdown($this->results_gid, 3),
            'emailowner_chk' => $this->onsubmit & FRM_ACTION_MAILOWNER ?
                        'checked="checked"' : '',
            'emailgroup_chk' => $this->onsubmit & FRM_ACTION_MAILGROUP ?
                        'checked="checked"' : '',
            'emailadmin_chk' => $this->onsubmit & FRM_ACTION_MAILADMIN ?
                        'checked="checked"' : '',
            'store_chk' => $this->onsubmit & FRM_ACTION_STORE ?
                        'checked="checked"' : '',
            'preview_chk' => $this->onsubmit & FRM_ACTION_DISPLAY ?
                        'checked="checked"' : '',
            'doc_url'   => FRM_getDocURL('form_def.html'),
            'referrer'      => $referrer,
            'lang_confirm_delete' => $LANG_FORMS['confirm_form_delete'],
            'captcha_chk' => $this->captcha == 1 ? 'checked="checked"' : '',
            'inblock_chk' => $this->inblock == 1 ? 'checked="checked"' : '',
            'one_chk_' . $this->onetime => 'selected="selected"',
            'iconset'   => $_CONF_FRM['_iconset'],
            'chk_' . $this->sub_type => 'checked="checked"',
            'sub_type' => $this->sub_type,
        ) );
        if (!$this->isNew) {
            $T->set_var('candelete', 'true');
        }

        $T->parse('output', 'editform');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Save all forms items to the database.
    *   Calls each field's Save() method iff there is a corresponding
    *   value set in the $vals array.
    *
    *   @param  array   $vals   Values to save, from $_POST, normally
    *   @return string      HTML error list, or empty for success
    */
    function SaveData($vals)
    {
        global $LANG_FORMS, $_CONF, $_TABLES;

        // Check that $vals is an array; should be from $_POST;
        if (!is_array($vals)) return false;

        // Check that the user has access to fill out this form
        if (!$this->hasAccess(FRM_ACCESS_FILL)) return false;
        if ($this->captcha == 1 &&
                function_exists('plugin_itemPreSave_captcha') ) {
            $msg = plugin_itemPreSave_captcha('general', $vals['captcha']);
            if ($msg != '') return $msg;
        }

        // Check whether the maximum submission number has been reached
        if (!$this->_checkMaxSubmit()) {
            COM_displayMessageAndAbort('7', 'forms');
        }

        if (isset($vals['res_id']) && !empty($vals['res_id'])) {
            $res_id = (int)$vals['res_id'];
            $newSubmission = false;
        } else {
            $newSubmission = true;
            $res_id = 0;
        }

        if (isset($vals['instance_id'])) {
            $this->instance_id = $vals['instance_id'];
        }

        // Check whether the submission can be updated and, if so, whether
        // the res_id from the form is correct
        if ($this->onetime == FRM_LIMIT_ONCE) {
            if ($res_id == 0) {
                // even if no result ID given, see if there is one
                $res_id = Result::FindResult($this->id, $this->uid);
            }
            if ($res_id > 0) return false;       // can't update the submission
        } elseif ($this->onetime == FRM_LIMIT_EDIT) {
            // check that the supplied result ID is the same as the saved one.
            $real_res_id = Result::FindResult($this->id, $this->uid);
            if ($real_res_id != $res_id) {
                return false;
            }
        }   // else, multiple submissions are allowed

        // Create a single value from all free-form fields to submit
        // for the spam check.
        $spamcheck = '';
        foreach ($this->fields as $fld_id=>$fld) {
            switch ($fld->type) {
            case 'text':
            case 'textarea':
            case 'link':
                if (!empty($vals[$fld->name])) {
                    $spamcheck .= "<p>{$vals[$fld->name]}</p>" . LB;
                }
                break;
            }
        }
        if (!empty($spamcheck)) {
            $result = PLG_checkforSpam($spamcheck, $_CONF['spamx']);
            if ($result > 0) {
                COM_updateSpeedlimit($_CONF_FRM['pi_name']);
                COM_displayMessageAndAbort($result, 'spamx', 403, 'Forbidden');
            }
        }

        // Validate the form fields
        $msg = '';
        $invalid_flds = '';
        foreach ($this->fields as $F) {
            $msg = $F->Validate($vals);
            if (!empty($msg)) {
                $invalid_flds .= "<li>$msg</li>\n";
            }
        }
        if (!empty($invalid_flds)) {
            // If fields are invalid, return to the caller with a message
            return $LANG_FORMS['frm_invalid'] .
                    "<br /><ul>\n$invalid_flds</ul>\n";
        }

        // All fields are valid, carry on with the onsubmit actions
        $onsubmit = $this->onsubmit;
        if ($onsubmit & FRM_ACTION_STORE) {
            // Save data to the database
            $this->Result = new Result($res_id);
            $this->Result->setInstance($this->instance_id);
            $this->Result->setModerate($this->moderate);
            $this->res_id = $this->Result->SaveData($this->id, $this->fields,
                    $vals, $this->uid);
        } else {
            $this->res_id = false;
        }

        if ($onsubmit > FRM_ACTION_STORE && $newSubmission) {
            // Emailing or displaying results
            $emails = array();

            // Sending to the form owner
            if ($onsubmit & FRM_ACTION_MAILOWNER) {
                $email = DB_getItem($_TABLES['users'], 'email',
                        "uid='".$this->owner_id."'");
                if (COM_isEmail($email))
                    $emails[$email] = COM_getDisplayName($this->owner_id);
            }

            // Sending to the site admin
            if ($onsubmit & FRM_ACTION_MAILADMIN) {
                $email = DB_getItem($_TABLES['users'], 'email', "uid='2'");
                if (COM_isEmail($email))
                    $emails[$email] = COM_getDisplayName(2);
            }

            // Sending to the admin group.  Need to get all users in group
            if ($onsubmit & FRM_ACTION_MAILGROUP) {
                USES_lib_user();
                $groups = implode(',', USER_getChildGroups($this->group_id));
                $sql = "SELECT DISTINCT uid, username, fullname, email
                    FROM {$_TABLES['users']}, {$_TABLES['group_assignments']}
                    WHERE uid > 1
                    AND {$_TABLES['users']}.status = 3
                    AND email is not null
                    AND email != ''
                    AND {$_TABLES['users']}.uid = ug_uid
                    AND ug_main_grp_id IN ({$groups})";
                $result = DB_query($sql, 1);
                while ($A = DB_fetchArray($result, false)) {
                    if (COM_isEmail($A['email'])) {
                        $emails[$A['email']] = COM_getDisplayName($A['uid']);
                    }
                }
            }

            // Sending to specific addresses. Don't have names here, just
            // addresses
            if ($this->email != '') {
                $addrs = explode(';', $this->email);
                if (is_array($addrs) && !empty($addrs)) {
                    foreach ($addrs as $addr) {
                        if (COM_isEmail($addr)) {
                            $emails[$addr] = $addr;
                        }
                    }
                }
            }

            $dt = new \Date('now', $_CONF['timezone']);
            $subject = sprintf($LANG_FORMS['formsubmission'], $this->name);

            $T = new \Template(FRM_PI_PATH . '/templates/admin');
            $T->set_file('mailresults', 'mailresults.thtml');

            $T->set_var(array(
                    'site_name' => $_CONF['site_name'],
                    'sub_date'   => $dt->format($_CONF['date'], true),
                    'username'  => COM_getDisplayName($this->uid),
                    //'recipient' => COM_getDisplayName($this->owner_id),
                    //'recipient' => $recip_name,
                    'uid'       => $this->uid,
                    'frm_name'  => $this->name,
                    'subject'   => $subject,
            ) );

            $T->set_block('mailresults', 'QueueRow', 'qrow');
            foreach ($this->fields as $field) {
                if (!$field->enabled) continue; // no disabled fields
                if ($field->type == 'calc') {
                    $field->CalcResult($this->fields);
                    $text = $field->value_text;
                } elseif ($field->type == 'static') {
                    continue;
                } elseif ($field->type == 'textarea') {
                    $text = nl2br($field->value_text);
                } else {
                    $text = $field->value_text;
                }

                $T->set_var(array(
                    'fld_name'      => $field->name,
                    'fld_prompt'    => $field->prompt,
                    'fld_value'     => $text,
                ) );
                $T->parse('qrow', 'QueueRow', true);
            }

            $T->parse('output', 'mailresults');
            $message = $T->finish($T->get_var('output'));

            foreach ($emails as $recip_email=>$recip_name) {
                COM_mail(
                    $recip_email,
                    $subject,
                    $message,
                    "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
                    true
                );
            }
        }
        CTL_clearCache();   // So results autotag will work.
        return '';
    }


    /**
    *   Save a form definition.
    *
    *   @param  array   $A      Array of values (e.g. $_POST)
    *   @return string      Error message, empty on success
    */
    function SaveDef($A = '')
    {
        global $_TABLES, $LANG_FORMS;

        if (is_array($A))
            $this->SetVars($A, false);

        $frm_name = $this->name;
        if (empty($frm_name)) {
            return $LANG_FORMS['err_name_required'];
        }

        $changingID = false;
        if ($this->isNew || (!$this->isNew && $this->id != $this->old_id)) {
            if (!$this->isNew) $changingID = true;
            // Saving a new record or changing the ID of an existing one.
            // Make sure the new frm ID doesn't already exist.
            $x = DB_count($_TABLES['forms_frmdef'], 'id', $this->id);
            if ($x > 0) {
                $this->id = COM_makeSid();
            }
        }

        if (!$this->isNew && $this->old_id != '') {
            $sql1 = "UPDATE {$_TABLES['forms_frmdef']} ";
            $sql3 = " WHERE id = '{$this->old_id}'";
        } else {
            $sql1 = "INSERT INTO {$_TABLES['forms_frmdef']} ";
            $sql3 = '';
        }
        $sql2 = "SET id = '{$this->id}',
            name = '" . DB_escapeString($this->name) . "',
            introtext = '" . DB_escapeString($this->introtext) . "',
            submit_msg = '" . DB_escapeString($this->submit_msg) . "',
            noaccess_msg = '" . DB_escapeString($this->noaccess_msg) . "',
            noedit_msg = '" . DB_escapeString($this->noedit_msg) . "',
            max_submit_msg = '" . DB_escapeString($this->max_submit_msg) . "',
            enabled = '{$this->enabled}',
            moderate = '{$this->moderate}',
            onsubmit= '" . DB_escapeString($this->onsubmit) . "',
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            fill_gid = '{$this->fill_gid}',
            results_gid = '{$this->results_gid}',
            redirect = '" . DB_escapeString($this->redirect) . "',
            captcha = '{$this->captcha}',
            inblock = '{$this->inblock}',
            max_submit = '{$this->max_submit}',
            email = '" . DB_escapeString($this->email) . "',
            onetime = '{$this->onetime}',
            sub_type = '{$this->sub_type}'";
        $sql = $sql1 . $sql2 . $sql3;
        DB_query($sql, 1);

        if (!DB_error()) {
            // Now, if the ID was changed, update the field & results tables
            if ($changingID) {
                DB_query("UPDATE {$_TABLES['forms_results']}
                        SET frm_id = '{$this->id}'
                        WHERE frm_id = '{$this->old_id}'", 1);
                DB_query("UPDATE {$_TABLES['forms_flddef']}
                        SET frm_id = '{$this->id}'
                        WHERE frm_id = '{$this->old_id}'", 1);
                Cache::delete('form_' . $this->old_id);  // Clear old form cache
            }
            CTL_clearCache();       // so autotags pick up changes
            Cache::delete('form_' . $this->id);      // Clear plugin cache
            $msg = '';              // no error message if successful
        } else {
            $msg = 5;
        }

        // Finally, if the option is selected, update each field's permission
        // with the form's.
        if (isset($A['reset_fld_perm'])) {
            DB_query("UPDATE {$_TABLES['forms_flddef']} SET
                    fill_gid = '{$this->fill_gid}',
                    results_gid = '{$this->results_gid}'
                WHERE frm_id = '{$this->id}'", 1);
        }
        return $msg;
    }


    /**
    *   Render the form.
    *   Set $mode to 'preview' to have the cancel button return to the admin
    *   list.  Otherwise it might return and re-execute an action, like "copy".
    *
    *   @param  string  $mode   'preview' if this is an admin preview, or blank
    *   @return string  HTML for the form
    */
    public function Render($mode='', $res_id=0)
    {
        global $_CONF, $_TABLES, $LANG_FORMS, $_GROUPS, $_CONF_FRM;

        $retval = '';
        $isAdmin = false;

        // Check that the current user has access to fill out this form.
        if (!$this->hasAccess(FRM_ACCESS_FILL)) {
            return $this->noaccess_msg;
        }

        // Check the number of submissions against the maximum before
        // rendering the form.
        // TODO:  possibly add a new status message for the max exceeded?
        if (!$this->_checkMaxSubmit($mode)) {
            return $this->max_submit_msg;
        }

        $success_msg = 1;
        $actionurl = FRM_PI_URL . '/index.php';
        $saveaction = 'savedata';
        $allow_submit = true;
        if ($mode == 'preview') {
            $referrer = FRM_ADMIN_URL . '/index.php';
            $this->onetime = FRM_LIMIT_MULTI; // otherwise admin might not be able to view
            $allow_submit = false;
        } elseif ($mode == 'edit') {    // admin editing submission
            //$this->ReadData();
            $this->onetime = FRM_LIMIT_EDIT; // allow editing of result
            $success_msg = 3;
            // Refer the submitter back to the results page.
            $referrer = FRM_ADMIN_URL . '/index.php?results=x&frm_id=' .
                    $this->id;
            $actionurl = FRM_ADMIN_URL . '/index.php';
            $saveaction = 'updateresult';
            $isAdmin = true;
        } else {
            if (isset($_POST['referrer'])) {
                $referrer = $_POST['referrer'];
            } elseif (isset($_SERVER['HTTP_REFERER'])) {
                $referrer = $_SERVER['HTTP_REFERER'];
            } else {
                $referrer = '';
            }
        }
        if ($this->inblock == 1) {
            $retval .= COM_startBlock($this->name, '',
               COM_getBlockTemplate('_forms_block', 'header'), $this->id);
        }

        if ($res_id > 0 && $this->onetime < FRM_LIMIT_ONCE) {
            // an existing results id was specifically requested, so display
            // the form with the fields already filled, as long as edits are
            // allowed.
            $this->ReadData($res_id);
        } elseif ($this->onetime > FRM_LIMIT_MULTI) {
            // this is a one-time form, so check that it hasn't already been
            // filled.
            $res_id = Result::FindResult($this->id, $this->uid);
            if ($res_id > 0) {
                if ($this->onetime == FRM_LIMIT_ONCE) {         // no editing
                    return $this->noedit_msg;
                } elseif ($this->onetime == FRM_LIMIT_EDIT) {   // edit allowed
                    $this->ReadData($res_id);
                }
            }
        }

        // Get the result details for a heading when an admin is
        // editing a submission. Have to do it here, after ReadData().
        if ($mode == 'edit') {
            $dt = new \Date($this->Result->dt, $_CONF['timezone']);
            $username = COM_getDisplayName($this->Result->uid);
            $additional = sprintf($LANG_FORMS['edit_result_header'],
                $username,
                $this->Result->uid,
                $this->Result->ip,
                $dt->toMySQL(true));
        } else {
            $additional = '';
        }

        $T = FRM_getTemplate('form', 'form');
        // Set template variables without allowing caching
        $T->set_var(array(
            'frm_action'    => $actionurl,
            'btn_submit'    => $saveaction,
            'frm_id'        => $this->id,
            'introtext'     => $this->introtext,
            'error_msg'     => isset($_POST['forms_error_msg']) ?
                                $_POST['forms_error_msg'] : '',
            'referrer'      => $referrer,
            'res_id'        => $res_id,
            'success_msg'   => self::_stripHtml($success_msg),
            'help_msg'      => self::_stripHtml($this->help_msg),
            'pi_url'        => FRM_PI_URL,
            'submit_disabled' => $allow_submit ? '' : 'disabled="disabled"',
            'instance_id'   => $this->instance_id,
            'iconset'       => $_CONF_FRM['_iconset'],
            'additional'    => $additional,
            'ajax'          => $this->sub_type == 'ajax' ? true : false,
        ), '', false, true );

        $T->set_block('form', 'QueueRow', 'qrow');
        $hidden = '';

        foreach ($this->fields as $F) {
            // Fields that can't be rendered (no permission, calc, disabled)
            // return null. Skip those completely.
            $rendered = $F->displayField($res_id, $mode);
            if ($rendered !== NULL) {
                $T->set_var(array(
                    'prompt'    => PLG_replaceTags($F->prompt),
                    'safe_prompt' => self::_stripHtml($F->prompt),
                    'fieldname' => $F->name,
                    'field'     => $rendered,
                    'help_msg'  => self::_stripHtml($F->help_msg),
                    'spancols'  => isset($F->options['spancols']) && $F->options['spancols'] == 1 ? 'true' : '',
                    'is_required' => $F->access == FRM_FIELD_REQUIRED ? 'true' : '',
                ), '', false, true);
                $T->parse('qrow', 'QueueRow', true);
            }
        }

        // Check to see if CAPTCHA plugin is installed and enabled
        // if yes, call the function to add the CAPTCHA image.
        if ($this->captcha == 1 &&
                function_exists('plugin_templatesetvars_captcha')) {
            $captcha = plugin_templatesetvars_captcha('general', $T);
            $T->set_var('captcha', $captcha);
        }

        $T->set_var('hidden_vars', $hidden);

        PLG_templateSetVars ('form', $T);

        $T->parse('output', 'form');
        $retval .= $T->finish($T->get_var('output'));

        if ($this->inblock) {
            $retval .= COM_endBlock(COM_getBlockTemplate('_forms_block', 'footer'));
        }
        return $retval;
    }


    /**
    *   Create a printable copy of the form results.
    *
    *   @uses   ReadData()
    *   @uses   hasAccess()
    *   @param  integer $res_id     Result set to print.
    *   @param  boolean $admin      True if being accessed as an administrator
    *   @return string              HTML page for printable form data.
    */
    public function Prt($res_id = 0, $admin = false)
    {
        global $_CONF, $_TABLES, $LANG_FORMS, $_USER;

        $res_id = (int)$res_id;
        if ($res_id > 0) {
            // If data hasn't already been read.
            $this->ReadData($res_id);
        }

        // Check that the current user has access and the result id is valid
        if ((empty($this->Result) && $res_id == 0) ||
                ($this->Result->uid != $_USER['uid'] &&
                !$this->hasAccess(FRM_ACCESS_VIEW)) ) {
            return $this->noaccess_msg;
        }

        $dt = new \Date($this->Result->dt, $_CONF['timezone']);

        $T = new \Template(FRM_PI_PATH . '/templates');
        $T->set_file('form', 'print.thtml');
        // Set template variables, without allowing caching
        $T->set_var(array(
            'introtext'     => $this->introtext,
            'title'         => $this->name,
            'filled_by'     => COM_getDisplayName($this->Result->uid),
            'filled_date'   => $dt->format($_CONF['date'], true),
        ), '', false, true);

        if ($admin) $T->set_var('ip_addr', $this->Result->ip);

        $T->set_block('form', 'QueueRow', 'qrow');
        foreach ($this->fields as $F) {
            if (!$F->canViewResults()) continue;

            $data = $F->displayValue($this->fields);
            $prompt = $F->displayPrompt();
            $T->set_var(array(
                'prompt'    => $prompt,
                'fieldname' => $F->fieldname,
                'data'      => $data,
                'colspan'   => isset($F->options['spancols']) && $F->options['spancols'] == 1 ? true : false,
            ), '', false, true);
            $T->parse('qrow', 'QueueRow', true);
        }
        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Delete a form definition.
    *   Deletes a form, removes the field associations, and deletes
    *   user data
    *
    *   @uses   Result::Delete()
    *   @param  integer $frm_id     Optional form ID, current object if empty
    */
    function DeleteDef($frm_id='')
    {
        global $_TABLES;

        // If no ID specified, use the current object
        if ($frm_id == '' && is_object($this)) {
            $frm_id = $this->id;
        } else {
            $frm_id = COM_sanitizeID($frm_id);
        }

        // If still no valid ID, do nothing
        if ($frm_id == '') return;

        DB_delete($_TABLES['forms_frmdef'], 'id', $frm_id);
        //DB_delete($_TABLES['forms_frmXfld'], 'frm_id', $frm_id);
        DB_delete($_TABLES['forms_flddef'], 'frm_id', $frm_id);

        $sql = "SELECT id FROM {$_TABLES['forms_results']}
            WHERE frm_id='$frm_id'";
        $r = DB_query($sql, 1);
        if ($r) {
            while ($A = DB_fetchArray($r, false)) {
                Result::Delete($A['id']);
            }
        }
    }


    /**
    *   Determine if a specific user has a given access level to the form
    *
    *   @param  integer $level  Requested access level
    *   @param  integer $uid    Optional user ID, current user if omitted.
    *   @return boolean     True if the user has access, false if not
    */
    function hasAccess($level, $uid = 0)
    {
        global $_USER;

        if ($uid == 0) $uid = (int)$_USER['uid'];
        if ($uid == $this->owner_id) return true;

        $retval = false;

        switch ($level) {
        case FRM_ACCESS_VIEW:
            if (SEC_inGroup($this->results_gid, $uid)) $retval = true;
            break;
        case FRM_ACCESS_FILL:
            if (SEC_inGroup($this->fill_gid, $uid)) $retval = true;
            break;
        case FRM_ACCESS_ADMIN:
            if (SEC_inGroup($this->group_id, $uid)) $retval = true;
            break;
        }
        return $retval;
    }


    /**
    *   Duplicate this form.
    *   Creates a copy of this form with all its fields.
    *
    *   @uses   Field::Duplicate()
    *   @return string      Error message, empty if successful
    */
    function Duplicate()
    {
        $this->name .= ' -Copy';
        $this->id = COM_makeSid();
        $this->isNew = true;
        $this->SaveDef();

        foreach ($this->fields as $F) {
            $F->frm_id = $this->id;
            $msg = $F->Duplicate();
            if (!empty($msg)) return $msg;
        }
        return '';
    }


    /**
    *   Get the number of responses (result sets) for this form.
    *
    *   @return integer     Response count.
    */
    function Responses()
    {
        global $_TABLES;
        return DB_count($_TABLES['forms_results'], 'frm_id', $this->id);
    }


    /**
    *   Check whether the maximum submission count has been reached.
    *
    *   @uses   Responses()
    *   @return boolean     True if submissions >= max count, false otherwise
    */
    private function _checkMaxSubmit($mode='')
    {
        if ($mode != 'preview' &&
            ($this->max_submit > 0 && $this->Responses() >= $this->max_submit)) {
            return false;
        } else {
            return true;
        }
    }


    /**
    *   Remove HTML and convert other characters.
    *
    *   @param  string  $str    String to sanitize
    *   @return string          String with no quotes or tags
    */
    private static function _stripHtml($str)
    {
        return htmlentities(strip_tags($str));
    }

    /**
    *   Toggle a boolean field in the database
    *
    *   @param  $id     Field def ID
    *   @param  $fld    DB variable to change
    *   @param  $oldval Original value
    *   @return integer New value
    */
    public static function toggle($id, $fld, $oldval)
    {
        global $_TABLES;

        $id = DB_escapeString($id);
        $fld = DB_escapeString($fld);
        $oldval = $oldval == 0 ? 0 : 1;
        $newval = $oldval == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['forms_frmdef']}
                SET $fld = $newval
                WHERE id = '$id'";
        $res = DB_query($sql, 1);
        if (DB_error($res)) {
            COM_errorLog(__CLASS__ . '\\' . __FUNCTION__ . ':: ' . $sql);
            return $oldval;
        } else {
            return $newval;
        }
    }

}

?>
