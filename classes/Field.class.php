<?php
/**
 * Class to handle individual form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2020 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms;


/**
 * Base form field class.
 */
class Field
{
    /** Indicate that this is a new record vs one read from the DB.
     * @var boolean */
    protected $isNew = true;

    /** Form record ID.
     * @var string */
    protected $frm_id = '';

    /** Field record ID.
     * @var integer */
    protected $fld_id = 0;

    /** Field order of appearance.
     * @var integer */
    protected $orderby = 9999;

    /** Group with access to fill out.
     * @var integer */
    protected $fill_gid = 13;

    /** Group with access to view results.
     * @var integer */
    protected $results_gid = 2;

    /** Access type (required, normal, read-only).
     * @var integer */
    protected $access = 0;

    /** Enabled flag.
     * @var integer */
    protected $enabled = 1;

    /** Prompt to display.
     * @var string */
    protected $prompt = '';

    /** Field name.
     * @var string */
    protected $fld_name = '';

    /** Field type (checkbox, text, radio, etc.).
     * @var string */
    protected $type = 'text';

    /** Help message.
     * @var string */
    protected $hlp_msg = '';

    /** Field value provided by the submitter.
     * @var string */
    protected $value = '';

    /** Value formatted for display based on options.
     * @var string */
    protected $value_text = '';

    /** Array of options.
     * @var array */
    protected $options = array();  // Form object needs access

    /** Submission type, either `ajax` or `regular`.
     * @var string */
    protected $sub_type = 'regular';

    /** Flag if the current user can view results, regardless of group membership.
     * @var boolean */
    protected $canviewResults = false;

    /** Help popup message.
     * @var string */
    protected $help_msg = '';

    /** Owner permission.
     * @var integer */
    protected $perm_owner = 3;

    /** Group permission.
     * @var integer */
    protected $perm_group = 2;

    /** Site Member permission.
     * @var integer */
    protected $perm_members = 2;

    /** Anonymous User permission.
     * @var integer */
    protected $perm_anon = 2;


    /**
     * Constructor. Sets the local properties using the array $item.
     *
     * @param   integer $id     ID of the existing field, empty if new
     * @param   string  $frm_id ID of related form, if any
     */
    public function __construct($id = 0, $frm_id=NULL)
    {
        global $_USER, $_CONF_FRM;

        if ($id == 0) {
            // Creating a new, empty object
            $this->frm_id = $frm_id;
            $this->fill_gid = $_CONF_FRM['fill_gid'];
            $this->results_gid = $_CONF_FRM['results_gid'];
        } elseif (is_array($id)) {
            // Already have the object data, just set up the variables
            $this->setVars($id, true);
            $this->isNew = false;
        } else {
            // Read an item from the database
            if ($this->Read($id)) {
                $this->isNew = false;
            }
        }
    }


    /**
     * Get an instance of a field based on the field type.
     * If the "fld" parameter is an array it must include at least fld_id and type.
     * Only works to retrieve existing fields.
     *
     * @param   mixed   $fld    Field ID or record
     * @return  object          Field object
     */
    public static function getInstance($fld)
    {
        global $_TABLES;

        if (is_array($fld)) {
            // Received a field record, make sure required parameters
            // are present
            if (!isset($fld['type']) || !isset($fld['fld_id'])) {
                return NULL;
            }
        } elseif (is_numeric($fld)) {
            // Received a field ID, have to look up the record to get the type
            $fld_id = (int)$fld;
            $key = 'field_' . $fld_id;
            $fld = Cache::get($key);
            if ($fld === NULL) {
                $fld = self::_readFromDB($fld_id);
                if (DB_error() || empty($fld)) return NULL;
                Cache::set($key, $fld);
            }
        }

        $cls = __NAMESPACE__ . '\\Fields\\' . ucfirst($fld['type']) . 'Field';
        return new $cls($fld);
    }


    /**
     * Read this field definition from the database and load the object.
     *
     * @see     Field::setVars
     * @uses    Field::_readFromDB()
     * @param   integer $id Field ID
     * @return  boolean     Status from setVars()
     */
    public function Read($id = 0)
    {
        if ($id != 0) $this->fld_id = $id;
        $A = self::_readFromDB($id);
        return $A ? $this->setVars($A, true) : false;
    }


    /**
     * Actually read a field from the database.
     *
     * @param   integer $id     Field ID
     * @return  mixed       Array of fields or False on error
     */
    private static function _readFromDB($id)
    {
        global $_TABLES;

        $sql = "SELECT * FROM {$_TABLES['forms_flddef']}
                WHERE fld_id='" . (int)$id . "'";
        $res = DB_query($sql, 1);
        if (DB_error() || !$res) return false;
        return DB_fetchArray($res, false);
    }


    /**
     * Get all the field objects associated with a form.
     *
     * @param   string  $frm_id     Form ID
     * @return  array       Array of Field objects
     */
    public static function getByForm($frm_id)
    {
        global $_TABLES;

        $retval = array();
        $sql = "SELECT * FROM {$_TABLES['forms_flddef']}
                WHERE frm_id = '" . DB_escapeString($frm_id) . "'
                ORDER BY orderby ASC";
        //echo $sql;die;
        $res2 = DB_query($sql, 1);
        while ($A = DB_fetchArray($res2, false)) {
            $retval[$A['fld_name']] = self::getInstance($A);
        }
        return $retval;
    }



    /**
     * Delete all the field records when a form is deleted.
     *
     * @param   string  $frm_id     Form ID
     */
    public static function deleteByForm($frm_id)
    {
        global $_TABLES;

        DB_delete($_TABLES['forms_flddef'], 'frm_id', $frm_id);
    }


    /**
     * Set all variables for this field.
     * Data is expected to be from $_POST or a database record
     *
     * @param   array   $A      Array of fields for this item
     * @param   boolean $fromdb Indicate whether this is read from the DB
     */
    public function setVars($A, $fromdb=false)
    {
        if (!is_array($A))
            return false;

        $this->fld_id = (int)$A['fld_id'];
        $this->frm_id = $A['frm_id'];
        $this->orderby = empty($A['orderby']) ? 9999 : $A['orderby'];
        $this->enabled = isset($A['enabled']) && $A['enabled'] ? 1 : 0;
        $this->access = (int)$A['access'];
        $this->prompt = $A['prompt'];
        $this->fld_name = $A['fld_name'];
        // Make sure 'type' is set before 'value'
        $this->type = $A['type'];
        $this->help_msg = $A['help_msg'];
        $this->results_gid = (int)$A['results_gid'];
        $this->fill_gid = (int)$A['fill_gid'];

        if (!$fromdb) {
            $this->options = $this->optsFromForm($A);
            $this->value = $this->valueFromForm($A);
        } else {
            $this->options = @unserialize($A['options']);
            if (!$this->options) $this->options = array();
        }
        return true;
    }


    /**
     * Edit a field definition.
     *
     * @uses    DateFormatSelect()
     * @return  string      HTML for editing form
     */
    public function EditDef()
    {
        global $_TABLES, $_CONF, $LANG_FORMS, $LANG_ADMIN, $_CONF_FRM;

        $retval = '';
        $format_str = '';
        $listinput = '';

        // Get defaults from the form, if defined
        if ($this->frm_id > 0) {
            $form = Form::getInstance($this->frm_id);
            if (!$form->isNew) {
                $this->results_gid = $form->results_gid;
                $this->fill_gid = $form->fill_gid;
            }
        }
        $T = new \Template(FRM_PI_PATH . '/templates/admin');
        $T->set_file('editform', 'editfield.thtml');

        // Create the "Field Type" dropdown
        $type_options = '';
        foreach ($LANG_FORMS['fld_types'] as $option => $opt_desc) {
            $sel = $this->type == $option ? 'selected="selected"' : '';
            $type_options .= "<option value=\"$option\" $sel>$opt_desc</option>\n";
        }
        $T->set_var('type_options', $type_options);

        // Create the calculate field type dropdown.
        // We have to do this even for non-calculated fields in case the
        // field type is changed.
        $type_options = '';
        foreach ($LANG_FORMS['calc_types'] as $option => $opt_desc) {
            if (isset($this->options['calc_type'])) {
                $sel = $this->options['calc_type'] == $option ?
                    'selected="selected"' : '';
            }
            $type_options .= "<option value=\"$option\" $sel>$opt_desc</option>\n";
        }
        $T->set_var('calc_fld_options', $type_options);

        // Populate the options specific to certain field types
        //$opts = FRM_getOpts($this->options['values']);

        $value_str = '';
        $curdtformat = 0;
        switch ($this->type) {
        case 'date':
            if ($this->getOption('timeformat') == '24') {
                $T->set_var('24h_sel', 'checked');
            } else {
                $T->set_var('12h_sel', 'checked');
            }

            $T->set_var(
                'shtime_chk',
                $this->getOption('showtime', 0) ? 'checked="checked"' : ''
            );
            $T->set_var(
                'format',
                $this->getOption('format', $_CONF_FRM['def_date_format'])
            );
            $curdtformat = (int)$this->getOption('input_format', 0);
            if ($this->getOption('century', '') != '') {
                $T->set_var('cent_chk', 'checked="checked"');
            }
            break;

        case 'time':
            if ($this->getOption('timeformat', '24') == '24') {
                $T->set_var('24h_sel', 'checked');
            } else {
                $T->set_var('12h_sel', 'checked');
            }
            break;

        case 'checkbox':
            $value_str = '1';
            if ($this->getOption('default', 0) == 1) {
                $T->set_var('defchk_chk', 'checked="checked"');
            }
            //if (!isset($opts['values']) || !is_array($opts['values']) {
            //    $value_str = '1';
            //}
            break;
        case 'select':
        case 'radio':
            $values = FRM_getOpts($this->getOption('values'), array());
            //foreach ($vals as $val=>$valname) {
            //if (is_array($this->options['values'])) {
            if (is_array($values)) {
                $listinput = '';
                $i = 0;
                foreach ($values as $valname) {
                    $listinput .= '<li><input type="text" id="vName' . $i .
                        '" value="' . $valname . '" name="selvalues[]" />';
                    $sel = $this->getOption('default') == $valname ?
                        ' checked="checked"' : '';
                    $listinput .= "<input type=\"radio\" name=\"sel_default\"
                        value=\"$i\" $sel />";
                    $listinput .= '</li>' . "\n";
                    $i++;
                }
            } else {
                $values = array();
                $listinput = '<li><input type="text" id="vName0" value=""
                    name="selvalues[]" /></li>' . "\n";
            }
            break;

        case 'multicheck':
            if (isset($this->options['values'])) {
                $values = FRM_getOpts($this->options['values']);
            } else {
                $values = array();
            }
            if (is_array($values)) {
                $listinput = '';
                $i = 0;
                foreach ($values as $valname) {
                    $listinput .= '<li><input type="text" id="vName' . $i .
                        '" value="' . $valname . '" name="selvalues[]" />';
                    $sel = $valname == $this->getOption('default') ?
                        ' checked="checked"' : '';
                    $listinput .= "<input type=\"radio\" name=\"sel_default\"
                        value=\"$i\" $sel />";
                    $listinput .= '</li>' . "\n";
                    $i++;
                }
            } else {
                $values = array();
                $listinput = '<li><input type="text" id="vName0" value=""
                    name="selvalues[]" /></li>' . "\n";
            }
            break;

        case 'calc':
            $value_str = $this->getOption('value');
            break;

        case 'static':
            $value_str = $this->getOption('default', '');
            break;

        }

        $format_str = $this->getOption('format', $_CONF_FRM['def_calc_format']);
        // Create the selection list for the "Position After" dropdown.
        // Include all options *except* the current one
        $sql = "SELECT orderby, fld_name
                FROM {$_TABLES['forms_flddef']}
                WHERE fld_id <> '{$this->fld_id}'
                AND frm_id = '{$this->frm_id}'
                ORDER BY orderby ASC";
        $res1 = DB_query($sql, 1);
        $orderby_list = '';
        $count = DB_numRows($res1);
        for ($i = 0; $i < $count; $i++) {
            $B = DB_fetchArray($res1, false);
            if (!$B) break;
            $orderby = (int)$B['orderby'] + 1;
            if ($this->isNew && $i == ($count - 1)) {
                $sel =  'selected="selected"';
            } else {
                $sel = '';
            }
            $orderby_list .= "<option value=\"$orderby\" $sel>{$B['fld_name']}</option>\n";
        }

        $autogen_opt = $this->getOption('autogen', 0);
        $T->set_var(array(
            //'admin_url' => FRM_ADMIN_URL,
            'frm_name'  => DB_getItem(
                $_TABLES['forms_frmdef'],
                'frm_name',
                "frm_id='" . DB_escapeString($this->frm_id) . "'"
            ),
//            'frm_id'    => $this->Form->id,
            'frm_id'    => $this->frm_id,
            'fld_id'    => $this->fld_id,
            'fld_name'  => $this->fld_name,
            'type'      => $this->type,
            'valuestr'  => $value_str,
            'defvalue'  => $this->getOption('default', ''),
            'prompt'    => $this->prompt,
            'size'      => $this->getOption('size', 40),
            'cols'      => $this->getOption('cols', 80),
            'rows'      => $this->getOption('rows', 3),
            'maxlength' => $this->getOption('maxlength', 255),
            'ena_chk'   => $this->enabled == 1 ? 'checked="checked"' : '',
            'span_chk'  => $this->getOption('spancols', 0) == 1 ? 'checked="checked"' : '',
            'format'    => $format_str,
            'doc_url'   => FRM_getDocURL('field_def.html'),
            'mask'      => $this->getOption('mask', ''),
            'vismask'   => $this->getOption('vismask', ''),
            'autogen_sel_' . $autogen_opt => ' selected="selected"',
            'stripmask_chk' => $this->getOption('stripmask', 0) == 1 ?
                        'checked="checked"' : '',
            'input_format' => self::DateFormatSelect($curdtformat),
            'orderby'   => $this->orderby,
            'editing'   => $this->isNew ? '' : 'true',
            'orderby_selection' => $orderby_list,
            'list_input' => $listinput,
            'help_msg'  => $this->help_msg,
            'fill_gid_select' => $this->_groupDropdown($this->fill_gid),
            'results_gid_select' => $this->_groupDropdown($this->results_gid),
            'permissions' => SEC_getPermissionsHTML(
                $this->perm_owner, $this->perm_group,
                $this->perm_members, $this->perm_anon
            ),
            'access_chk' . $this->access => 'selected="selected"',
        ) );

        $T->parse('output', 'editform');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Save the field definition to the database.
     *
     * @param   array   $A      Array of elements, e.g. `$_POST`
     * @return  string          Error message, or empty string for success
     */
    public function SaveDef($A = '')
    {
        global $_TABLES, $_CONF_FRM;

        $fld_id = isset($A['fld_id']) ? (int)$A['fld_id'] : 0;
        $frm_id = isset($A['frm_id']) ? COM_sanitizeID($A['frm_id']) : '';
        if ($frm_id == '') {
            return 'Invalid form ID';
        }

        // Sanitize the name, especially make sure there are no spaces
        $A['fld_name'] = COM_sanitizeID($A['fld_name'], false);
        if (empty($A['fld_name']) || empty($A['type'])) {
            return;
        }

        $this->setVars($A, false);
        $this->fill_gid = (int)$A['fill_gid'];
        $this->results_gid = (int)$A['results_gid'];

        if ($fld_id > 0) {
            // Existing record, perform update
            $sql1 = "UPDATE {$_TABLES['forms_flddef']} SET ";
            $sql3 = " WHERE fld_id = $fld_id";
        } else {
            $sql1 = "INSERT INTO {$_TABLES['forms_flddef']} SET ";
            $sql3 = '';
        }

        $sql2 = "frm_id = '" . DB_escapeString($this->frm_id) . "',
                fld_name = '" . DB_escapeString($this->fld_name) . "',
                type = '" . DB_escapeString($this->type) . "',
                enabled = '{$this->enabled}',
                access = '{$this->access}',
                prompt = '" . DB_escapeString($this->prompt) . "',
                options = '" . DB_escapeString(@serialize($this->options)) . "',
                orderby = '{$this->orderby}',
                help_msg = '" . DB_escapeString($this->help_msg) . "',
                fill_gid = '{$this->fill_gid}',
                results_gid = '{$this->results_gid}'";
        $sql = $sql1 . $sql2 . $sql3;
        DB_query($sql);

        if (!DB_error()) {
            // After saving, reorder the fields
            $this->Reorder($A['frm_id']);
            $msg = '';
        } else {
            $msg = 5;
        }
        return $msg;
    }


    /**
     * Delete the current field definition.
     *
     * @param   integer $fld_id     ID number of the field
     */
    public static function Delete($fld_id=0)
    {
        global $_TABLES;

        DB_delete($_TABLES['forms_values'], 'fld_id', $fld_id);
        DB_delete($_TABLES['forms_flddef'], 'fld_id', $fld_id);
    }


    /**
     * Save this field to the database.
     *
     * @uses    AutoGen()
     * @param   mixed   $newval Data value to save
     * @param   integer $res_id Result ID associated with this field
     * @return  boolean     True on success, False on failure
     */
    public function SaveData($newval, $res_id=0)
    {
        global $_TABLES;

        $res_id = (int)$res_id;
        if ($res_id == 0) {
            return false;
        }

        if ($this->getOption('autogen') == FRM_AUTOGEN_SAVE) {
            $newval = $this->AutoGen('save');
        }

        // Put the new value back into the array after sanitizing
        $this->value = $newval;
        $db_value = $this->prepareForDB($newval);

        //$this->fld_name = $name;
        $sql = "INSERT INTO {$_TABLES['forms_values']}
                    (results_id, fld_id, value)
                VALUES (
                    '$res_id',
                    '{$this->fld_id}',
                    '$db_value'
                )
                ON DUPLICATE KEY
                    UPDATE value = '$db_value'";
        //COM_errorLog($sql);
        DB_query($sql, 1);
        $status = DB_error();
        return $status ? false : true;
    }


    /**
     * Provide a dropdown selection of date formats
     *
     * @param   integer $cur    Option to be selected by default
     * @return  string          HTML for selection, without select tags
     */
    public function DateFormatSelect($cur=0)
    {
        global $LANG_FORMS;

        $retval = '';
        $_formats = array(
            1 => $LANG_FORMS['month'].' '.$LANG_FORMS['day'].' '.$LANG_FORMS['year'],
            2 => $LANG_FORMS['day'].' '.$LANG_FORMS['month'].' '.$LANG_FORMS['year'],
        );
        foreach ($_formats as $key => $string) {
            $sel = $cur == $key ? 'selected="selected"' : '';
            $retval .= "<option value=\"$key\" $sel>$string</option>\n";
        }
        return $retval;
    }


    /**
     * Validate the submitted field value(s).
     *
     * @param   array   $vals  All form values
     * @return  string      Empty string for success, or error message
     */
    public function Validate(&$vals)
    {
        global $LANG_FORMS;

        $msg = '';
        if (!$this->enabled) return $msg;   // not enabled
        if (($this->access & FRM_FIELD_REQUIRED) != FRM_FIELD_REQUIRED)
            return $msg;        // not required

        switch ($this->type) {
        case 'date':
            if (
                empty($vals[$this->fld_name . '_month']) ||
                empty($vals[$this->fld_name . '_day']) ||
                empty($vals[$this->fld_name . '_year'])
            ) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        case 'time':
            if (empty($vals[$this->fld_name . '_hour']) ||
                empty($vals[$this->fld_name . '_minute'])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        case 'radio':
            if (empty($vals[$this->fld_name])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        default:
            if (empty($vals[$this->fld_name])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        }
        return $msg;
    }


    /**
     * Copy this field to another form.
     *
     * @see Form::Duplicate()
     */
    public function Duplicate()
    {
        global $_TABLES;

        if (is_array($this->options)) {
            $options = serialize($this->options);
        } else {
            $options = $this->options;
        }

        $sql = "INSERT INTO {$_TABLES['forms_flddef']} SET
                frm_id = '" . DB_escapeString($this->frm_id) . "',
                name = '" . DB_escapeString($this->fld_name) . "',
                type = '" . DB_escapeString($this->type) . "',
                enabled = {$this->enabled},
                access = {$this->access},
                prompt = '" . DB_escapeString($this->prompt) . "',
                options = '" . DB_escapeString($options) . "',
                help_msg = '" . DB_escapeString($this->help_msg) . "',
                fill_gid = {$this->fill_gid},
                results_gid = {$this->results_gid},
                orderby = '" . (int)$this->orderby . "'";
        DB_query($sql, 1);
        $msg = DB_error() ? 5 : '';
        return $msg;
    }


    /**
     * Move a form field up or down in the form.
     *
     * @param   string  $frm_id     ID of form record
     * @param   integer $fld_id     ID of field record
     * @param   string  $where      Direction to move ('up' or 'down')
     */
    public static function Move($frm_id, $fld_id, $where)
    {
        global $_CONF, $_TABLES, $LANG21;

        $retval = '';
        $frm_id = COM_sanitizeID($frm_id);
        $fld_id = (int)$fld_id;

        switch ($where) {
        case 'up':
            $sign = '-';
            break;

        case 'down':
            $sign = '+';
            break;

        default:
            return '';
            break;
        }
        $sql = "UPDATE {$_TABLES['forms_flddef']}
                SET orderby = orderby $sign 11
                WHERE frm_id = '$frm_id'
                AND fld_id = '$fld_id'";
        //echo $sql;die;
        DB_query($sql, 1);

        if (!DB_error()) {
            // Reorder fields to get them separated by 10
            self::Reorder($frm_id);
            $msg = '';
        } else {
            $msg = 5;
        }
        return $msg;
    }


    /**
     * Reorder the fields appearance on the form.
     *
     * @param   string  $frm_id     ID of form being reordered
     */
    public static function Reorder($frm_id)
    {
        global $_TABLES;

        // Sanitize & validate form ID
        $frm_id = COM_sanitizeID($frm_id);
        if ($frm_id == '') return;

        $sql = "SELECT fld_id, orderby FROM {$_TABLES['forms_flddef']}
                WHERE frm_id='$frm_id'
                ORDER BY orderby ASC";
        $result = DB_query($sql);

        $order = 10;
        $stepNumber = 10;

        while ($A = DB_fetchArray($result, false)) {

            if ($A['orderby'] != $order) {  // only update incorrect ones
                $sql = "UPDATE {$_TABLES['forms_flddef']}
                    SET orderby = '$order'
                    WHERE frm_id = '$frm_id'
                    AND fld_id = '{$A['fld_id']}'";
                DB_query($sql, 1);
                if (DB_error()) {
                    return 5;
                }
            }
            $order += $stepNumber;
        }
        return '';
    }


    /**
     * Get the default value for a field.
     * Normally this will be the configured default retuned verbatim.
     * It could also be a value from the $_USER array (more maybe to follow).
     *
     * @uses    AutoGen()
     * @param   string  $def    Defined default value
     * @return  string          Actual text to use as the field value.
     */
    public function GetDefault($def = '')
    {
        global $_USER;

        if (
            empty($def) &&
            $this->getOption('autogen') == FRM_AUTOGEN_FILL
        ) {
            return $this->AutoGen('fill');
        }

        $value = $def;      // by default just return the given value
        if (isset($def[0]) && $def[0] == '$') {
            // Look for something like "$_USER:fullname"
            $A = explode(':', $def);
            $var = $A[0];
            $valname = isset($A[1]) ? $A[1] : false;
            switch (strtoupper($var)) {
            case '$_USER':
                if ($valname && isset($_USER[$valname]))
                    $value = $_USER[$valname];
                else
                    $value = '';    // Empty if not available
                break;
            case '$NOW':
                if ($this->type == 'time') {
                    $value = date('H:i:s');
                } else {
                    $value = date('Y-m-d H:i:s');
                }
                break;
            }
        }
        return $value;
    }


    /**
     * Create the time field.
     * This is in this class so it can be used by both date and time fields.
     *
     * @uses    hour24to12()
     * @param   string  $timestr    Optional HH:MM string.  Seconds ignored.
     * @return  string  HTML for time selection field
     */
    protected function TimeField($timestr = '')
    {
        $ampm_fld = '';
        $hour = '';
        $minute = '';

        // Check for POSTed values first, coming from a previous form
        // If one is set, all should be set, and empty values are ok
        if (isset($_POST[$this->fld_name . '_hour']) &&
            isset($_POST[$this->fld_name . '_minute'])) {
            $hour = (int)$_POST[$this->fld_name . '_hour'];
            $minute = (int)$_POST[$this->fld_name . '_minute'];
        }
        if (empty($hour) || empty($minute)) {
            if (!empty($timestr)) {
                // Default to the specified time string
                list($hour, $minute)  = explode(':', $timestr);
            } elseif (!empty($this->getOption('default'))) {
                if (strtolower($this->options['default']) == '$now') {
                    // Handle the special "now" default"
                    list($hour, $minute) = explode(':', date('H:i:s'));
                } else {
                    // Expecting a 24-hour time as "HH:MM"
                    list($hour, $minute) = explode(':', $this->options['default']);
                }
            }
        }

        // Nothing selected by default, or invalid values
        if (empty($hour) || empty($minute) ||
            !is_numeric($hour) || !is_numeric($minute) ||
            $hour < 0 || $hour > 23 ||
            $minute < 0 || $minute > 59) {
            list($hour, $minute) = array(0, 0);
        }

        if ($this->getOption('timeformat') == '12') {
            list($hour, $ampm_sel) = $this->hour24to12($hour);
            $ampm_fld = COM_getAmPmFormSelection($this->fld_name . '_ampm', $ampm_sel);
        }

        $h_fld = '<select name="' . $this->fld_name . '_hour">' . LB .
            COM_getHourFormOptions($hour, $this->getOption('timeformat')) .
            '</select>' . LB;
        $m_fld = '<select name="' . $this->fld_name . '_minute">' . LB .
            COM_getMinuteFormOptions($minute) .
            '</select>' . LB;
        return $h_fld . ' ' . $m_fld . $ampm_fld;
    }


    /**
     * Convert an hour from 24-hour to 12-hour format for display.
     *
     * @param   integer $hour   Hour to convert
     * @return  array       array(new_hour, ampm_indicator)
     */
    public function hour24to12($hour)
    {
        if ($hour >= 12) {
            $ampm = 'pm';
            if ($hour > 12) $hour -= 12;
        } else {
            $ampm = 'am';
            if ($hour == 0) $hour = 12;
        }
        return array($hour, $ampm);
    }


    /**
     * Auto-generate a field value.
     * Calls the first available function from the following list:
     *   1. CUSTOM_forms_autogen_$type_$varname()
     *   2. CUSTOM_forms_autogen_$varname()
     *   3. CUSTOM_forms_autogen()
     *   4. COM_makeSid()
     *
     * @param   string  $type   `fill` or `save` to indicate which function
     * @param   integer $uid    User ID, passwd through to autogen function
     * @return mixed       Generated field value
     */
    private function AutoGen($type, $uid = 0)
    {
        global $_USER;

        if ($type != 'fill') $type = 'save';
        if ($uid == 0) $uid = (int)$_USER['uid'];
        $var = $this->getName();

        $function = 'CUSTOM_forms_autogen';
        if (function_exists($function . '_' . $type . '_' . $var)) {
            $retval = $function . '_' . $type . '_' . $var($A, $uid);
        } elseif (function_exists($function . '_' . $var)) {
            $retval =  $function . '_' . $var($A, $uid);
        } elseif (function_exists($function)) {
            $retval = $function($A, $uid);
        } else {
            $retval = COM_makeSID();
        }
        return $retval;
    }


    /**
     * Toggle a boolean field in the database.
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
        $sql = "UPDATE {$_TABLES['forms_flddef']}
                SET $fld = $newval
                WHERE fld_id = '$id'";
        $res = DB_query($sql, 1);
        if (DB_error($res)) {
            COM_errorLog(__CLASS__ . '\\' . __FUNCTION__ . ':: ' . $sql);
            return $oldval;
        } else {
            return $newval;
        }
    }


    /**
     * Get the HTML element ID based on the form and field ID.
     * This is for ajax fields that store values in session variables
     * instead of result sets.
     * Also uses the field value if available and needed, such as for
     * multi-checkboxes.
     *
     * @param   string  $val    Optional field value
     * @return  string          ID string for the field element
     */
    public function _elemID($val = '')
    {
        $name  = str_replace(' ', '', $this->fld_name);
        $id = 'forms_' . $this->frm_id . '_' . $name;
        if (!empty($val)) {
            $id .= '_' . str_replace(' ', '', $val);
        }
        return $id;
    }


    /**
     * Get the session ID to use for saving values via AJAX.
     * Called internally using the current object values
     *
     * @uses    self::sessID()
     * @return  string      String like "forms.formid.fieldid"
     */
    protected function _sessID()
    {
        return self::sessID($this->frm_id, $this->fld_id);
    }


    /**
     * Get the session ID to use for saving values via AJAX.
     *
     * @param   string  $frm_id     Form ID
     * @param   integer $fld_id     Field ID
     * @return  string      String like `forms.formid.fieldid`
     */
    public static function sessID($frm_id, $fld_id)
    {
        return 'forms.' . $frm_id . '.' . $fld_id;
    }


    /**
     * Default function to get the field value from the form.
     * Just returns the form value.
     *
     * @param   array   $A      Array of form values, e.g. $_POST
     * @return  mixed           Field value
     */
    public function valueFromForm($A)
    {
        return isset($A[$this->fld_name]) ? $A[$this->fld_name] : '';
    }


    /**
     * Get the value to show in the CSV export of results.
     * This allows a field like a checkbox to report the actual numeric value.
     * The default behavior is to call displayValue().
     *
     * @return  mixed       Value to show in CSV export.
     */
    public function getValueForCSV($fields)
    {
        return $this->displayValue($fields);
    }


    /**
     * Default function to get the display value for a field.
     * Just returns the raw value.
     *
     * @param   array   $fields     Array of all field objects (for calc-type)
     * @return  string      Display value
     */
    public function displayValue($fields)
    {
        if ($this->canViewResults()) {
            return htmlspecialchars($this->value);
        } else {
            return NULL;
        }
    }


    /**
     * Default function to get the field prompt.
     * Gets the user-defined prompt, if any, or falls back to the field name.
     *
     * @return  string  Field prompt
     */
    public function displayPrompt()
    {
        return $this->prompt == '' ? $this->fld_name : $this->prompt;
    }


    /**
     * Get the field record ID.
     *
     * @return  integer     Field record ID
     */
    public function getID()
    {
        return (int)$this->fld_id;
    }


    /**
     * Set the form ID for this field.
     *
     * @param   string  $frm_id     Form ID
     * @return  object  $this
     */
    public function setFormID($frm_id)
    {
        $this->frm_id = $frm_id;
        return $this;
    }


    /**
     * Get the field short name.
     *
     * @return  string      Field name
     */
    public function getName()
    {
        return $this->fld_name;
    }


    /**
     * Set the name of this field.
     *
     * @param   string  $name   Field name
     * @return  object  $this
     */
    public function setName($name)
    {
        $this->fld_name = $name;
        return $this;
    }


    /**
     * Get the name of the field.
     *
     * @return  string      Field name
     */
    public function getPrompt()
    {
        return $this->prompt;
    }


    /**
     * Get the field value.
     *
     * @return   mixed      Value submitted
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * Get the help message for this field.
     *
     * @return  string      Help message
     */
    public function getHelpMsg()
    {
        return $this->help_msg;
    }


    /**
     * Get the access setting for this field (normal, required, read-only)
     *
     * @return  integer     Access level flag
     */
    public function getAccess()
    {
        return (int)$this->access;
    }


    /**
     * Check that the user has a given level of access to the field.
     *
     * @param   integer $req    Access required
     * @return  boolean     True if access is allowed, False if not
     */
    public function checkAccess($req)
    {
        return $this->access & $req == $req;
    }


    /**
     * Check if this field is enabled.
     *
     * @return  boolean     True if enabled, False if disabled
     */
    public function isEnabled()
    {
        return $this->enabled ? 1 : 0;
    }


    /**
     * Get the type of field.
     *
     * @return  string      Field type
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Get a sanitized value to set in the properties.
     * Default function just trims the input.
     *
     * @param   string  $value  Value to set
     * @return  object  $his
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            if (isset($value[$this->getName()])) {
                $this->value = trim($value[$this->getName()]);
            }
        } else {
            $this->value = trim($value);
        }
        return $this;
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
     * Get the submission type of the parent form.
     *
     * @return string  Submission type ("ajax" or "regular")
     */
    protected function getSubType()
    {
        static $sub_type = NULL;
        if ($sub_type === NULL) {
            $Form = Form::getInstance($this->frm_id);
            if (!$Form) {
                $sub_type = 'regular';
            } else {
                $sub_type = $Form->getSubType();
            }
        }
        return $sub_type;
    }


    /**
     * Get the value for a specified option, if it is set.
     *
     * @param   string  $opt        Option name
     * @param   mixed   $default    Default value if option not set
     * @return  mixed       Option value, NULL if not set
     */
    protected function getOption($opt, $default=NULL)
    {
        if (isset($this->options[$opt])) {
            return $this->options[$opt];
        } else {
            return $default;
        }
    }


    /**
     * Get the value to be rendered in the form.
     *
     * @param   integer $res_id     Result set ID
     * @param   string  $mode       View mode, e.g. "preview"
     * @return  mixed               Field value used to populate form
     */
    protected function renderValue($res_id, $mode)
    {
        $value = '';
        if (isset($_POST[$this->fld_name])) {
            // First, check for a POSTed value. The form is being redisplayed.
            $value = $_POST[$this->fld_name];
        } elseif ($this->getSubType() == 'ajax') {
            $sess_id = $this->_sessID();
            if (SESS_isSet($sess_id)) {
                // Second, if this is an AJAX form check the session variable.
                $value = SESS_getVar($sess_id);
            }
        } elseif ($res_id == 0 || $mode == 'preview') {
            // Finally, use the default value if defined.
            if (isset($this->options['default'])) {
                $value = $this->GetDefault($this->options['default']);
            }
        } else {
            $value = $this->value;
        }
        return $value;
    }


    /**
     * Helper function to get the access string for fields.
     *
     * @return  string  Access-control string, e.g. "required" or "disabled"
     */
    protected function renderAccess()
    {
        switch ($this->access) {
        case FRM_FIELD_READONLY:
            return 'disabled = "disabled"';
            break;
        case FRM_FIELD_REQUIRED:
            return 'required';
            break;
        default:
            return '';
            break;
        }
    }


    /**
     * Get the Javascript string for AJAX fields.
     *
     * @param   string  $mode   View mode, e.g. "preview"
     * @return  string          Javascript to save the data
     */
    protected function renderJS($mode)
    {
        global $LANG_FORMS;

        if ($this->getSubType() == 'ajax') {
            // Only ajax fields get this
            if ($mode == 'preview') {
                $js = 'onchange="FORMS_ajaxDummySave(\'' . $LANG_FORMS['save_disabled'] . '\')"';
            } else {
                $js = "onchange=\"FORMS_ajaxSave('" . $this->frm_id . "','" . $this->fld_id .
                    "',this);\"";
            }
        } else {
            $js = '';
        }
        return $js;
    }


    /**
     * Get the data formatted for saving to the database.
     * Field types can override this as needed.
     *
     * @param   mixed   $newval     New data to save
     * @return  mixed       Data formatted for the DB
     */
    protected function prepareForDB($newval)
    {
        return DB_escapeString(COM_checkWords(strip_tags($newval)));
    }


    /**
     * Get the default option values from a field definition form.
     * Should be called by child objects in their own optsFromForm function.
     *
     * @param   array   $A      Array of all form fields
     * @return  array           Field options
     */
    protected function optsFromForm($A)
    {
        $options = array(
            'default' => trim($A['defvalue']),
        );
        // only if they are actually defined.
        if (isset($A['mask']) && $A['mask'] != '') {
            $options['mask'] = trim($A['mask']);
        }
        if (isset($A['vismask']) && $A['vismask'] != '') {
            $options['vismask'] = trim($A['vismask']);
        }
        if (isset($A['spancols']) && $A['spancols'] == 1) {
            $options['spancols'] = 1;
        }
        if (isset($A['valuetext']) && $A['valuetext'] != '') {
            $options['default'] = $A['valuetext'];
        }
        return $options;
    }


    /**
     * Check if the current user can render this form field.
     * Checks that the user is a member of fill_gid and the field is enabled.
     * Caches the status for each fill_gid since this gets called for
     * each field, and fill_gid is likely to be the same for all.
     *
     * @return  boolean     True if the field can be rendered, False if not.
     */
    protected function canViewField()
    {
        global $_GROUPS;

        if (!$this->enabled || !in_array($this->fill_gid, $_GROUPS)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Check if the current user can view the results for this field.
     * Checks that the user is a member of results_gid and the field is enabled.
     * Caches the status for each results_gid since this gets called for
     * each field, and results_gid is likely to be the same for all.
     *
     * @return  boolean     True if the user can view, False if not.
     */
    public function canViewResults()
    {
        global $_GROUPS, $_USERS;
        static $gids = array();

        if ($this->canviewResults) return true;

        if (!array_key_exists($this->results_gid, $gids)) {
            if ($this->enabled == 0 || !in_array($this->results_gid, $_GROUPS)) {
                $gids[$this->results_gid] = false;
            } else {
                $gids[$this->results_gid] = true;
            }
        }
        return $gids[$this->results_gid];
    }


    /**
     * Return the XML element for privacy export.
     *
     * @return  string  XML element string: <fld_name>data</fld_name>
     */
    public function XML()
    {
        $retval = '';
        $Form = Form::getInstance($this->frm_id);
        $d = addSlashes(htmlentities(trim($this->displayValue($Form->getFields()))));
        // Replace spaces in prompts with underscores, then remove all other
        // non-alphanumerics
        $p = str_replace(' ', '_', $this->prompt);
        $p = preg_replace("/[^A-Za-z0-9_]/", '', $p);

        if (!empty($d)) {
            $retval .= "<$p>$d</$p>\n";
        }
        return $retval;
    }


    /**
     * Helper function to check if an option is set for this field.
     *
     * @param   string  $opt    Name of option
     * @param   mixed   $val    Required value, usually "1"
     * @return  boolean     True if it is set, False if not.
     */
    public function hasOption($opt, $val = 1)
    {
        if (
            isset($this->options[$opt]) && 
            $this->options[$opt] == $val
        ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Set the canviewResults flag to force allow viewing.
     * Used for a user to be able to view their own submissions regardless
     * of group membership.
     *
     * @param   boolean $canview    True to allow viewing, False to use default perms.
     */
    public function setCanviewResults($canview=false)
    {
        $this->canviewResults = $canview ? true : false;
    }


    /**
     * Uses lib-admin to list the field definitions and allow updating.
     *
     * @param   string  $frm_id     Form ID
     * @return  string              HTML for the list
     */
    public static function adminList($frm_id = '')
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_FORMS, $_CONF_FRM;

        $header_arr = array(
            array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['name'],
                'field' => 'fld_name',
                'sort' => false,
            ),
            array(
                'text' => $LANG_FORMS['move'],
                'field' => 'move',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['type'],
                'field' => 'type',
                'sort' => false,
            ),
            array(
                'text' => $LANG_FORMS['enabled'],
                'field' => 'enabled',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_FORMS['fld_access'],
                'field' => 'access',
                'sort' => false,
            ),
            array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
            ),
        );
        $defsort_arr = array('field' => 'orderby', 'direction' => 'asc');
        $text_arr = array('form_url' => FRM_ADMIN_URL . '/index.php');
        $options_arr = array(
            'chkdelete' => true,
            'chkname' => 'delfield',
            'chkfield' => 'fld_id',
        );
        $query_arr = array(
            'table' => 'forms_flddef',
            'sql' => "SELECT * FROM {$_TABLES['forms_flddef']}",
            'query_fields' => array('name', 'type', 'value'),
            'default_filter' => '',
        );
        if ($frm_id != '') {
            $query_arr['sql'] .= " WHERE frm_id='" . DB_escapeString($frm_id) . "'";
        }
        $form_arr = array();
        $T = new \Template(FRM_PI_PATH . '/templates/admin');
        $T->set_file('formfields', 'formfields.thtml');
        $T->set_var(array(
            'action_url'    => FRM_ADMIN_URL . '/index.php',
            'frm_id'        => $frm_id,
            'pi_url'        => FRM_PI_URL,
            'field_adminlist' => ADMIN_list(
                'forms_fieldlist',
                array(__CLASS__, 'getListField'),
                $header_arr,
                $text_arr, $query_arr, $defsort_arr, '', '',
                $options_arr, $form_arr
            ),
        ) );
        $T->parse('output', 'formfields');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Determine what to display in the admin list for each field.
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @return  string              HTML for the field cell
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_CONF_FRM, $LANG_ACCESS, $LANG_FORMS;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval = COM_createLink(
                Icon::getHTML('edit'),
                FRM_ADMIN_URL . "/index.php?editfield=x&amp;fld_id={$A['fld_id']}"
            );
            break;

        case 'delete':
            $retval = COM_createLink(
                Icon::getHTML('delete'),
                FRM_ADMIN_URL . '/index.php?deleteFldDef=x&fld_id=' .
                    $A['fld_id'] . '&frm_id=' . $A['frm_id'],
                array(
                    'onclick' => "return confirm('{$LANG_FORMS['confirm_delete']}');",
                )
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
            $retval = "<input name=\"{$fieldname}_{$A['fld_id']}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='FRMtoggleEnabled(this, \"{$A['fld_id']}\", \"field\", \"{$fieldname}\", \"" . FRM_ADMIN_URL . "\");' ".
                "/>\n";
            break;

        case 'fld_id':
            return '';
            break;

        case 'move':
            $retval = COM_createLink(
                Icon::getHTML('arrow-up'),
                FRM_ADMIN_URL . "/index.php?frm_id={$A['frm_id']}&reorder=x&where=up&fld_id={$A['fld_id']}"
            ) . '&nbsp;';
            $retval .= COM_createLink(
                Icon::getHTML('arrow-down'),
                FRM_ADMIN_URL . "/index.php?frm_id={$A['frm_id']}&reorder=x&where=down&fld_id={$A['fld_id']}"
            );
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
     * @param   string      Dropdown list or hidden field
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

}

?>
