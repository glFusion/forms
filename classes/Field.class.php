<?php
/**
*   Class to handle individual form fields.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;

/**
*   Class for form fields
*/
class Field
{
    public $isNew;
    public $options = array();  // Form object needs access
    protected $properties = array();
    protected $sub_type = 'regular';

    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  integer $id     ID of the existing field, empty if new
    *   @param  object  $Form   Form object to which this field belongs
    */
    public function __construct($id = 0, $frm_id=NULL)
    {
        global $_USER, $_CONF_FRM;

        $this->isNew = true;
        if ($id == 0) {
            // Creating a new, empty object
            $this->fld_id = 0;
            $this->name = '';
            $this->type = 'text';
            $this->enabled = 1;
            $this->access = 0;
            $this->prompt = '';
            $this->frm_id = $frm_id;
            $this->fill_gid = $_CONF_FRM['fill_gid'];
            $this->results_gid = $_CONF_FRM['results_gid'];
        } elseif (is_array($id)) {
            // Already have the object data, just set up the variables
            $this->SetVars($id, true);
            $this->isNew = false;
        } else {
            // Read an item from the database
            if ($this->Read($id)) {
                $this->isNew = false;
            }
        }
    }


    /**
    *   Get an instance of a field based on the field type.
    *   If the "fld" parameter is an array it must include at least fld_id
    *   and type.
    *   Only works to retrieve existing fields.
    *
    *   @param  mixed   $fld    Field ID or record
    *   @return object          Field object
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
                $fld = self::_readFromDB($fld);
                if (DB_error() || empty($fld)) return NULL;
                Cache::set($key, $fld);
            }
        }

        $cls = __NAMESPACE__ . '\\Fields\\' . $fld['type'];
        return new $cls($fld);
    }


    /**
    *   Read this field definition from the database and load the object
    *
    *   @see Field::SetVars
    *   @uses Field::_readFromDB()
    *   @param  string  $name   Optional field name
    *   @return boolean     Status from SetVars()
    */
    public function Read($id = 0)
    {
        if ($id != 0) $this->fld_id = $id;
        $A = self::_readFromDB($id);
        return $A ? $this->setVars($A, true) : false;
    }


    /**
    *   Actually read a field from the database
    *
    *   @param  integer $id     Field ID
    *   @return mixed       Array of fields or False on error
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
    *   Set a value into a property
    *
    *   @uses   hour24to12()
    *   @param  string  $name       Name of property
    *   @param  mixed   $value      Value to set
    */
    public function __set($name, $value)
    {
        global $LANG_FORMS;
        switch ($name) {
        case 'frm_id':
            $this->properties[$name] = COM_sanitizeID($value);
            break;

        case 'fld_id':
        case 'orderby':
        case 'fill_gid':
        case 'results_gid';
        case 'access':
            $this->properties[$name] = (int)$value;
            break;

        case 'enabled':
            $this->properties[$name] = $value == 0 ? 0 : 1;
            break;

        case 'calcvalues':
            if (is_array($value))
                $this->properties[$name] = $value;
            else
                $this->properties[$name] = array();
            break;

        case 'prompt':
        case 'name':
        case 'type':
        case 'help_msg':
            $this->properties[$name] = trim($value);
            break;

        case 'value':
            $this->properties['value'] = $this->setValue($value);
            break;

        }
    }


    /**
    *   Get a property's value
    *
    *   @param  string  $name       Name of property
    *   @return mixed       Value of property, or empty string if undefined
    */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
           return $this->properties[$name];
        } else {
            return '';
        }
    }


    /**
    *   Set all variables for this field.
    *   Data is expected to be from $_POST or a database record
    *
    *   @param  array   $item   Array of fields for this item
    *   @param  boolean $fromdb Indicate whether this is read from the DB
    */
    public function SetVars($A, $fromdb=false)
    {
        if (!is_array($A))
            return false;

        $this->fld_id = $A['fld_id'];
        $this->frm_id = $A['frm_id'];
        $this->orderby = empty($A['orderby']) ? 255 : $A['orderby'];
        $this->enabled = isset($A['enabled']) ? $A['enabled'] : 0;
        $this->access = $A['access'];
        $this->prompt = $A['prompt'];
        $this->name = $A['name'];
        // Make sure 'type' is set before 'value'
        $this->type = $A['type'];
        $this->help_msg = $A['help_msg'];
        $this->results_gid = $A['results_gid'];
        $this->fill_gid = $A['fill_gid'];

        if (!$fromdb) {
            $this->options = $this->optsFromForm($_POST);
            $this->value = $this->valueFromForm($_POST);
        } else {
            $this->options = @unserialize($A['options']);
            if (!$this->options) $this->options = array();
        }
        return true;
    }


    /**
    *   Edit a field definition.
    *
    *   @uses   DateFormatSelect()
    *   @return string      HTML for editing form
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
        $T = FRM_getTemplate('editfield', 'editform', '/admin');

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
            if ($this->options['timeformat'] == '24') {
                $T->set_var('24h_sel', 'checked');
            } else {
                $T->set_var('12h_sel', 'checked');
            }

            $T->set_var('shtime_chk', $this->options['showtime'] == 1 ?
                'checked="checked"' : '');
            $T->set_var('format', isset($this->options['format']) ?
                    $this->options['format'] : $_CONF_FRM['def_date_format']);
            $curdtformat = (isset($this->options['input_format'])) ?
                    (int)$this->options['input_format'] : 0;
            $T->set_var('cent_chk', $this->options['century'] == 1 ?
                'checked="checked"' : '');
            break;

        case 'time':
            if ($this->options['timeformat'] == '24') {
                $T->set_var('24h_sel', 'checked');
            } else {
                $T->set_var('12h_sel', 'checked');
            }
            break;

        case 'checkbox':
            $value_str = '1';
            if (isset($this->options['default']) && $this->options['default'] == 1) {
                $T->set_var('defchk_chk', 'checked="checked"');
            }
            //if (!isset($opts['values']) || !is_array($opts['values']) {
            //    $value_str = '1';
            //}
            break;
        case 'select':
        case 'radio':
            $values = FRM_getOpts($this->options['values']);
            //foreach ($vals as $val=>$valname) {
            //if (is_array($this->options['values'])) {
            if (is_array($values)) {
                $listinput = '';
                $i = 0;
                foreach ($values as $valname) {
                    $listinput .= '<li><input type="text" id="vName' . $i .
                        '" value="' . $valname . '" name="selvalues[]" />';
                    $sel = $this->options['default'] == $valname ?
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
                    $sel = $valname == $this->options['default'] ?
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
            $value_str = $this->options['value'];
            //$format_str = empty($this->options['format']) ?
            //        $_CONF_FRM['def_calc_format'] : $this->options['format'];
            break;

        case 'static':
            $value_str = $this->options['default'];
            break;

        }

        $format_str = empty($this->options['format']) ?
                    $_CONF_FRM['def_calc_format'] : $this->options['format'];
        // Create the selection list for the "Position After" dropdown.
        // Include all options *except* the current one
        $sql = "SELECT orderby, name
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
            $orderby_list .= "<option value=\"$orderby\" $sel>{$B['name']}</option>\n";
        }

        $autogen_opt = isset($this->options['autogen']) ?
                    (int)$this->options['autogen'] : 0;
        $T->set_var(array(
            //'admin_url' => FRM_ADMIN_URL,
            'frm_name'  => DB_getItem($_TABLES['forms_frmdef'], 'name',
                            "id='" . DB_escapeString($this->frm_id) . "'"),
//            'frm_id'    => $this->Form->id,
            'frm_id'    => $this->frm_id,
            'fld_id'    => $this->fld_id,
            'name'      => $this->name,
            'type'      => $this->type,
            'valuestr'  => $value_str,
            'defvalue'  => isset($this->options['default']) ? $this->options['default'] : '',
            'prompt'    => $this->prompt,
            'size'      => isset($this->options['size']) ? $this->options['size'] : 0,
            'cols'      => isset($this->options['cols']) ? $this->options['cols'] : 0,
            'rows'      => isset($this->options['rows']) ? $this->options['rows'] : 0,
            'maxlength' => isset($this->options['maxlength']) ? $this->options['maxlength'] : 0,
            'ena_chk'   => $this->enabled == 1 ? 'checked="checked"' : '',
            'span_chk'  => isset($this->options['spancols']) && $this->options['spancols'] == 1 ? 'checked="checked"' : '',
            'format'    => $format_str,
            'doc_url'   => FRM_getDocURL('field_def.html'),
            'mask'      => isset($this->options['mask']) ? $this->options['mask'] : '',
            'vismask'   => isset($this->options['vismask']) ? $this->options['vismask'] : '',
            'autogen_sel_' . $autogen_opt => ' selected="selected"',
            'stripmask_chk' => (isset($this->options['stripmask']) &&
                        $this->options['stripmask']  == 1) ?
                        'checked="checked"' : '',
            'input_format' => self::DateFormatSelect($curdtformat),
            'orderby'   => $this->orderby,
            'editing'   => $this->isNew ? '' : 'true',
            'orderby_selection' => $orderby_list,
            'list_input' => $listinput,
            'help_msg'  => $this->help_msg,
            'fill_gid_select' => FRM_GroupDropdown($this->fill_gid, 3),
            'results_gid_select' => FRM_GroupDropdown($this->results_gid, 3),
            'permissions' => SEC_getPermissionsHTML(
                    $this->perm_owner, $this->perm_group,
                    $this->perm_members, $this->perm_anon),
            'access_chk' . $this->access => 'selected="selected"',
        ) );

        $T->parse('output', 'editform');
        $retval .= $T->finish($T->get_var('output'));

        return $retval;
    }


    /**
    *   Save the field definition to the database.
    *
    *   @param  mixed   $val    Value to save
    *   @return string          Error message, or empty string for success
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
        $A['name'] = COM_sanitizeID($A['name'], false);
        if (empty($A['name']) || empty($A['type']))
            return;

        $this->SetVars($A, false);
        $this->fill_gid = $A['fill_gid'];
        $this->results_gid = $A['results_gid'];

        if ($fld_id > 0) {
            // Existing record, perform update
            $sql1 = "UPDATE {$_TABLES['forms_flddef']} SET ";
            $sql3 = " WHERE fld_id = $fld_id";
        } else {
            $sql1 = "INSERT INTO {$_TABLES['forms_flddef']} SET ";
            $sql3 = '';
        }

        $sql2 = "frm_id = '" . DB_escapeString($this->frm_id) . "',
                name = '" . DB_escapeString($this->name) . "',
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
        //echo $sql;die;
        DB_query($sql, 1);

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
    *   Delete the current field definition.
    *
    *   @param  integer $fld_id     ID number of the field
    */
    public static function Delete($fld_id=0)
    {
        global $_TABLES;

        DB_delete($_TABLES['forms_values'], 'fld_id', $fld_id);
        DB_delete($_TABLES['forms_flddef'], 'fld_id', $fld_id);
    }


    /**
    *   Save this field to the database.
    *
    *   @uses   AutoGen()
    *   @param  mixed   $newval Data value to save
    *   @param  integer $res_id Result ID associated with this field
    *   @return boolean     True on success, False on failure
    */
    public function SaveData($newval, $res_id)
    {
        global $_TABLES;

        $res_id = (int)$res_id;
        if ($res_id == 0)
            return false;

        if (isset($this->options['autogen']) &&
            $this->options['autogen'] == FRM_AUTOGEN_SAVE) {
            $newval = self::AutoGen($this->properties, 'save');
        }

        // Put the new value back into the array after sanitizing
        $this->value = $newval;
        $db_value = $this->prepareForDB($newval);

        //$this->name = $name;
        $sql = "INSERT INTO {$_TABLES['forms_values']}
                    (results_id, fld_id, value)
                VALUES (
                    '$res_id',
                    '{$this->fld_id}',
                    '$db_value'
                )
                ON DUPLICATE KEY
                    UPDATE value = '$db_value'";
        COM_errorLog($sql);
        DB_query($sql, 1);
        $status = DB_error();
        return $status ? false : true;
    }


    /**
    *   Rudimentary date display function to mimic strftime()
    *   Timestamps don't handle dates far in the past or future.  This function
    *   does a str_replace using a subset of PHP's date variables.  Only the
    *   numeric variables with leading zeroes are used.
    *
    *   @return string  Date formatted for display
    */
    public function DateDisplay()
    {
        if ($this->type != 'date')
            return $this->value_text;

        $dt_tm = explode(' ', $this->value);
        if (strpos($dt_tm[0], '-')) {
            list($year, $month, $day) = explode('-', $dt_tm[0]);
        } else {
            $year = '0000';
            $month = '01';
            $day = '01';
        }
        if (isset($dt_tm[1]) && strpos($dt_tm[1], ':')) {
            list($hour, $minute, $second) = explode(':', $dt_tm[1]);
        } else {
            $hour = '00';
            $minute = '00';
            $second = '00';
        }

        switch ($this->options['input_format']) {
        case 2:
            $retval = sprintf('%02d/%02d/%04d', $day, $month, $year);
            break;
        case 1:
        default:
            $retval = sprintf('%02d/%02d/%04d', $month, $day, $year);
            break;
        }
        if ($this->options['showtime'] == 1) {
            if ($this->options['timeformat'] == '12') {
                list($hour, $ampm) = $this->hour24to12($hour);
                $retval .= sprintf(' %02d:%02d %s', $hour, $minute, $ampm);
            } else {
                $retval .= sprintf(' %02d:%02d', $hour, $minute);
            }
        }

        /*if (empty($this->options['format']))
            return $this->value;

        $formats = array('%Y', '%d', '%m', '%H', '%i', '%s');
        $values = array($year, $day, $month, $hour, $minute, $second);
        $retval = str_replace($formats, $values, $this->options['format']);*/

        return $retval;
    }


    /**
    *   Get the defined date formats into an array.
    *   Static for now, maybe allow more user-defined options in the future.
    *
    *   return  array   Array of date formats
    */
    public function DateFormats()
    {
        global $LANG_FORMS;
        $_formats = array(
            1 => $LANG_FORMS['month'].' '.$LANG_FORMS['day'].' '.$LANG_FORMS['year'],
            2 => $LANG_FORMS['day'].' '.$LANG_FORMS['month'].' '.$LANG_FORMS['year'],
        );
        return $_formats;
    }


    /**
    *   Provide a dropdown selection of date formats
    *
    *   @param  integer $cur    Option to be selected by default
    *   @return string          HTML for selection, without select tags
    */
    public function DateFormatSelect($cur=0)
    {
        $retval = '';
        $_formats = self::DateFormats();
        foreach ($_formats as $key => $string) {
            $sel = $cur == $key ? 'selected="selected"' : '';
            $retval .= "<option value=\"$key\" $sel>$string</option>\n";
        }
        return $retval;
    }


    /**
    *   Validate the submitted field value(s)
    *
    *   @param  array   $vals  All form values
    *   @return string      Empty string for success, or error message
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
            if (empty($vals[$this->name . '_month']) ||
                empty($vals[$this->name . '_day']) ||
                empty($vals[$this->name . '_year'])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        case 'time':
            if (empty($vals[$this->name . '_hour']) ||
                empty($vals[$this->name . '_minute'])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        case 'radio':
            if (empty($vals[$this->name])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        default:
            if (empty($vals[$this->name])) {
                $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
            }
            break;
        }
        return $msg;
    }


    /**
    *   Copy this field to another form.
    *
    *   @see    Form::Duplicate()
    */
    public function Duplicate()
    {
        global $_TABLES;

        if (is_array($this->options)) {
            $options = serialize($this->options);
        } else {
            $options = $this->options;
        }

        $sql .= "INSERT INTO {$_TABLES['forms_flddef']} SET
                frm_id = '" . DB_escapeString($this->frm_id) . "',
                name = '" . DB_escapeString($this->name) . "',
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
    *   Move a form field up or down in the form.
    *
    *   @param  integer $id     Record ID to move
    *   @param  string  $where  Direction to move ('up' or 'down')
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
    *   Reorder the fields appearance on the form
    *
    *   @param  integer $frm_id     ID of form being reordered
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
    *   Get the default value for a field.
    *   Normally this will be the configured default retuned verbatim.
    *   It could also be a value from the $_USER array (more maybe to follow).
    *
    *   @uses   AutoGen()
    *   @param  string  $def    Defined default value
    *   @return string          Actual text to use as the field value.
    */
    public function GetDefault($def = '')
    {
        global $_USER;

        if (empty($def) &&
                isset($this->options['autogen']) &&
                $this->options['autogen'] == FRM_AUTOGEN_FILL) {
            return self::AutoGen($this->name, 'fill');
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
    *   Create the time field.
    *   This is in a separate function so it can be used by both date
    *   and time fields.
    *
    *   @uses   hour24to12()
    *   @param  string  $timestr    Optional HH:MM string.  Seconds ignored.
    *   @return string  HTML for time selection field
    */
    public function TimeField($timestr = '')
    {
        $ampm_fld = '';
        $hour = '';
        $minute = '';

        // Check for POSTed values first, coming from a previous form
        // If one is set, all should be set, and empty values are ok
        if (isset($_POST[$this->name . '_hour']) &&
            isset($_POST[$this->name . '_minute'])) {
            $hour = (int)$_POST[$this->name . '_hour'];
            $minute = (int)$_POST[$this->name . '_minute'];
        }
        if (empty($hour) || empty($minute)) {
            if (!empty($timestr)) {
                // Default to the specified time string
                list($hour, $minute)  = explode(':', $timestr);
            } elseif (!empty($this->options['default'])) {
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

        if ($this->options['timeformat'] == '12') {
            list($hour, $ampm_sel) = $this->hour24to12($hour);
            $ampm_fld = COM_getAmPmFormSelection($this->name . '_ampm', $ampm_sel);
        }

        $h_fld = '<select name="' . $this->name . '_hour">' . LB .
                COM_getHourFormOptions($hour, $this->options['timeformat']) .
                '</select>' . LB;
        $m_fld = '<select name="' . $this->name . '_minute">' . LB .
                COM_getMinuteFormOptions($minute) .
                '</select>' . LB;
        return $h_fld . ' ' . $m_fld . $ampm_fld;
    }


    /**
    *   Convert an hour from 24-hour to 12-hour format for display.
    *
    *   @param  integer $hour   Hour to convert
    *   @return array       array(new_hour, ampm_indicator)
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
    *   Auto-generate a field value.
    *   Calls the first available function from the following list:
    *   <ol><li>CUSTOM_forms_autogen_$type_$varname()</li>
    *       <li>CUSTOM_forms_autogen_$varname()</li>
    *       <li>CUSTOM_forms_autogen()</li>
    *       <li>COM_makeSid()</li>
    *   </ol>
    *
    *   @param  string  $A      Array of variable info
    *   @param  string  $type   'fill' or 'save' to indicate which function
    *   @return mixed       Generated field value
    */
    public static function AutoGen($A, $type, $uid = 0)
    {
        global $_USER;

        if ($type != 'fill') $type = 'save';
        if ($uid == 0) $uid = (int)$_USER['uid'];
        $var = $A['name'];

        $function = 'CUSTOM_forms_autogen';
        if (function_exists($function . '_' . $type . '_' . $var))
            $retval = $function . '_' . $type . '_' . $var($A, $uid);
        elseif (function_exists($function . '_' . $var))
            $retval =  $function . '_' . $var($A, $uid);
        elseif (function_exists($function))
            $retval = $function($A, $uid);
        else
            $retval = COM_makeSID();

        return $retval;
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
    *   Get the HTML element ID based on the form and field ID.
    *   This is for ajax fields that store values in session variables
    *   instead of result sets.
    *   Also uses the field value if available and needed, such as for
    *   multi-checkboxes.
    *
    *   @param  string  $val    Optional field value
    *   @return string          ID string for the field element
    */
    public function _elemID($val = '')
    {
        $name  = str_replace(' ', '', $this->name);
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
     * @return  string      String like "forms.formid.fieldid"
     */
    public static function sessID($frm_id, $fld_id)
    {
        return 'forms.' . $frm_id . '.' . $fld_id;
    }


    /**
    *   Default function to get the field value from the form
    *   Just returns the form value
    *   @param  array   $A      Array of form values, e.g. $_POST
    *   @return mixed           Field value
    */
    public function valueFromForm($A)
    {
        return isset($A[$this->name]) ? $A[$this->name] : '';
    }


    /**
    *   Get the value from the database.
    *   Typically this is just copying the "value" field, but
    *   some field types may need to unserialize values.
    *
    *   @param  array   $A      Array of all DB fields
    *   @return mixed           Value field used by the object
    */
    public function valueFromDB($A)
    {
        return $A['value'];
    }


    /**
    *   Default function to get the display value for a field
    *   Just returns the raw value
    *
    *   @param  array   $fields     Array of all field objects (for calc-type)
    *   @return string      Display value
    */
    public function displayValue($fields)
    {
        global $_GROUPS;

        if (!$this->canViewResults()) return NULL;
        return htmlspecialchars($this->value);
    }


    /**
    *   Default function to get the field prompt.
    *   Gets the user-defined prompt, if any, or falls back to the field name.
    *
    *   @return string  Field prompt
    */
    public function displayPrompt()
    {
        return $this->prompt == '' ? $this->name : $this->prompt;
    }


    public function setValue($value)
    {
        return trim($value);
    }


    /**
    *   Get the submission type of the parent form
    *
    *   @return string  Submission type ("ajax" or "regular")
    */
    protected function getSubType()
    {
        static $sub_type = NULL;
        if ($sub_type === NULL) {
            $form = Form::getInstance($this->frm_id);
            if (!$form) {
                $sub_type = 'regular';
            } else {
                $sub_type = $form->sub_type;
            }
        }
        return $sub_type;
    }


    /**
    *   Get the value to be rendered in the form
    *
    *   @param  integer $res_id     Result set ID
    *   @param  string  $mode       View mode, e.g. "preview"
    *   @return mixed               Field value used to populate form
    */
    protected function renderValue($res_id, $mode, $valname = '')
    {
        $value = '';
        if (isset($_POST[$this->name])) {
            // First, check for a POSTed value. The form is being redisplayed.
            $value = $_POST[$this->name];
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
    *   Helper function to get the access string for fields.
    *
    *   @return string  Access-control string, e.g. "required" or "disabled"
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
    *   Get the Javascript string for AJAX fields
    *
    *   @param  string  $mode   View mode, e.g. "preview"
    *   @return string          Javascript to save the data
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
    *   Get the data formatted for saving to the database
    *   Field types can override this as needed.
    *
    *   @param  mixed   $newval     New data to save
    *   @return mixed       Data formatted for the DB
    */
    protected function prepareForDB($newval)
    {
        return DB_escapeString(COM_checkWords(strip_tags($newval)));
    }


    /**
    *   Get the default option values from a field definition form.
    *   Should be called by child objects in their own optsFromForm function.
    *
    *   @param  array   $A      Array of all form fields
    *   @return array           Field options
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
        return $options;
    }


    /**
    *   Check if the current user can render this form field.
    *   Checks that the user is a member of fill_gid and the field is enabled.
    *   Caches the status for each fill_gid since this gets called for
    *   each field, and fill_gid is likely to be the same for all.
    *
    *   @return boolean     True if the field can be rendered, False if not.
    */
    protected function canViewField()
    {
        global $_GROUPS;
        static $gids = array();

        if (!array_key_exists($this->fill_gid, $gids)) {
            if ($this->enabled == 0 || !in_array($this->fill_gid, $_GROUPS)) {
                $gids[$this->fill_gid] = false;
            } else {
                $gids[$this->fill_gid] = true;
            }
        }
        return $gids[$this->fill_gid];
    }


    /**
    *   Check if the current user can view the results for this field.
    *   Checks that the user is a member of results_gid and the field is enabled.
    *   Caches the status for each results_gid since this gets called for
    *   each field, and results_gid is likely to be the same for all.
    *
    *   @return boolean     True if the user can view, False if not.
    */
    public function canViewResults()
    {
        global $_GROUPS, $_USERS;
        static $gids = array();

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
    *   Return the XML element for privacy export.
    *
    *   @return string  XML element string: <fld_name>data</fld_name>
    */
    public function XML()
    {
        $retval = '';
        $Form = Form::getInstance($this->frm_id);
        $d = addSlashes(htmlentities(trim($this->displayValue($Form->fields))));
        // Replace spaces in prompts with underscores, then remove all other
        // non-alphanumerics
        $p = str_replace(' ', '_', $this->prompt);
        $p = preg_replace("/[^A-Za-z0-9_]/", '', $p);

        if (!empty($d)) {
            $retval .= "<$p>$d</$p>\n";
        }
        return $retval;
    }

}

?>
