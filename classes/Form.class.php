<?php
/**
 * Class to handle all forms items.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2019 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.4.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms;

/**
 * Handle form objects and their related fields.
 */
class Form
{
    /** Form fields, an array of objects
     * @var array */
    private $fields = array();

    /** Result object for a user submission
     * @var object */
    private $Result = NULL;

    /** Database ID of a result record
     * @var integer */
    private $res_id = 0;

    /** Indicate that the form can be submitted.
     * @var boolean */
    private $allow_submit = true;

    /** Instance of this form, for tying to a plugin entry.
     * @var integer */
    private $instance_id = 0;

    /** Indicate that this is a new record.
     * @var boolean */
    private $isNew = true;

    /** User ID.
     * @var integer */
    private$uid = 0;

    /** Current user's access level.
     * @var integer */
    private $access = 0;

    /** Token string, set to allow anonymous users to view their own submissions.
     * @var string */
    private $token = '';

    /** Form record ID.
     * @var string */
    private $frm_id = '';

    /** Form owner ID.
     * @var integer */
    private $owner_id = 0;

    /** Form group ID.
     * @var integer */
    private $group_id = 0;

    /** Action taken upon form submission.
     * @var integer */
    private $onsubmit = 0;

    /** Group with access to fill out form.
     * @var integer */
    private $fill_gid = 13;     // logged-in users

    /** Group which can see the form results.
     * @var integer */
    private $results_gid = 0;

    /** Show the form in a block?
     * @var boolean */
    private $inblock = 0;

    /** Max allowed submissions. 0 = unlimited.
     * @var integer */
    private $max_submit = 0;

    /** Each user can submit only one time?
     * @var boolean */
    private $onetime = 0;

    /** Is the form enabled?
     * @var boolean */
    private $enabled = 1;

    /** Is the form moderated? (Require approval before saving result?)
     * @var boolean */
    private $req_approval = 0;

    /** Use a CAPTCHA on the form?
     * @var boolean */
    private $captcha = 0;

    /** Intro text to display at the top of the form.
     * @var string */
    private $introtext = '';

    /** Message to show after submission.
     * @var string */
    private $submit_msg = '';

    /** Message to show if the current user doesn't have access to submit.
     * @var string */
    private $noaccess_msg = '';

    /** Message to show if the current user can't edit their previous submission.
     * @var string */
    private $noedit_msg = '';

    /** Message to show if the maximum submission count has been reached.
     * @var string */
    private $max_submit_msg = '';

    /** Name of the form.
     * @var string */
    private $frm_name = '';

    /** Email address to send form submissions.
     * @var string */
    private $email = '';

    /** URL for redirection after submission.
     * @var string */
    private $redirect = '';

    /** Submission type, either `ajax` or `regular`.
     * @var string */
    private $sub_type = 'regular';

    /** Flag to indicate that SPAMX should be used.
     * This will enable or disable the spamx template variable, which can
     * cause issues with auto-completion.
     * @var boolean */
    private $use_spamx = 0;

    /** Help message.
     * @var string */
    private $help_msg = '';


    /**
     * Create a forms object for the specified or current user ID.
     *
     * @param  integer $id     Form ID, empty to create new record
     * @param  integer $access Access level
     */
    public function __construct($id = '', $access=FRM_ACCESS_ADMIN)
    {
        global $_USER, $_CONF_FRM, $_TABLES;

        $this->uid = (int)$_USER['uid'];
        if ($this->uid == 0) {
            $this->uid = 1;    // Anonymous
        }
        $def_group = (int)DB_getItem(
            $_TABLES['groups'],
            'grp_id',
            "grp_name='forms Admin'"
        );
        if ($def_group < 1) $def_group = 1;     // default to Root
        $this->instance_id = '';
        $this->Result = NULL;

        if (!empty($id)) {
            $this->frm_id = COM_sanitizeID($id);
            $this->isNew = false;
            if (!$this->Read($this->frm_id, $access)) {
                $this->frm_id = COM_makeSid();
                $this->isNew = true;
            }
        } else {
            $this->fill_gid = (int)$_CONF_FRM['fill_gid'];
            $this->results_gid = (int)$_CONF_FRM['results_gid'];
            $this->group_id = (int)$def_group;
            $this->frm_id = COM_makeSid();
            $this->use_spamx = (int)$_CONF_FRM['def_spamx'];
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
     * Get the form ID.
     *
     * @return  string      Form record ID
     */
    public function getID()
    {
        return $this->frm_id;
    }


    /**
     * Get the form name.
     *
     * @return  string      Form name
     */
    public function getName()
    {
        return $this->frm_name;
    }


    /**
     * Get all the fields for this form.
     *
     * @return  array   Array of Field objects
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Set the submission type, either `ajax` or `regular`.
     *
     * @param   string  $type   Type of submission.
     * @return  object  $this
     */
    private function setSubType($type)
    {
        $this->sub_type = $type == 'ajax' ? 'ajax' : 'regular';
        return $this;
    }


    /**
     * Get the submission type, either `ajax` or `regular`.
     *
     * @return  string  Type of submission.
     */
    public function getSubType()
    {
        return $this->sub_type == 'ajax' ? 'ajax' : 'regular';
    }


    /**
     * Get the post-submission message.
     *
     * @return  string      Message to show
     */
    public function getSubmitMsg()
    {
        return $this->submit_msg;
    }


    /**
     * Sets the instance ID value.
     * Normally $vals is an array of 'pi_name' and 'instance_id', but it can
     * also be a single string.
     *
     * @param   mixed   $vals   ID string, or array of values
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
     * Check if this form is enabled.
     *
     * @return  boolean     1 if enabled, 0 if not
     */
    public function isEnabled()
    {
        return $this->enabled ? 1 : 0;
    }


    /**
     * Get the `no_access` message for this form.
     *
     * @return  string      Message shown if the user can't access the form
     */
    public function getNoAccessMsg()
    {
        return $this->noaccess_msg;
    }


    /**
     * Get the onsubmit value for the form.
     *
     * @return  integer     Action taken on submission
     */
    public function getOnsubmit()
    {
        return (int)$this->onsubmit;
    }


    /**
     * Check if this is a new record.
     *
     * @return  integer     True if new, False if existing
     */
    public function isNew()
    {
        return $this->isNew ? true : false;
    }


    /**
     * Get the redirect URL for this form.
     *
     * @return  string      Redirect URL
     */
    public function getRedirect()
    {
        global $_CONF;

        // Special handling, return the site_url by default
        if (!empty($this->redirect)) {
            return $this->redirect;
        } else {
            return $_CONF['site_url'];
        }
    }


    /**
     * Get the result ID for this form.
     *
     * @return  integer     Result record ID
     */
    public function getResultID()
    {
        return (int)$this->res_id;
    }


    /**
     * Get the result object for this form.
     *
     * @return  object      Result object
     */
    public function getResult()
    {
        return $this->Result;
    }


    /**
     * Get the group ID that is allowed to view results, e.g. in an autotag.
     *
     * @return  integer     Authorized group ID
     */
    public function getResultsGid()
    {
        return (int)$this->results_gid;
    }


    /**
     * Read all forms variables into the $items array.
     * Set the $uid paramater to read another user's forms into
     * the current object instance.
     *
     * @param   string  $id     Form ID to read
     * @param   integer $access Access level requested
     * @return  boolean     True on success, False if not found
     */
    public function Read($id = '', $access=FRM_ACCESS_ADMIN)
    {
        global $_TABLES;

        $this->frm_id = $id;

        // Clear out any existing items, in case we're reusing this instance.
        $this->fields = array();

        $sql = "SELECT fd.* FROM {$_TABLES['forms_frmdef']} fd
            WHERE fd.frm_id = '" . $this->frm_id . "'";
        //echo $sql;die;
        $res1 = DB_query($sql, 1);
        if (!$res1 || DB_numRows($res1) < 1) {
            $this->access = false;
            return false;
        }

        $A = DB_fetchArray($res1, false);
        $this->SetVars($A, true);
        $this->access = $this->hasAccess($access);

        $this->fields = Field::getByForm($this->frm_id);
        return true;
    }


    /**
     * Read a results set for this form.
     * If no results set ID is given, then find the first set for the
     * current user ID.
     *
     * @param   integer $res_id     Results set to read
     */
    public function ReadData($res_id = 0)
    {
        if ($res_id == 0) {
            $res_id = Result::FindResult($this->frm_id, $this->uid);
        } else {
            $res_id = (int)$res_id;
        }

        if ($res_id > 0) {
            $this->Result = new Result($res_id);
            $this->Result->GetValues($this->fields);
        }
    }


    /**
     * Set all values for this form into local variables.
     *
     * @param   array   $A          Array of values to use.
     * @param   boolean $fromdb     Indicate if $A is from the DB or a form.
     */
    public function SetVars($A, $fromdb=false)
    {
        if (!is_array($A)) {
            return false;
        }

        $this->frm_id = $A['frm_id'];
        $this->frm_name = $A['frm_name'];
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
        $this->use_spamx = isset($A['use_spamx']) && $A['use_spamx'] == 1 ? 1 : 0;

        if ($fromdb) {
            // Coming from the database
            $this->enabled = $A['enabled'];
            $this->onsubmit = $A['onsubmit'];
            $this->captcha = $A['captcha'];
            $this->old_id = $A['frm_id'];
            $this->inblock = $A['inblock'];
            $this->req_approval = $A['req_approval'];
        } else {
            // This is coming from a form
            $this->enabled = isset($A['enabled']) ? 1 : 0;
            $this->captcha = isset($A['captcha']) ? 1 : 0;
            $this->inblock = isset($A['inblock']) ? 1 : 0;
            $this->req_approval = isset($A['req_approval']) ? 1 : 0;

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
     * Create the edit form for all the forms variables.
     * Checks the type of edit being done to select the right template.
     *
     * @param   string  $type   Type of editing- 'edit' or 'registration'
     * @return  string          HTML for edit form
     */
    public function EditForm($type = 'edit')
    {
        global $_CONF, $_CONF_FRM, $_USER, $_TABLES, $LANG_FORMS;

        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
        } else {
            $referrer = '';
        }

        $T = new \Template(FRM_PI_PATH . '/templates/admin');
        $T->set_file(array(
            'editform'  => 'editform.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );
        $T->set_var(array(
            'frm_id'    => $this->frm_id,
            'old_id' => $this->frm_id,
            'frm_name'  => $this->frm_name,
            'introtext' => $this->introtext,
            'submit_msg' => $this->submit_msg,
            'noaccess_msg' => $this->noaccess_msg,
            'noedit_msg' => $this->noedit_msg,
            'max_submit' => $this->max_submit,
            'max_submit_msg' => $this->max_submit_msg,
            'redirect' => $this->redirect,
            'ena_chk' => $this->enabled == 1 ? 'checked="checked"' : '',
            'mod_chk' => $this->req_approval == 1 ? 'checked="checked"' : '',
            'owner_dropdown' => $this->_userDropdown(),
            'email' => $this->email,
            'admin_group_dropdown' => $this->_groupDropdown($this->group_id),
            'user_group_dropdown' => $this->_groupDropdown($this->fill_gid),
            'results_group_dropdown' => $this->_groupDropdown($this->results_gid),
            'emailowner_chk' => $this->onsubmit & FRM_ACTION_MAILOWNER ?
                        'checked="checked"' : '',
            'emailgroup_chk' => $this->onsubmit & FRM_ACTION_MAILGROUP ?
                        'checked="checked"' : '',
            'emailadmin_chk' => $this->onsubmit & FRM_ACTION_MAILADMIN ?
                        'checked="checked"' : '',
            'emailuser_chk' => $this->onsubmit & FRM_ACTION_MAILOWNER ?
                        'checked="checked"' : '',
            'preview_chk' => $this->onsubmit & FRM_ACTION_DISPLAY ?
                        'checked="checked"' : '',
            'doc_url'   => FRM_getDocURL('form_def.html'),
            'referrer'      => $referrer,
            'lang_confirm_delete' => $LANG_FORMS['confirm_form_delete'],
            'captcha_chk' => $this->captcha == 1 ? 'checked="checked"' : '',
            'inblock_chk' => $this->inblock == 1 ? 'checked="checked"' : '',
            'spamx_chk' => $this->use_spamx == 1 ? 'checked="checked"' : '',
            'one_chk_' . $this->onetime => 'selected="selected"',
            'chk_' . $this->sub_type => 'checked="checked"',
            'sub_type' => $this->sub_type,
        ) );
        if (!$this->isNew) {
            $T->set_var('candelete', 'true');
        }

        $T->parse('tooltipster', 'tips');
        $T->parse('output', 'editform');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Save all forms items to the database.
     * Calls each field's Save() method iff there is a corresponding
     * value set in the $vals array.
     *
     * @param   array   $vals   Values to save, from $_POST, normally
     * @return  string      HTML error list, or empty for success
     */
    public function SaveData($vals)
    {
        global $LANG_FORMS, $_CONF, $_TABLES, $_CONF_FRM;

        // Check that $vals is an array; should be from $_POST;
        if (!is_array($vals)) {
            return false;
        }

        // Check that the user has access to fill out this form
        if (!$this->hasAccess(FRM_ACCESS_FILL)) return false;
        if ($this->captcha == 1 &&
                function_exists('plugin_itemPreSave_captcha') ) {
            $msg = plugin_itemPreSave_captcha('general', $vals['captcha']);
            if ($msg != '') {
                return $msg;
            }
        }

        // Check whether the maximum submission number has been reached
        if (!$this->_checkMaxSubmit()) {
            COM_displayMessageAndAbort('7', 'forms');
        }

        if (isset($vals['res_id']) && !empty($vals['res_id'])) {
            $res_id = (int)$vals['res_id'];
            //$newSubmission = false;
        } else {
            //$newSubmission = true;
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
                $res_id = Result::FindResult($this->frm_id, $this->uid);
            }
            if ($res_id > 0) return false;       // can't update the submission
        } elseif ($this->onetime == FRM_LIMIT_EDIT) {
            // check that the supplied result ID is the same as the saved one.
            $real_res_id = Result::FindResult($this->frm_id, $this->uid);
            if ($real_res_id != $res_id) {
                return false;
            }
        }   // else, multiple submissions are allowed

        // Create a single value from all free-form fields to submit
        // for the spam check.
        $spamcheck = '';
        foreach ($this->fields as $fld_id=>$fld) {
            switch ($fld->getType()) {
            case 'text':
            case 'textarea':
            case 'link':
                if (!empty($vals[$fld->getName()])) {
                    $spamcheck .= "<p>{$vals[$fld->getName()]}</p>" . LB;
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
            } else {
                /*if (isset($vals[$F->getName()])) {
                    $F->setValue($vals[$F->getName()]);
            }*/
                $F->setValue($vals);
            }
        }
        if (!empty($invalid_flds)) {
            // If fields are invalid, return to the caller with a message
            return $LANG_FORMS['frm_invalid'] .
                    "<br /><ul>\n$invalid_flds</ul>\n";
        }

        // All fields are valid, carry on with the onsubmit actions.
        $onsubmit = $this->onsubmit;

        // Always save data to the database.
        $this->Result = new Result($res_id);
        $this->Result->setInstance($this->instance_id)
            ->setModerate($this->req_approval);
        $this->res_id = $this->Result->SaveData(
            $this->frm_id, $this->fields,
            $vals, $this->uid
        );

        // Emailing or displaying results
        $emails = array();

        // Sending to the form owner
        if ($onsubmit & FRM_ACTION_MAILOWNER) {
            $email = DB_getItem($_TABLES['users'], 'email',
                        "uid='".$this->owner_id."'");
            if (COM_isEmail($email)) {
                $emails[$email] = COM_getDisplayName($this->owner_id);
            }
        }

        // Sending to the site admin
        if ($onsubmit & FRM_ACTION_MAILADMIN) {
            $email = DB_getItem($_TABLES['users'], 'email', "uid='2'");
            if (COM_isEmail($email)) {
                $emails[$email] = COM_getDisplayName(2);
            }
        }

        // Sending to the admin group. Need to get all users in the group,
        // excluding Root since it is in every group.
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
                    AND ug_main_grp_id IN ({$groups})
                    AND ug_main_grp_id <> 1";
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
                    if (COM_isEmail($addr) && !isset($emails[$addr])) {
                        $emails[$addr] = $addr;
                    }
                }
            }
        }

        if (!empty($emails)) {
            $dt = new \Date('now', $_CONF['timezone']);
            $subject = sprintf($LANG_FORMS['formsubmission'], $this->frm_name);

            $T = new \Template(FRM_PI_PATH . '/templates/admin');
            $T->set_file('mailresults', 'mailresults.thtml');
            $T->set_var(array(
                'site_name' => $_CONF['site_name'],
                'sub_date'   => $dt->format($_CONF['date'], true),
                'username'  => COM_getDisplayName($this->uid),
                //'recipient' => COM_getDisplayName($this->owner_id),
                //'recipient' => $recip_name,
                'uid'       => $this->uid,
                'frm_name'  => $this->frm_name,
                'subject'   => $subject,
            ) );

            $T->set_block('mailresults', 'QueueRow', 'qrow');
            foreach ($this->fields as $Fld) {
                if (!$Fld->enabled) continue; // no disabled fields
                $Fld->setCanviewResults(true);
                $T->set_var(array(
                    'fld_name'      => $Fld->frm_name,
                    'fld_prompt'    => $Fld->prompt,
                    'fld_value'     => $Fld->displayValue($this->fields),
                    'spancols'      => $Fld->hasOption('spancols'),
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
     * Save a form definition.
     *
     * @param   array   $A      Array of values (e.g. $_POST)
     * @return  string      Error message, empty on success
     */
    public function SaveDef($A = '')
    {
        global $_TABLES, $LANG_FORMS;

        if (is_array($A)) {
            $this->SetVars($A, false);
        }
        if (empty($this->frm_name)) {
            return $LANG_FORMS['err_name_required'];
        }

        $changingID = false;
        if (
            $this->isNew ||
            (!$this->isNew && $this->frm_id != $this->old_id)
        ) {
            if (!$this->isNew) {
                $changingID = true;
            }
            // Saving a new record or changing the ID of an existing one.
            // Make sure the new frm ID doesn't already exist.
            $x = DB_count($_TABLES['forms_frmdef'], 'frm_id', $this->frm_id);
            if ($x > 0) {
                $this->frm_id = COM_makeSid();
            }
        }

        if (!$this->isNew && $this->old_id != '') {
            $sql1 = "UPDATE {$_TABLES['forms_frmdef']} ";
            $sql3 = " WHERE frm_id = '{$this->old_id}'";
        } else {
            $sql1 = "INSERT INTO {$_TABLES['forms_frmdef']} ";
            $sql3 = '';
        }
        $sql2 = "SET frm_id = '{$this->frm_id}',
            frm_name = '" . DB_escapeString($this->frm_name) . "',
            introtext = '" . DB_escapeString($this->introtext) . "',
            submit_msg = '" . DB_escapeString($this->submit_msg) . "',
            noaccess_msg = '" . DB_escapeString($this->noaccess_msg) . "',
            noedit_msg = '" . DB_escapeString($this->noedit_msg) . "',
            max_submit_msg = '" . DB_escapeString($this->max_submit_msg) . "',
            enabled = '{$this->enabled}',
            req_approval = '{$this->req_approval}',
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
            use_spamx = '{$this->use_spamx}',
            sub_type = '{$this->sub_type}'";
        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            // Now, if the ID was changed, update the field & results tables
            if ($changingID) {
                DB_query("UPDATE {$_TABLES['forms_results']}
                        SET frm_id = '{$this->frm_id}'
                        WHERE frm_id = '{$this->old_id}'", 1);
                DB_query("UPDATE {$_TABLES['forms_flddef']}
                        SET frm_id = '{$this->frm_id}'
                        WHERE frm_id = '{$this->old_id}'", 1);
                Cache::delete('form_' . $this->old_id);  // Clear old form cache
            }
            CTL_clearCache();       // so autotags pick up changes
            Cache::delete('form_' . $this->frm_id);      // Clear plugin cache
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
                WHERE frm_id = '{$this->frm_id}'", 1);
        }
        return $msg;
    }


    /**
     * Render the form.
     * Set $mode to 'preview' to have the cancel button return to the admin
     * list.  Otherwise it might return and re-execute an action, like "copy".
     *
     * @param   string  $mode   'preview' if this is an admin preview, or blank
     * @param   integer $res_id     Result set ID to display data
     * @return  string  HTML for the form
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
        $not_inline = true;
        switch ($mode) {
        case 'preview':
            $isAdmin = plugin_isadmin_forms();
            if ($isAdmin) {
                $referrer = FRM_ADMIN_URL . '/index.php';
            } else {
                $referrer = FRM_PI_URL . '/index.php?listforms';
            }
            $this->onetime = FRM_LIMIT_MULTI; // otherwise admin might not be able to view
            $allow_submit = false;
            break;
        case 'edit':    // admin editing submission
            //$this->ReadData();
            $this->onetime = FRM_LIMIT_EDIT; // allow editing of result
            $success_msg = 3;
            // Refer the submitter back to the results page.
            $referrer = FRM_ADMIN_URL . '/index.php?results=x&frm_id=' .
                    $this->frm_id;
            $actionurl = FRM_ADMIN_URL . '/index.php';
            $isAdmin = true;
            $saveaction = 'updateresult';
            break;
        case 'inline':
            $not_inline = false;
        default:
            if (isset($_POST['referrer'])) {
                $referrer = $_POST['referrer'];
            } elseif (isset($_SERVER['HTTP_REFERER'])) {
                $referrer = $_SERVER['HTTP_REFERER'];
            } else {
                $referrer = '';
            }
            break;
        }
        if ($this->inblock == 1) {
            $retval .= COM_startBlock($this->frm_name, '',
               COM_getBlockTemplate('_forms_block', 'header'), $this->frm_id);
        }

        if ($res_id > 0 && $this->onetime < FRM_LIMIT_ONCE) {
            // an existing results id was specifically requested, so display
            // the form with the fields already filled, as long as edits are
            // allowed.
            $this->ReadData($res_id);
        } elseif ($this->onetime > FRM_LIMIT_MULTI) {
            // this is a one-time form, so check that it hasn't already been
            // filled.
            $res_id = Result::FindResult($this->frm_id, $this->uid);
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
            $dt = new \Date($this->Result->getTimestamp(), $_CONF['timezone']);
            $username = COM_getDisplayName($this->Result->getUid());
            $additional = sprintf($LANG_FORMS['edit_result_header'],
                $username,
                $this->Result->getUid(),
                $this->Result->getIP(),
                $dt->toMySQL(true));
        } else {
            $additional = '';
        }

        $T = new \Template(FRM_PI_PATH . '/templates');
        $T->set_file('form', 'form.thtml');
        // Set template variables without allowing caching
        $T->set_var(array(
            'frm_action'    => $actionurl,
            'btn_submit'    => $saveaction,
            'frm_id'        => $this->frm_id,
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
            'additional'    => $additional,
            'ajax'          => $this->sub_type == 'ajax' ? true : false,
            'not_inline'    => $not_inline,
            'use_spamx'     => $this->use_spamx,
        ), '', false, true );

        $T->set_block('form', 'QueueRow', 'qrow');
        $hidden = '';

        foreach ($this->fields as $Field) {
            // Fields that can't be rendered (no permission, calc, disabled)
            // return null. Skip those completely.
            $rendered = $Field->displayField($res_id, $mode);
            if ($rendered !== NULL) {
                $T->set_var(array(
                    'prompt'    => PLG_replaceTags($Field->getPrompt()),
                    'safe_prompt' => self::_stripHtml($Field->getPrompt()),
                    'fieldname' => $Field->getName(),
                    'field'     => $rendered,
                    'help_msg'  => self::_stripHtml($Field->getHelpMsg()),
                    'spancols'  => $Field->hasOption('spancols'),
                    'is_required' => $Field->getAccess() == FRM_FIELD_REQUIRED ? 'true' : '',
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
     * Preview the form.
     *
     * @return  string      HTML for form preview
     */
    public function Preview()
    {
        $T = new \Template(FRM_PI_PATH . '/templates/');
        $T->set_file('header', 'preview_header.thtml');
        $T->set_var(array(
            'frm_name'      => $this->getName(),
            'frm_id'        => $this->getID(),
            'frm_link'      => FRM_PI_URL . '/index.php?frm_id=' . $this->getID(),
        ) );
        $T->parse('output', 'header');
        $retval = $T->finish($T->get_var('output'));
        $retval .= $this->Render('preview');
        return $retval;
    }


    /**
     * Create a printable copy of the form results.
     *
     * @uses    ReadData()
     * @uses    hasAccess()
     * @param   integer $res_id     Result set to print.
     * @param   boolean $admin      True if being accessed as an administrator
     * @return  string              HTML page for printable form data.
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
        if (
            (empty($this->Result) && $res_id == 0) ||
            ($this->Result->getUid() != $_USER['uid'] && !$this->hasAccess(FRM_ACCESS_VIEW))
        ) {
            return $this->noaccess_msg;
        }

        $dt = new \Date($this->Result->getTimestamp(), $_CONF['timezone']);
        $T = new \Template(FRM_PI_PATH . '/templates');
        $T->set_file('form', 'print.thtml');
        // Set template variables, without allowing caching
        $T->set_var(array(
            'introtext'     => $this->introtext,
            'title'         => $this->frm_name,
            'filled_by'     => COM_getDisplayName($this->Result->getUid()),
            'filled_date'   => $dt->format($_CONF['date'], true),
        ), '', false, true);

        if ($admin) $T->set_var('ip_addr', $this->Result->getIP());

        $T->set_block('form', 'QueueRow', 'qrow');
        foreach ($this->fields as $Field) {
            if ($this->uid == $_USER['uid'] && $this->token == $this->Result->getToken()) {
                $Field->setCanviewResults(true);
            }
            $data = $Field->displayValue($this->fields);
            $prompt = $Field->displayPrompt();
            $T->set_var(array(
                'prompt'    => $prompt,
                'data'      => $data,
                'spancols'  => $Field->hasOption('spancols'),
            ), '', false, true);
            $T->parse('qrow', 'QueueRow', true);
        }
        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Delete a form definition.
     * Deletes a form, removes the field associations, and deletes
     * user data
     *
     * @uses    Result::Delete()
     * @param   integer $frm_id     Optional form ID, current object if empty
     */
    public function DeleteDef()
    {
        global $_TABLES;

        // If still no valid ID, do nothing
        if ($this->frm_id == '') return;

        $frm_id = DB_escapeString($this->frm_id);
        DB_delete($_TABLES['forms_frmdef'], 'frm_id', $frm_id);
        Field::deleteByForm($frm_id);
        Result::deleteByForm($frm_id);
    }


    /**
     * Reset a form by deleting all related results.
     */
    public function Reset()
    {
        Result::deleteByForm($this->frm_id);
    }


    /**
     * Determine if a specific user has a given access level to the form.
     *
     * @param   integer $level  Requested access level
     * @param   integer $uid    Optional user ID, current user if omitted.
     * @return  boolean     True if the user has access, false if not
     */
    public function hasAccess($level, $uid = 0)
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
     * Duplicate this form.
     * Creates a copy of this form with all its fields.
     *
     * @uses    Field::Duplicate()
     * @return  string      Error message, empty if successful
     */
    public function Duplicate()
    {
        $this->frm_name .= ' -Copy';
        $this->frm_id = COM_makeSid();
        $this->isNew = true;
        $this->SaveDef();

        foreach ($this->fields as $Field) {
            $Field->setFormID($this->frm_id);
            $msg = $Field->Duplicate();
            if (!empty($msg)) {
                return $msg;
            }
        }
        return '';
    }


    /**
     * Get the number of responses (result sets) for this form.
     *
     * @return  integer     Response count.
     */
    private function countResponses()
    {
        global $_TABLES;
        return DB_count($_TABLES['forms_results'], 'frm_id', $this->frm_id);
    }


    /**
     * Check whether the maximum submission count has been reached.
     *
     * @uses    countResponses()
     * @param   string  $mode   Viewing mode
     * @return  boolean     True if submissions >= max count, false otherwise
     */
    private function _checkMaxSubmit($mode='')
    {
        if ($mode != 'preview' &&
            ($this->max_submit > 0 && $this->countResponses() >= $this->max_submit)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Remove HTML and convert other characters.
     *
     * @param   string  $str    String to sanitize
     * @return  string          String with no quotes or tags
     */
    private static function _stripHtml($str)
    {
        return htmlentities(strip_tags($str));
    }


    /**
     * Toggle a boolean field in the database
     *
     * @param   $id     Field def ID
     * @param   $fld    DB variable to change
     * @param   $oldval Original value
     * @return  integer New value
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
                WHERE frm_id = '$id'";
        $res = DB_query($sql, 1);
        if (DB_error($res)) {
            COM_errorLog(__CLASS__ . '\\' . __FUNCTION__ . ':: ' . $sql);
            return $oldval;
        } else {
            return $newval;
        }
    }


    /**
     * Set the token value.
     * This is used to set the token supplied in the URL when a user
     * is viewing their own submission.
     *
     * @param   string  $token  Unique token value
     */
    public function setToken($token)
    {
        $this->token = $token;
    }


    /**
     * Export all form results as a CSV file.
     *
     * @return  string      CSV file contents
     */
    public function exportResultsCSV()
    {
        $Results = Result::getByForm($this->frm_id);
        if (empty($Results)) {
            return '';
        }

        $fields = array('"UserID"', '"Submitted"');
        foreach ($this->getFields() as $Field) {
            if (!$Field->isEnabled()) continue;     // ignore disabled fields
            $fields[] = '"' . $Field->getName() . '"';
        }
        $retval = join(',', $fields) . "\n";
        foreach ($Results as $Result) {
            $vals = $Result->getValues($this->getFields());
            $fields = array(
                COM_getDisplayName($Result->getUid()),
                strftime('%Y-%m-%d %H:%M', $Result->getTimestamp()),
            );
            foreach ($this->getFields() as $Field) {
                if (!$Field->isEnabled()) continue;     // ignore disabled fields
                if (isset($vals[$Field->getName()])) {
                    $Field->setValue($vals[$Field->getName()]);
                    $fields[] = '"' . str_replace('"', '""', $Field->getValueForCSV($this->getFields())) . '"';
                }
            }
            $retval .= join(',', $fields) . "\n";
        }
        return $retval;
    }


    /**
     * Uses lib-admin to list the forms definitions and allow updating.
     *
     * @param   boolean $isAdmin    True if this is an admin, False if not
     * @return  string  HTML for the list
     */
    public static function adminList($isAdmin=true)
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS, $_USER, $_GROUPS;

        $retval = '';
        // Validate admin status
        $isAdmin = $isAdmin && plugin_isadmin_forms();
        if (!$isAdmin) {
            $perm_sql = " AND (owner_id='". (int)$_USER['uid'] . "'
                OR group_id IN (" . implode(',', $_GROUPS). "))";
            $base_url = FRM_PI_URL;
        } else {
            $base_url = FRM_ADMIN_URL;
            $perm_sql = '';
        }

        $header_arr = array(
            array(
                'text' => 'ID',
                'field' => 'frm_id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_FORMS['submissions'],
                'field' => 'submissions',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['name'],
                'field' => 'frm_name',
                'sort' => true,
            ),
            array(
                'text' => $LANG_FORMS['enabled'],
                'field' => 'enabled',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['action'],
                'field' => 'action',
                'sort' => false,
            ),
            array(
                'text' => $LANG_FORMS['reset'],
                'field' => 'reset',
                'sort' => false,
                'align' => 'center',
            ),
        );

        if ($isAdmin) {
            $header_arr[] = array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            );
            $header_arr[] = array(
                'text' => $LANG_ADMIN['copy'],
                'field' => 'copy',
                'sort' => false,
                'align' => 'center',
            );
            $header_arr[] = array(
                'text' => $LANG_FORMS['view_html'],
                'field' => 'view_html',
                'sort' => false,
                'align' => 'center',
            );
            $header_arr[] = array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            );
        }

        $text_arr = array();
        $query_arr = array(
            'table' => 'forms_frmdef',
            'sql' => "SELECT *
                FROM {$_TABLES['forms_frmdef']}
                WHERE 1=1 $perm_sql",
            'query_fields' => array('frm_name'),
            'default_filter' => '',
        );
        $extras = array(
            'base_url' => $base_url,
            'isAdmin' => $isAdmin,
        );
        $defsort_arr = array('field' => 'frm_name', 'direction' => 'ASC');
        $form_arr = array();
        $retval .= ADMIN_list(
            'forms_adminlistform',
            array(__CLASS__, 'getListField'),
            $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', $extras, '', $form_arr
        );
        return $retval;
    }

    
    /**
     * Determine what to display in the admin list for each form.
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @param   array   $extras     Extra verbatim values from adminList()
     * @return  string              HTML for the field cell
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr, $extras)
    {
        global $_CONF, $LANG_ACCESS, $LANG_FORMS, $_TABLES, $_CONF_FRM, $_LANG_ADMIN;

        $retval = '';
        switch($fieldname) {
        case 'edit':
            $url = $extras['base_url'] . "/index.php?editform=x&amp;frm_id={$A['frm_id']}";
            $retval = COM_createLink(
                Icon::getHTML('edit'),
                $url
            );
            break;

        case 'copy':
            $url = $extras['base_url'] . "/index.php?copyform=x&amp;frm_id={$A['frm_id']}";
            $retval = COM_createLink(
                Icon::getHtml('copy'),
                $url
            );
            break;

        case 'view_html':
            $url = $extras['base_url'] . "/index.php?showhtml=x&amp;frm_id={$A['frm_id']}";
            $retval = COM_createLink(
                Icon::getHTML('code'),
                '#',
                array(
                    'onclick' => "popupWindow('$url', '', 640, 480, 1); return false;",
                )
            );
            break;

        case 'delete':
            $retval = COM_createLink(
                Icon::getHTML('delete'),
                $extras['base_url'] . "/index.php?deleteFrmDef=x&frm_id={$A['frm_id']}",
                array(
                    'onclick' => "return confirm('{$LANG_FORMS['confirm_form_delete']}?');",
                )
            );
            break;

        case 'reset':
            $retval = COM_createLink(
                Icon::getHTML(
                    'reset',
                    'uk-text-danger tooltip',
                    array(
                        'title' => $LANG_FORMS['reset_results'],
                    )
                ),
                $extras['base_url'] . "/index.php?reset=x&frm_id={$A['frm_id']}",
                array(
                    'onclick' => "return confirm('{$LANG_FORMS['confirm_form_reset']}?');",
                )
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
            $retval = "<input name=\"{$fieldname}_{$A['frm_id']}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='FRMtoggleEnabled(this, \"{$A['frm_id']}\", \"form\", \"{$fieldname}\", \"" . $extras['base_url'] . "\");' " .
                "/>\n";
            break;

        case 'frm_name':
            $retval = COM_createLink(
                $fieldvalue,
                $extras['base_url'] . '/index.php?preview&frm_id=' . $A['frm_id'],
                array(
                    'class' => 'tooltip',
                    'title' => $LANG_FORMS['preview'],
                )
            );
            break;

        case 'submissions':
            $url = $extras['base_url'] . '/index.php?results=x&frm_id=' . $A['frm_id'];
            $txt = (int)DB_count($_TABLES['forms_results'], 'frm_id', $A['frm_id']);
            $retval = COM_createLink($txt, $url,
                array(
                    'class' => 'tooltip',
                    'title' => $LANG_FORMS['form_results'],
                )
            );
            break;
    
        case 'action':
            $retval = '<select name="action"
                onchange="javascript: document.location.href=\'' .
                $extras['base_url'] . '/index.php?frm_id=' . $A['frm_id'] .
                '&\'+this.options[this.selectedIndex].value">'. "\n";
            $retval .= '<option value="">--' . $LANG_FORMS['select'] . '--</option>'. "\n";
            $retval .= '<option value="preview">' . $LANG_FORMS['preview'] . '</option>'. "\n";
            $retval .= '<option value="results">' . $LANG_FORMS['form_results'] . '</option>'. "\n";
            $retval .= '<option value="export">' . $LANG_FORMS['export'] . '</option>'. "\n";
            $retval .= "</select>\n";
            break;

        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }
    
    
    /**
     * Create a group selection.
     *
     * @param   integer $group_id   Selected group ID
     * @return  string      Dropdown list
     */
    private function _groupDropdown($group_id=0)
    {
        global $_TABLES;

        return COM_optionList(
            $_TABLES['groups'],
            'grp_id,grp_name',
            $group_id
        );
    }

    
    /**
     * Create a user selection using the current owner ID as the default.
     *
     * @return  string      Dropdown list
     */
    private function _userDropdown()
    {
        global $_TABLES;

        return COM_optionList(
            $_TABLES['users'],
            'uid,username',
            $this->owner_id
        );
    }


    /**
     * Check if the current user is the owner or in the owner group.
     * Used to determind if the form can be previewed or results displayed.
     *
     * @return  boolean     True if the user is an owner, False if not
     */
    public function isOwner()
    {
        global $_USER, $_GROUPS;

        return $this->owner_id == $_USER['uid'] || in_array($this->group_id, $_GROUPS);
    }

}

?>
