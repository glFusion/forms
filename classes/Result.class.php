<?php
/**
*   Class to handle the form results.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2017 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;


/**
*   Class for a single form's result
*/
class Result
{
    /** Form fields, array of Field objects
    *   @var array */
    var $fields = array();

    /** Result record ID
    *   @var integer */
    var $id;

    /** Form ID
    *   @var string */
    var $frm_id;

    /** Submitting user ID
    *   @var integer */
    var $uid;

    /** Submission date
    *   @var string */
    var $dt;

    /** Submission approved?
    *   @var boolean */
    var $approved;

    /** Moderation flag
    *   @var boolean */
    var $moderate;

    /** IP address of submitter
    *   @var string */
    var $ip;

    /** Unique token for this submission
    *   @var string */
    var $token;

    /**
    *   Instance ID for the form. Used for stock forms loaded by plugins
    *   @var string */
    var $instance_id;


    /**
    *   Constructor.  Create a forms object for the specified user ID,
    *   or the current user if none specified.  If a key is requested,
    *   then just build the forms for that key (requires a $uid).
    *
    *   @param  integer $uid    Optional user ID
    *   @param  string  $key    Optional key to retrieve
    */
    public function __construct($id=0)
    {
        if ($id > 0) {
            $this->isNew = false;
            $this->id = (int)$id;
            $this->Read($id);
        } else {
            $this->isNew = true;
            $this->id = 0;
            $this->frm_id = '';
            $this->uid = 0;
            $this->dt = 0;
            $this->ip = '';
            $this->token = '';
        }
    }


    /**
    *   Read all forms variables into the $items array.
    *   Set the $uid paramater to read another user's forms into
    *   the current object instance.
    *
    *   @param  integer $id     Result set ID
    *   @return boolea          True on success, False on failure/not found
    */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id > 0) $this->id = (int)$id;

        // Clear out any existing items, in case we're reusing this instance.
        $this->fields = array();

        $sql = "SELECT r.*
            FROM {$_TABLES['forms_results']} r
            WHERE r.id = " . $this->id;
        //echo $sql;die;
        $res1 = DB_query($sql);
        if (!$res1)
            return false;

        $A = DB_fetchArray($res1, false);
        if (empty($A)) return false;

        $this->SetVars($A);

        // Now get field information
        $sql = "SELECT fld_id FROM {$_TABLES['forms_flddef']}
                WHERE frm_id = '{$this->frm_id}'
                ORDER BY orderby ASC";
        $res2 = DB_query($sql);
        while ($A = DB_fetchArray($res2, false)) {
            $this->fields[$A['fld_id']] = new Field($A['fld_id']);
        }

        return true;
    }


    /**
    *   Set all the variables from a form or when read from the DB
    */
    public function SetVars($A)
    {
        if (!is_array($A))
            return false;

        $this->id = (int)$A['id'];
        $this->frm_id = COM_sanitizeID($A['frm_id']);
        $this->dt = (int)$A['dt'];
        $this->approved = $A['approved'] == 0 ? 0 : 1;
        $this->uid = (int)$A['uid'];
        $this->ip = $A['ip'];
        $this->token = $A['token'];
    }


    /**
    *   Set the instance ID, if used
    *
    *   @param  string  $id     Instance ID, may be empty
    */
    public function setInstance($id)
    {
        $this->instance_id = $id;
    }


    /**
    *   Find the result set ID for a single form/user combination.
    *   Assumes only one result per user for a given form.
    *
    *   @param  string  $frm_id     Form ID
    *   @param  integer $uid        Optional user id, default=$_USER['uid']
    *   @return integer             Result set ID
    */
    public static function FindResult($frm_id, $uid=0, $token='')
    {
        global $_TABLES, $_USER;

        $frm_id = COM_sanitizeID($frm_id);
        $uid = $uid == 0 ? $_USER['uid'] : (int)$uid;
        $query = "frm_id='$frm_id' AND uid='$uid'";
        if (!empty($token))
            $query .= " AND token = '" . DB_escapeString($token) . "'";
        $id = (int)DB_getItem($_TABLES['forms_results'], 'id', $query);
        return $id;
    }


    /**
    *   Retrieve all the results for this set into the supplied field objects.
    *
    *   @param  array   $fields     Array of Field objects
    */
    public function GetValues($fields)
    {
        global $_TABLES;

        foreach ($fields as $field) {
            $field->GetValue($this->id);
        }
    }


    /**
    *   Save the field results in a new result set
    *
    *   @param  string  $frm_id     Form ID
    *   @param  array   $fields     Array of Field objects
    *   @param  array   $vals       Array of values ($_POST)
    *   @param  integer $uid        Optional user ID, default=$_USER['uid']
    *   @return mixed       False on failure/invalid, result ID on success
    */
    function SaveData($frm_id, $fields, $vals, $uid = 0)
    {
        global $_USER;

        $this->uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        if ($this->uid == 0) $this->uid = 1;
        $this->frm_id = COM_sanitizeID($frm_id);

        if ($this->isNew) {
            $res_id = $this->Create($frm_id, $this->uid);
        } else {
            $res_id = $this->id;
        }
        if (!$res_id)
            return false;
        foreach ($fields as $field) {
            switch ($field->type) {
            case 'date':
                // special handling for dates since there are three
                // form fields to concatenate
                $hour = isset($vals[$field->name.'_hour']) ?
                            (int)$vals[$field->name.'_hour'] : 0;
                $minute = isset($vals[$field->name.'_minute']) ?
                            (int)$vals[$field->name.'_minute'] : 0;
                $second = isset($vals[$field->name.'_second']) ?
                            (int)$vals[$field->name.'_second'] : 0;
                $year = isset($vals[$field->name.'_year']) ?
                            (int)$vals[$field->name.'_year'] : 0;
                $month = isset($vals[$field->name.'_month']) ?
                            (int)$vals[$field->name.'_month'] : 12;
                $day = isset($vals[$field->name.'_day']) ?
                            (int)$vals[$field->name.'_day'] : 31;
                if ($field->options['century'] == 1 && $year < 100) {
                    $year += ((int)strftime('%C', time()) * 100);
                }
                if ($field->options['timeformat'] == '12') {
                    $hour = FRM_12to24($hour, $vals[$field->name.'_ampm']);
                }
                $newval = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
                        $year, $month, $day, $hour, $minute, $second);
                $field->SaveData($newval, $res_id);
                break;

            case 'time':
                $hour = isset($vals[$field->name.'_hour']) ?
                            (int)$vals[$field->name.'_hour'] : 0;
                $minute = isset($vals[$field->name.'_minute']) ?
                            (int)$vals[$field->name.'_minute'] : 0;
                $second = isset($vals[$field->name.'_second']) ?
                            (int)$vals[$field->name.'_second'] : 0;
                if ($field->options['timeformat'] == '12') {
                    $hour = FRM_12to24($hour, $vals[$field->name.'_ampm']);
                }
                $newval = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
                $field->SaveData($newval, $res_id);
                break;

            default:
                if (isset($vals[$field->name])) {
                // Only save items that are referenced by $vals
                    $field->SaveData($vals[$field->name], $res_id);
                }
                break;
            }
        }
        return $res_id;
    }


    /**
    *   Save all forms items to the database.
    *   Calls each item's Save() method iff there is a corresponding
    *   value set in the $vals array.
    *
    *   @param  string  $frm_id Form ID
    *   @param  array   $vals   Values to save, from $_POST, normally
    */
    function Create($frm_id, $uid = 0)
    {
        global $_TABLES, $_USER;

        $this->uid = $uid == 0 ? $_USER['uid'] : (int)$uid;
        $this->frm_id = COM_sanitizeID($frm_id);
        $this->dt = time();
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $ip = DB_escapeString($this->ip);
        $this->token = md5(time() . rand(1,100));
        $sql = "INSERT INTO {$_TABLES['forms_results']} SET
                frm_id='{$this->frm_id}',
                instance_id='" . DB_escapeString($this->instance_id) . "',
                uid='{$this->uid}',
                dt='{$this->dt}',
                ip = '$ip',
                token = '{$this->token}'";
        //echo $sql;die;
        if ($this->moderate) {
            $sql .= ', approved=0';
        }
        DB_query($sql, 1);
        if (!DB_error()) {
            $this->id = DB_insertID();
        } else {
            $this->id = 0;
        }
        return $this->id;
    }


    /**
    *   Set the moderation flag.
    *   Called from the Form class
    *
    *   @param  boolean $mod    True to moderate results, False to not
    */
    public function setModerate($mod = false)
    {
        $this->moderate = $mod ? true : false;
    }


    /**
    *   Delete a single result set
    *
    *   @param  integer $res_id     Database ID of result to delete
    *   @return boolean     True on success, false on failure
    */
    function Delete($res_id=0)
    {
        global $_TABLES;

        if ($res_id == 0 && is_object($this)) {
            $res_id = $this->id;
        }
        $res_id = (int)$res_id;
        if ($res_id == 0)
            return false;

        self::DeleteValues($res_id);
        DB_delete($_TABLES['forms_results'], 'id', $res_id);
    }


    /**
    *   Delete the form values related to a result set.
    *
    *   @param  integer $res_id Required result ID
    *   @param  integer $uid    Optional user ID
    */
    function DeleteValues($res_id, $uid=0)
    {
        global $_TABLES;

        if ($res_id == 0 && is_object($this)) {
            $res_id = $this->id;
        }
        
        $res_id = (int)$res_id;
        if ($res_id == 0)
            return false;
        $uid = (int)$uid;

        $keys = array('results_id');
        $vals = array($res_id);
        if ($uid > 0) {
            $keys[] = 'uid';
            $vals[] = $uid;
        }

        DB_delete($_TABLES['forms_values'], $keys, $vals);
    }


    /**
    *   Create a printable copy of the form results.
    *
    *   @param  boolean $admin  TRUE if this is done by an administrator
    *   @return string          HTML page for printable form data.
    */
    function Prt($admin = false)
    {
        global $_CONF, $_TABLES, $LANG_FORMS, $_GROUPS;

        // Retrieve the values for this result set
        $this->GetValues($this->fields);

        $dt = new Date($this->dt, $_CONF['timezone']);

        $T = new Template(FRM_PI_PATH . '/templates');
        $T->set_file('form', 'print.thtml');
        $T->set_var(array(
            'introtext'     => $this->introtext,
            'title'         => $this->name,
            'filled_by'     => COM_getDisplayName($this->uid),
            'filled_date'   => $dt->format($_CONF['date'], true),
        ) );

        if ($admin) $T->set_var('ip_addr', $this->ip);

        $T->set_block('form', 'QueueRow', 'qrow');
        foreach ($this->fields as $F) {
            if (!in_array($F->results_gid, $_GROUPS)) {
                continue;
            }
            switch ($F->type) {
            case 'static':
                $data = $F->GetDefault($F->options['default']);
                $prompt = '';
                break;
            case 'textarea':
                $data = nl2br($F->value_text);
                $prompt = $F->prompt == '' ? $F->name : $F->prompt;
                break;
            default;
                $data = $F->value_text;
                $prompt = $F->prompt == '' ? $F->name : $F->prompt;
                break;
            }

            $T->set_var(array(
                'prompt'    => $prompt,
                'fieldname' => $F->name,
                'data'      => $data,
                'colspan'   => $F->options['spancols'] == 1 ? 'true' : '',
            ) );

            $T->parse('qrow', 'QueueRow', true);
        }

        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));

    }


    /**
    *   Returns this result set's token.
    *   The token provides a very basic authentication mechanism when
    *   the after-submission action is to display the results, to ensure
    *   that only the newly-submitted result set is displayed.
    *
    *   @return string      Token saved with this result set
    */
    public function Token()
    {
        return $this->token;
    }

}


?>
