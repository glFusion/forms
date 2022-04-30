<?php
/**
 * Class to handle the form results.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.6.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Result class for the Forms plugin.
 */
class Result
{
    /** Form fields, array of Field objects
     * @var array */
    private $fields = array();

    /** Result record ID
     * @var integer */
    private $res_id = 0;

    /** Form ID
     * @var string */
    private $frm_id = '';

    /** Submitting user ID
     * @var integer */
    private $uid = 0;

    /** Submission date
     * @var string */
    private $dt = '';

    /** Submission approved?
     * @var boolean */
    private $approved = 1;

    /** Flag to indicate this result requires approval.
     * @var boolean */
    private $moderate = false;

    /** IP address of submitter
     * @var string */
    private $ip = '';

    /** Unique token for this submission
     * @var string */
    private $token = '';

    /** Instance ID for the form. Used for stock forms loaded by plugins
     * @var string */
    private $instance_id = '';


    /**
     * Constructor.
     * If a result set ID is specified, it is read. If an array is given
     * then the fields are simply copied from the array, e.g. when displaying
     * many results in a table.
     *
     * @param  mixed   $id     Result set ID or array from DB
     */
    public function __construct($id=0)
    {
        if (is_array($id)) {
            // Already read from the DB, just load the values
            $this->setVars($id);
        } elseif ($id > 0) {
            // Result ID supplied, read it
            $this->Read($id);
        }
    }


    /**
     * Read all forms variables into the $items array.
     * Set the $uid paramater to read another user's forms into
     * the current object instance.
     *
     * @param   integer $id     Result set ID
     * @return  boolean         True on success, False on failure/not found
     */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id > 0) {
            $this->res_id = (int)$id;
        }

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['forms_results']}
                WHERE res_id = ?",
                array($this->res_id),
                array(Database::INTEGER)
            )->fetchAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }
        if (is_array($data)) {
            $this->setVars($data);
            return true;
        } else {
            $this->res_id = 0;
            return false;
        }
    }


    /**
     * Set all the variables from a form or when read from the DB.
     *
     * @param   array   $A      Array of values
     */
    public function setVars($A)
    {
        if (!is_array($A)) {
            return false;
        }

        $this->res_id = (int)$A['res_id'];
        $this->frm_id = COM_sanitizeID($A['frm_id']);
        $this->dt = (int)$A['dt'];
        $this->approved = $A['approved'] == 0 ? 0 : 1;
        $this->uid = (int)$A['uid'];
        $this->token = $A['token'];
        if (isset($A['instance_id'])) {
            $this->instance_id = $A['instance_id'];
        }

        if (isset($A['ip'])) {
            $this->setIP($A['ip']);
        } else {
            $this->setIP();
        }
        return $this;
    }


    /**
     * Set the instance ID, if used.
     *
     * @param   string  $id     Instance ID, may be empty
     * @return  object  $this
     */
    public function setInstance($id)
    {
        $this->instance_id = $id;
        return $this;
    }


    /**
     * Get the instance ID of this result.
     *
     * @return  string      Instance ID
     */
    public function getInstance()
    {
        return $this->instance_id;
    }


    /**
     * Find the result set ID for a single form/user combination.
     * Assumes only one result per user for a given form.
     *
     * @param   string  $frm_id     Form ID
     * @param   integer $uid        Optional user id, default=$_USER['uid']
     * @param   string  $token      Resultset token, if provided
     * @return  integer             Result set ID
     */
    public static function findResult($frm_id, $uid=0, $token='')
    {
        global $_TABLES, $_USER;

        $db = Database::getInstance();
        $uid = $uid == 0 ? $_USER['uid'] : (int)$uid;
        $criteria = array('uid' => $uid, 'frm_id' => $frm_id);
        if (!empty($token)) {
            $criteria['token'] = $token;
        }

        try {
            $res_id = $db->getItem($_TABLES['forms_results'], 'id', $criteria);
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            $res_id = 0;
        }
        return $res_id;
    }


    /**
     * Get all the results submitted for a given form.
     *
     * @param   object  $Form   Form object
     * @return  array       Array of Result objects
     */
    public static function getByForm(Form $Form) : array
    {
        global $_TABLES;

        $retval = array();
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['forms_results']}
                WHERE frm_id = ?",
                array($Form->getID()),
                array(Database::STRING)
            )->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }
        if (is_array($data)) {
            foreach ($data as $A) {
                $retval[$A['res_id']] = new self($A);
            }
        }
        return $retval;
    }


    /**
     * Get the submission timestamp.
     *
     * @return  integer     Submission timestamp
     */
    public function getTimestamp()
    {
        return (int)$this->dt;
    }


    /**
     * Get the submitting user ID.
     *
     * @return  integer     User ID
     */
    public function getUid()
    {
        return (int)$this->uid;
    }


    /**
     * Get the IP address for the submission.
     *
     * @return  string      IP address
     */
    public function getIP()
    {
        return $this->ip;
    }


    /**
     * Get the token for the result.
     *
     * @return  string      Token value
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * Get the result set record ID.
     *
     * @return  integer     Result ID
     */
    public function getID()
    {
        return (int)$this->res_id;
    }


    /**
     * Get the related form ID.
     *
     * @return  string      Form ID
     */
    public function getFormID()
    {
        return $this->frm_id;
    }


    /**
     * Retrieve all the values for this set into the supplied field objects.
     *
     * @param   array   $fields     Array of Field objects
     * @return  array       Array of field name=>value pairs
     */
    public function getValues(array $fields) : array
    {
        global $_TABLES, $_CONF_FRM;

        $retval = array();
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * from {$_TABLES['forms_values']} WHERE results_id = ?",
                array($this->res_id),
                array(Database::INTEGER)
            )->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }

        $vals = array();
        if (is_array($data)) {
            // First get the values into an array indexed by field ID
            foreach ($data as $A) {
                $vals[$A['fld_id']] = $A;
            }
        }

        // Then they can be pushed into the field array
        foreach ($fields as $field) {
            if ($field->isEnabled() && isset($vals[$field->getID()])) {
                // Get the value of the submission and decrypt if required.
                // If decryption fails, just show the value.
                $val = $vals[$field->getID()]['value'];
                if ($field->isEncrypted()) {
                    $value = $field->decrypt($val);
                    if ($value !== false) {
                        $val = $value;
                    }
                }
                $field->setValue($val);
//                if ($field->getID() == 31) {
//                    var_dump($field);die;
                //            }
                $retval[$field->getName()] = $field->getValue();
            }
        }
        return $retval;
    }


    /**
     * Save the field results in a new result set.
     *
     * @param  string  $frm_id     Form ID
     * @param  array   $fields     Array of Field objects
     * @param  array   $vals       Array of values ($_POST)
     * @param  integer $uid        Optional user ID, default=$_USER['uid']
     * @return mixed       False on failure/invalid, result ID on success
     */
    public function SaveData($frm_id, $fields, $vals, $uid = 0)
    {
        global $_USER;

        $this->uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        $this->frm_id = COM_sanitizeID($frm_id);

        // Get the result set ID, creating a new one if needed.
        // Save the isNew value to later check if result should be
        // auto-approved.
        if ($this->isNew()) {
            $res_id = $this->Create($frm_id, $this->uid);
            $isnew = true;
        } else {
            $res_id = $this->res_id;
            $isnew = false;
        }
        if (!$res_id) {     // couldn't create a result set
            return false;
        }

        foreach ($fields as $field) {
            // Get the value to save and have the field save it
            $newval = $field->valueFromForm($vals);
            $field->SaveData($newval, $res_id);
        }

        // Auto-approve the submission if new, and not moderated.
        if ($isnew && !$this->moderate) {
            // Approve now, also notifying other plugins
            $this->Approve(false);
        }

        Cache::clear(array('result_fields', 'result_' . $res_id));
        return $res_id;
    }


    /**
     * Creates a result set in the database.
     *
     * @param   string  $frm_id Form ID
     * @param   integer $uid    Optional user ID, if not the current user
     * @return  integer         New result set ID
     */
    public function Create($frm_id, $uid = 0)
    {
        global $_TABLES, $_USER;

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $this->uid = $uid == 0 ? $_USER['uid'] : (int)$uid;
        $this->dt = time();
        $this->setIP();
        $this->token = md5(time() . rand(1,100));
        $approved = $this->moderate ? 0 : 1;
        try {
            $qb->insert($_TABLES['forms_results'])
               ->setValue('frm_id', ':frm_id')
               ->setValue('instance_id', ':instance_id')
               ->setValue('uid', ':uid')
               ->setValue('dt', ':dt')
               ->setValue('ip', ':ip')
               ->setValue('token', ':token')
               ->setValue('approved', ':approved')
               ->setParameter('frm_id', $this->frm_id, Database::STRING)
               ->setParameter('instance_id', $this->instance_id, Database::STRING)
               ->setParameter('uid', $this->uid, Database::INTEGER)
               ->setParameter('dt', $this->dt, Database::STRING)
               ->setParameter('ip', $this->getIP(), Database::STRING)
               ->setParameter('token', $this->token, Database::STRING)
               ->setParameter('approved', $approved, Database::INTEGER)
               ->execute();
            $this->res_id = $db->conn->lastInsertId();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $this->res_id = 0;
        }
        return $this->res_id;
    }


    /**
     * Set the moderation flag.
     * Called from the Form class.
     *
     * @param   boolean $mod    True to moderate results, False to not
     * @return  object  $this
     */
    public function setModeration($mod = false)
    {
        $this->moderate = $mod ? true : false;
        return $this;
    }


    /**
     * Set the IP address of the submitter.
     *
     * @param   string  $ip     Optional override
     * @return  object  $this
     */
    public function setIP($ip=NULL)
    {
        global $_CONF_FRM;

        if ($ip !== NULL) {
            $this->ip = $ip;
        } elseif ($_CONF_FRM['use_real_ip']) {
            $this->ip = $_SERVER['REAL_ADDR'];
        } else {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }
        return $this;
    }


    /**
     * Approve a submission.
     *
     * @param   boolean $is_moderated   True if result is moderated
     * @return  boolean         True if no DB error
     */
    public function Approve($is_moderated=true)
    {
        global $_TABLES;

        if ($this->res_id < 1) {
            return false;
        }
        $db = Database::getInstance();
        try {
            $db->conn->executeUpdate(
                "UPDATE {$_TABLES['forms_results']}
                SET approved = 1
                WHERE res_id = ?",
                array($this->res_id),
                array(Database::INTEGER)
            );
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }

        // Give plugins a chance to act on the submission
        if (!empty($this->instance_id)) {
            $Form = Form::getInstance($this->frm_id);
            $values = $this->getValues($Form->getFields());
            list($pi_name, $pi_data) = explode('|', $this->instance_id);
            if ($pi_name != 'forms') {  // avoid recursion
                $status = LGLIB_invokeService(
                    $pi_name,
                    'approvesubmission',
                    array(
                        'source' => 'forms',
                        'source_id' => $this->frm_id,
                        'moderated' => $is_moderated,
                        'uid' => $this->getUid(),
                        'pi_info' => $pi_data,
                        'data' => $values,
                    ),
                    $output,
                    $svc_msg
                );
            }
        }
        return true;
    }


    /**
     * Delete a single result set.
     *
     * @param   integer $res_id     Database ID of result to delete
     * @return  boolean     True on success, false on failure
     */
    public static function Delete(int $res_id) : bool
    {
        global $_TABLES;

        $res_id = (int)$res_id;
        if ($res_id == 0) return false;
        self::deleteValues($res_id);
        $db = Database::getInstance();
        try {
            $db->conn->delete($_TABLES['forms_results'], array('res_id' => $res_id));
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * Delete the form values related to a result set.
     *
     * @param   integer $res_id Required result ID
     * @param   integer $uid    Optional user ID
     */
    public static function deleteValues(int $res_id, ?int $uid=NULL) : void
    {
        global $_TABLES;

        $res_id = (int)$res_id;
        if ($res_id == 0) return;
        $uid = (int)$uid;

        $keys = array('results_id' => $res_id);
        if ($uid > 0) {
            $keys['uid'] = $uid;
        }
        $db = Database::getInstance();
        try {
            $db->conn->delete($_TABLES['forms_values'], $keys);
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return;
        }
    }


    /**
     * Delete all results by user ID.
     *
     * @see plugin_user_deleted_forms()
     * @param   integer $uid    User ID
     */
    public static function deleteByUser(int $uid) : void
    {
        global $_TABLES;

        $uid = (int)$uid;
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT res_id FROM {$_TABLES['forms_results']}
                WHERE uid = ?",
                array($uid),
                array(Database::INTEGER)
            )->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }
        if (!empty($data)) {
            foreach ($data as $id=>$res) {
                self::Delete($res['res_id']);
            }
        }
    }


    /**
     * Delete all results related to a form.
     *
     * @param   string  $frm_id     Form ID
     */
    public static function deleteByForm(string $frm_id) : void
    {
        global $_TABLES;

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT res_id FROM {$_TABLES['forms_results']}
                WHERE frm_id = ?",
                array($frm_id),
                array(Database::STRING)
            )->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }
        if (!empty($data)) {
            foreach ($data as $id=>$res) {
                self::Delete($res['res_id']);
            }
        }
    }


    /**
     * Change the user ID.
     *
     * @see plugin_user_move_forms()
     * @param   integer $origUID    Original user ID
     * @param   integer $destUID    New user ID
     */
    public static function changeUID(int $origUID, int $destUID) : void
    {
        global $_TABLES;

        $origUID = (int)$origUID;
        $destUID = (int)$destUID;

        $db = Database::getInstance();
        try {
            $db->conn->executeUpdate(
                "UPDATE {$_TABLES['forms_results']}
                SET uid = ? WHERE uid = ?",
                array($destUID, $origUID),
                array(Database::INTEGER, Database::INTEGER)
            );
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
    }


    /**
     * Create a printable copy of the form results.
     *
     * @param   boolean $admin  TRUE if this is done by an administrator
     * @return  string          HTML page for printable form data.
     */
    public function XPrt($admin = false)
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
        foreach ($this->fields as $Field) {
            $data = $Field->displayValue($this->fields);
            if ($data === NULL) {
                continue;
            }
            //var_dump($F);die;
            $prompt = $F->displayPrompt();
/*
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
 */

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
     * Returns this result set's token.
     * The token provides a very basic authentication mechanism when
     * the after-submission action is to display the results, to ensure
     * that only the newly-submitted result set is displayed.
     *
     * @return  string      Token saved with this result set
     */
    public function Token()
    {
        return $this->token;
    }


    public function isNew()
    {
        return $this->res_id == 0;
    }


    /**
     * Uses lib-admin to list the form results.
     *
     * @param   string  $frm_id         ID of form
     * @param   string  $instance_id    Optional form instance ID
     * @param   boolean $isAdmin        True if this is for an admin
     * @return  string          HTML for the list
     */
    public static function adminList($frm_id, $instance_id='', $isAdmin=true)
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS;

        $retval = '';

        if ($frm_id == '') {
            return $retval;
        }

        $header_arr = array(
            array(
                'text' => $LANG_FORMS['action'],
                'field' => 'action',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['instance'],
                'field' => 'instance_id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_FORMS['submitter'],
                'field' => 'uid',
                'sort' => true,
            ),
            array(
                'text' => $LANG_FORMS['submitted'],
                'field' => 'submitted',
                'sort' => true,
            ),
        );
        if ($isAdmin) {
            $header_arr[] = array(
                'text' => $LANG_FORMS['ip_addr'],
                'field' => 'ip',
                'sort' => true,
            );
            $header_arr[] = array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'align' => 'center',
            );
        }

        $defsort_arr = array(
            'field' => 'submitted',
            'direction' => 'desc',
        );
        $text_arr = array(
            'form_url'  => FRM_ADMIN_URL . '/index.php?results&frm_id=' . $frm_id,
        );
        $sql = "SELECT *, FROM_UNIXTIME(dt) as submitted
            FROM {$_TABLES['forms_results']}
            WHERE frm_id = '" . DB_escapeString($frm_id) . "'";
        if (!empty($instance_id)) {
            $sql .= " AND instance_id = '" . DB_escapeString($instance_id) . "'";
        }
        $query_arr = array(
            'table' => 'forms_results',
            'sql' =>  $sql,
            //'query_fields' => array(''),
            'default_filter' => '',
        );
        $form_arr = array();
        $options_arr = array(
            'chkdelete' => true,
            'chkname' => 'delresmulti',
            'chkfield' => 'res_id',
        );
        $extras = array(
            'isAdmin' => $isAdmin,
        );
        $retval .= '<h1>' . $LANG_FORMS['form_results'] . ': ' . $frm_id;
        if ($instance_id != '') {
            $retval .= ', ' . $LANG_FORMS['instance'] . ': ' . $instance_id;
        }
        $retval .= '</h1>';
        $retval .= ADMIN_list(
            'forms_resultlist',
            array(__CLASS__, 'getListField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr, '', $extras,
            $options_arr, $form_arr
        );
        return $retval;
    }


    /**
     * Determine what to display in the admin list for each results field.
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @return  string              HTML for the field cell
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr, $extras)
    {
        global $_CONF, $_CONF_FRM, $LANG_ADMIN, $LANG_FORMS;

        $retval = '';

        switch($fieldname) {
        case 'action':
            $url = FRM_ADMIN_URL . '/index.php?print=x&frm_id=' . $A['frm_id'] .
                '&res_id=' . $A['res_id'];
            $retval = FieldList::print(array(
                'url' => '#!',
                'attr' => array(
                    'title' => $LANG_FORMS['print'],
                    'class' => 'tooltip',
                    'onclick' => "popupWindow('$url', '', 640, 480, 1); return false;",
                ),
            ) );
            if ($extras['isAdmin']) {
                $retval .= '&nbsp;' . FieldList::edit(array(
                    'url' => FRM_ADMIN_URL . '/index.php?editresult=x&res_id=' . $A['res_id'],
                    'attr' => array(
                        'class' => 'tooltip',
                        'title' => $LANG_ADMIN['edit'],
                    ),
                ) );
            }
            break;

        case 'instance_id':
            $url = FRM_ADMIN_URL . '/index.php?results=x&frm_id=' . $A['frm_id'];
            $retval = '<a href="' . $url . '&instance_id=' . $fieldvalue . '">' . $fieldvalue . '</a>';
            break;

        case 'uid':
            $retval = COM_getDisplayName($fieldvalue);
            break;

        case 'delete':
            $retval = FieldList::delete(array(
                'delete_url' => FRM_ADMIN_URL . '/index.php?delresult=' . $A['res_id'] . '&frm_id=' . $A['frm_id'],
                array(
                    'onclick' => "return confirm('{$LANG_FORMS['confirm_delete']}');",
                ),
            ) );
            break;

        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }

}
