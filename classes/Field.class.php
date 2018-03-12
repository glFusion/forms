<?php
/**
*   Class to handle individual form fields.
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
*   Class for form fields
*/
class Field
{
    public $isNew;
    protected $options = array();
    protected $properties = array();
    protected $Form = NULL;

    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  integer $id     ID of the existing field, empty if new
    *   @param  object  $Form   Form object to which this field belongs
    */
    public function __construct($id = 0, $Form = NULL)
    {
        global $_USER, $_CONF_FRM;

        $id = (int)$id;

        $this->isNew = true;
        $this->fld_id = $id;
        if (!empty($Form)) {
            if (is_object($Form)) {
                $this->Form = $Form;
            } else {
                // A form ID was passed in
                $this->Form = new Form($Form);
            }
        }

        if ($id == 0) {
            $this->name = '';
            $this->type = 'text';
            $this->enabled = 1;
            $this->access = 0;
            $this->prompt = '';
            $this->options = array(
                'rows' => $_CONF_FRM['def_textarea_rows'],
                'cols' => $_CONF_FRM['def_textarea_cols'],
                'size' => $_CONF_FRM['def_text_size'],
                'maxlength' => $_CONF_FRM['def_text_maxlen'],
                'spancols' => 0,
            );

            // Get the group IDs that can fill out and view results for this
            // field
            if (is_object($this->Form)) {
                $this->results_gid = $this->Form->results_gid;
                $this->fill_gid = $this->Form->fill_gid;
            } else {
                $this->fill_gid = $_CONF_FRM['def_fill_gid'];
                $this->results_gid = $_CONF_FRM['def_results_gid'];
            }
        } else {
            if ($this->Read($id)) {
                $this->isNew = false;
            }
            if (empty($this->Form)) $this->Form = new Form($this->frm_id);
        }
    }


    public static function getInstance($fld_id, $frm_obj = NULL)
    {
        static $_fields = array();
        $fld_id = (int)$fld_id;
        if (!array_key_exists($fld_id, $_fields)) {
            $_fields[$fld_id] = new self($fld_id, $frm_obj);
        }
        return $_fields[$fld_id];
    }


    /**
    *   Read this field definition from the database.
    *
    *   @see Field::SetVars
    *   @param  string  $name   Optional field name
    *   @return boolean     Status from SetVars()
    */
    public function Read($id = 0)
    {
        global $_TABLES;

        if ($id != 0) $this->fld_id = $id;
        $sql = "SELECT * FROM {$_TABLES['forms_flddef']}
                WHERE fld_id='" . (int)$this->fld_id . "'";
        $res = DB_query($sql, 1);
        if (!$res) return false;
        return $this->SetVars(DB_fetchArray($res, false), true);
    }


    /**
    *   Retrieve this field's value from a specific result set
    *
    *   @param  integer $res_id Result set
    *   @return mixed       Field value
    */
    public function GetValue($res_id)
    {
        global $_TABLES, $_CONF_FRM;

        $res_id = (int)$res_id;
        if ($this->type == 'calc') {
            $valnames = explode(',', $this->options['value']);
            $values = array();
            foreach ($valnames as $val) {
                if (is_numeric($val)) {     // Handle constants
                    $values[] = $val;
                } elseif ($val == $this->name) {    // avoid recursion
                    continue;
                } else {
                    $fld_id = DB_getItem($_TABLES['forms_flddef'], 'fld_id',
                        "frm_id = '" . DB_escapeString($this->frm_id) .
                        "' AND name = '" . DB_escapeString($val) . "'");
                    if (empty($fld_id)) continue;
                    $fld = new Field($fld_id);
                    $values[] = $fld->GetValue($res_id);
                }
            }
            if (!empty($values)) {
                $result = $values[0];
                $valcount = count($values);
                switch ($this->options['calc_type']) {
                case 'add':
                    for ($i = 1; $i < $valcount; $i++) {
                        if (!is_numeric($values[$i])) continue;
                        $result += $values[$i];
                    }
                    break;
                case 'sub':
                    for ($i = 1; $i < $valcount; $i++) {
                        if (!is_numeric($values[$i])) continue;
                        $result -= $values[$i];
                    }
                    break;
                case 'div':
                    for ($i = 1; $i < $valcount; $i++) {
                        if (!is_numeric($values[$i]) || $values[$i] == 0)
                            continue;
                        $result /= $values[$i];
                    }
                    break;
                case 'mul':
                    for ($i = 1; $i < $valcount; $i++) {
                        if (!is_numeric($values[$i])) continue;
                        $result *= $values[$i];
                    }
                    break;
                case 'mean':
                    if ($valcount < 1) {    // protect against div by zero
                        $result = 0;
                    } else {
                        for ($i = 1; $i < $valcount; $i++) {
                            if (!is_numeric($values[$i])) continue;
                            $result += $values[$i];
                        }
                        $result /= $valcount;
                    }
                    break;
                }
            }
            if (is_numeric($result)) {
                $format_str = empty($this->options['format']) ?
                            $_CONF_FRM['def_calc_format'] :
                            $this->options['format'];
                $result = sprintf($format_str, $result);
            }
            $this->value = $result;
            $value = $result;
        } elseif ($this->type == 'static') {
            $value = $this->options['value'];
            $value_text = $value;
        } else {
            $value = DB_getItem($_TABLES['forms_values'], 'value',
                    "results_id='$res_id' AND fld_id='{$this->fld_id}'");
            if ($this->type == 'numeric') {
                $val = (float)$value;
                $format_str = empty($this->options['format']) ?
                            $_CONF_FRM['def_calc_format'] :
                            $this->options['format'];
                $this->value = sprintf($format_str, $val);
            } else {
                $this->value = $value;
            }
        }
        return $value;
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
        /*case 'perm_owner':
        case 'perm_group':
        case 'perm_members':
        case 'perm_anon':*/
            $this->properties[$name] = (int)$value;
            break;

        case 'enabled':
        //case 'required':
            $this->properties[$name] = $value == 0 ? 0 : 1;
            break;

        case 'calcvalues':
            if (is_array($value))
                $this->properties[$name] = $value;
            else
                $this->properties[$name] = array();
            break;
        /*case 'options':
            $this->properties['options'] = unserialize($value);
            if (!$this->properties['options'])
                $this->properties['options'] = array();
            break;*/

        case 'prompt':
        case 'name':
        case 'type':
        case 'help_msg':
            $this->properties[$name] = trim($value);
            break;

        case 'value':
            switch ($this->type) {
            case 'integer':
                $this->properties['value'] = (int)$value;
                $this->properties['value_text'] = (int)$value;
                break;
            case 'radio':
            case 'select':
                $this->properties['value'] = trim($value);
                $this->properties['value_text'] = $this->value;
                break;
            case 'checkbox':
                if ($value == 1) {
                    $this->properties['value'] = 1;
                    $this->properties['value_text'] = $LANG_FORMS['yes'];
                } else {
                    $this->properties['value'] = 0;
                    $this->properties['value_text'] = $LANG_FORMS['no'];
                }
                break;
            case 'multicheck':
                if (!is_array($value)) {
                    // should be a serialized string from the DB
                    $this->properties['value'] = unserialize($value);
                    if (!$this->properties['value'])
                        $this->properties['value'] = $value;
                } else {
                    // already an array, from a form
                    $this->properties['value'] = $value;
                }
                if (is_array($this->properties['value'])) {
                    /*$strings = array();
                    foreach($this->value as $idx=>$val) {
                        $strings[] = $this->stringFromValues($val);
                    }*/
                    $this->properties['value_text'] =
                        implode(', ', $this->properties['value']);
                } else {
                    $this->properties['value_text'] = $this->properties['value'];
                }
                break;
            case 'date':
                $this->properties['value'] = trim($value);
                $this->properties['value_text'] = $this->DateDisplay();
                break;
            case 'time':
                $this->properties['value'] = trim($value);
                list($hour, $min, $sec) =
                            explode(':', $this->properties['value']);
                if ($this->options['timeformat'] == '12') {
                    list($hour, $ampm) = $this->hour24to12($hour);
                    $this->properties['value_text'] = sprintf('%02d:%02d %s',
                            $hour, $min, $ampm);
                } else {
                    $this->properties['value_text'] = sprintf('%02d:%02d',
                            $hour, $min);
                }
                break;
            case 'numeric':
                $this->properties['value'] = (float)$value;
                $this->properties['value_text'] = $value;
                break;
            case 'text':
            case 'textarea':
            default:
                $value = trim($value);
                $this->properties['value'] = $value;
                $this->properties['value_text'] = $value;
                break;

            }

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
        $this->orderby = $A['orderby'];
        $this->enabled = $A['enabled'];
        //$this->required = $A['required'];
        $this->access = $A['access'];
        $this->prompt = $A['prompt'];
        $this->name = $A['name'];
        // Make sure 'type' is set before 'value'
        $this->type = $A['type'];
        $this->help_msg = $A['help_msg'];
        $this->results_gid = $A['results_gid'];
        $this->fill_gid = $A['fill_gid'];

        //if ($fromdb) {
        //    $this->Set('value', $A['value']);
        //} else {
        if (!$fromdb) {

            if ($this->type == 'date') {
                $dt = array('0000', '00', '00');
                if (isset($_POST[$this->name . '_month'])) {
                    $dt[1] = (int)$_POST[$this->name . '_month'];
                }
                if (isset($_POST[$this->name . '_day'])) {
                    $dt[2] = (int)$_POST[$this->name . '_day'];
                }
                if (isset($_POST[$this->name . '_year'])) {
                    $dt[0] = (int)$_POST[$this->name . '_year'];
                }
                if ($this->options['century'] == 1 && $dt[0] < 100) {
                    $dt[0] += 2000;
                }
                $tmpval = sprintf('%04d-%02d-%02d',
                        $dt[0] . '-' . $dt[1] . '-' . $dt[2]);

                if (isset($this->options['showtime']) &&
                        $this->options['showtime'] == 1) {
                    $hour = isset($_POST[$this->name . '_hour']) ?
                                (int)$_POST[$this->name . '_hour'] : 0;
                    $minute = isset($_POST[$this->name . '_minute']) ?
                                (int)$_POST[$this->name . '_minute'] : 0;
                    $tmpval .= sprintf(' %02d:%02d', $hour, $minute);
                }
                $this->value = $tmpval;
            } else {
                $this->value = $A['value'];
            }

        } else {
            $this->options = @unserialize($A['options']);
            if (!$this->options) $this->options = array();
            //$this->value = $this->options['default'];
            if ($this->type == 'static') {
                $this->value = $this->options['default'];
            }
        }

        return true;
    }


    /**
    *   Get the string that corresponds to a single value option given
    *   a string of "name:value;" option pairs.
    *
    *   @param  string  $value  Name of value key to check
    *   @return string          String associated with $value
    */
    public function stringFromValues($val)
    {
        // If the request value is set in the array, return it.
        // Otherwise, return an empty string.
        if (isset($this->options['values'][$val])) {
            return $this->options['values'][$val];
        } else {
            return $val;
        }
    }


    /**
    *   Create a single form field for data entry.
    *
    *   @param  integer     Results ID, zero for new form
    *   @return string      HTML for this field, including prompt
    */
    public function Render($res_id = 0)
    {
        global $_CONF, $LANG_FORMS, $_CONF_FRM;

        // If POSTed form data, set the user variable to that.  Otherwise,
        // set it to the default or leave it alone. Since an empty checkbox
        // isn't posted, check if there's a positive result id. If so, then
        // this is rendering an existing form so just show the values.
        // If not, then this is a new form and should get the defaults.
        // Can't rely totally on $res_id since it only works for forms that
        // are saving data to the DB
        if (isset($_POST[$this->name])) {
            $this->value = $_POST[$this->name];
        } elseif ($res_id == 0) {
            if ($this->Form->sub_type == 'ajax' && SESS_isSet($this->_elemID())) {
                $this->value = SESS_getVar($this->_elemID());
            } elseif (isset($this->options['default'])) {
                $this->value = $this->GetDefault($this->options['default']);
            } else {
                $this->value = '';
            }
        }

        $readonly = '';
        $class = '';
        $elem_id = $this->_elemID();
        switch ($this->access) {
        case FRM_FIELD_READONLY:
            $readonly = 'disabled="disabled"';
            break;
        case FRM_FIELD_HIDDEN:
            $fld = '<input type="hidden" name="' . $this->name .
                    '" value="' . $this->value_text .
                    '" id="' . $elem_id . '"/>';
            return $fld;
            break;
        case FRM_FIELD_REQUIRED:
            $class .= 'required';
            break;
        default:
            break;
        }
        if ($this->Form->sub_type = 'ajax') {
            $js = "onchange=\"FORMS_ajaxSave('" . $this->frm_id . "','" . $this->fld_id .
                    "',this);\"";
        } else {
            $js = '';
        }
        //  Create the field HTML based on the type of field.
        switch ($this->type) {
        case 'text':
        default:
            $size = $this->options['size'];
            $maxlength = min($this->options['maxlength'], 255);

            $fld = "<input $class name=\"{$this->name}\"
                    id=\"$elem_id\"
                    size=\"$size\" maxlength=\"$maxlength\"
                    type=\"text\" value=\"{$this->value_text}\" $readonly $js />\n";
            break;

        case 'textarea':
            $cols = $this->options['cols'];
            $rows = $this->options['rows'];
            $fld = "<textarea name=\"{$this->name}\"
                    id=\"$elem_id\"
                    cols=\"$cols\" rows=\"$rows\"
                    >{$this->value_text}</textarea>\n";
            break;

        case 'checkbox':
            $chk = $this->value == 1 ? 'checked="checked"' : '';
            $fld = "<input $class name=\"{$this->name}\"
                    id=\"$elem_id\" type=\"checkbox\" value=\"1\"
                    $chk $readonly $js />\n";
            break;

        case 'multicheck':
            $values = FRM_getOpts($this->options['values']);
            if (!is_array($values)) {
                // Have to have some values for radio buttons
                break;
            }
            $fld = '';
            foreach ($values as $id=>$value) {
                if ($this->Form->sub_type == 'ajax') {
                    $tmp = SESS_getVar($this->_elemID($value));
                    $sel = $tmp == 1 ? 'checked="checked"' : '';
                } else {
                    if (is_array($this->value)) {
                        $sel = in_array($value, $this->value) ?
                            'checked="checked"' : '';
                    } else {
                        $sel = $value == $this->value ? 'checked="checked"' : '';
                    }
                }
                    $fld .= "<input $class type=\"checkbox\"
                        name=\"{$this->name}[]\"
                        id=\"" . $elem_id . '_' . str_replace(' ', '', $value) . "\"
                        value=\"$value\" $sel $readonly $js>&nbsp;$value&nbsp;\n";
            }
            break;

        case 'select':
            $values = FRM_getOpts($this->options['values']);
            if (empty($values)) break;

            $fld = "<select $class name=\"{$this->name}\"
                    id=\"$elem_id\" $readonly $js>\n";
            $fld .= "<option value=\"\">{$LANG_FORMS['select']}</option>\n";
            foreach ($values as $id=>$value) {
                $sel = $this->value == $value ? 'selected="selected"' : '';
                $fld .= "<option value=\"$value\" $sel>{$value}</option>\n";
            }
            $fld .= "</select>\n";
            break;

        case 'radio':
            $values = FRM_getOpts($this->options['values']);
            if (empty($values)) break;

            // If no current value, use the defined default
            if (is_null($this->value)) {
                $this->value = $this->options['default'];
            }

            $fld = '';
            foreach ($values as $id=>$value) {
                $sel = $this->value == $value ? 'checked="checked"' : '';
                $fld .= "<input $class type=\"radio\" name=\"{$this->name}\"
                        id=\"" . $elem_id . '_' . $value . "\"
                        value=\"$value\" $sel $readonly $js>&nbsp;$value&nbsp;\n";
            }
            break;

        case 'date':
            $fld = '';
            $dt = array();
            // Check for POSTed values first, coming from a previous form
            // If one is set, all should be set, and empty values are ok
            if (isset($_POST[$this->name . '_month'])) {
                $dt[1] = $_POST[$this->name . '_month'];
            }
            if (isset($_POST[$this->name . '_day'])) {
                $dt[2] = $_POST[$this->name . '_day'];
            }
            if (isset($_POST[$this->name . '_year'])) {
                $dt[0] = $_POST[$this->name . '_year'];
            }

            // Nothing from POST, check for an existing value.  If none,
            // use the default.
            $value = $this->value;
            if (empty($dt)) {
                if (empty($value) && isset($this->options['default']) && !empty($this->options['default'])) {
                    $this->value = $this->options['default'];
                } else {
                    $dt = new \Date('now', $_CONF['timezone']);
                    $this->value = $dt->format('Y-m-d', true);
                }
                $datestr = explode(' ', $this->value);  // separate date & time
                $dt = explode('-', $datestr[0]);        // get date components
            }

            $m_fld = $LANG_FORMS['month'] .
                    ": <select $class id=\"{$this->name}_month\" name=\"{$this->name}_month\">\n";
            $m_fld .= "<option value=\"0\">--{$LANG_FORMS['select']}--</option>\n";
            $m_fld .= COM_getMonthFormOptions($dt[1]) . "</select>\n";

            $d_fld = $LANG_FORMS['day'] .
                    ": <select $class id=\"{$this->name}_day\" name=\"{$this->name}_day\">\n";
            $d_fld .= "<option value=\"0\">--{$LANG_FORMS['select']}--</option>\n";
            $d_fld .= COM_getDayFormOptions($dt[2]) . "</select>\n";

            $y_fld = $LANG_FORMS['year'] .
                    ': <input ' . $class . ' type="text" id="' . $this->name.'_year" name="'.$this->name.'_year"
                    size="5" value="' . $dt[0] . "\"/>\n";

            switch ($this->options['input_format']) {
            case 1:
                $fld .= $m_fld . ' ' . $d_fld . ' ' . $y_fld;
                break;
            case 2:
                $fld .= $d_fld . ' ' . $m_fld . ' ' . $y_fld;
                break;
            }

            if ($this->options['showtime'] == 1) {
                $fld .= ' ' . $this->TimeField($datestr[1]);
                $timeformat = $this->options['timeformat'];
            } else {
                $timeformat = 0;
            }
                $fld .= '<i id="' . $this->name .
                        '_trigger" class="' . $_CONF_FRM['_iconset'] . '-calendar tooltip" ' .
                        'title="' . $LANG_FORMS['datepicker'] . '"></i>';
            $fld .= LB . "<script type=\"text/javascript\">
Calendar.setup({
    inputField  :    \"{$this->name}dummy\",
    ifFormat    :    \"%Y-%m-%d\",
    showsTime   :    false,
    timeFormat  :    \"{$timeformat}\",
    button      :   \"{$this->name}_trigger\",
    onUpdate    :   {$this->name}_onUpdate
});
function {$this->name}_onUpdate(cal)
{
    var d = cal.date;

    if (cal.dateClicked && d) {
        FRM_updateDate(d, \"{$this->name}\", \"{$timeformat}\");
    }
    return true;
}
</script>" . LB;
             break;

        case 'time':
            $fld .= $this->TimeField($this->value);
            break;

        case 'static':
            // Static field, just render it as entered.
            $fld .= $this->GetDefault($this->value_text);
            break;

        case 'calc':
            // Render calculated field as text.
            $fld = '';
            break;

        }
        return $fld;
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
        $T = FRM_getTemplate('editfield', 'editform', '/admin');

        // Create the "Field Type" dropdown
        $type_options = '';
//        $ajax_opts = array('checkbox', 'radio', 'select');
        foreach ($LANG_FORMS['fld_types'] as $option => $opt_desc) {
//            if ($this->Form->sub_type == 'ajax' && !in_array($option, $ajax_opts)) {
//                continue;
//            }
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
            $values = FRM_getOpts($this->options['values']);
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
            $format_str = empty($this->options['format']) ?
                    $_CONF_FRM['def_calc_format'] : $this->options['format'];
            break;

        case 'static':
            $value_str = $this->options['default'];
            break;

        }

        // Create the selection list for the "Position After" dropdown.
        // Include all options *except* the current one
        $sql = "SELECT orderby, name
                FROM {$_TABLES['forms_flddef']}
                WHERE fld_id <> '{$this->fld_id}'
                AND frm_id = '{$this->Form->id}'
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
            'frm_id'    => $this->Form->id,
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
            //'readonly_chk' => $A['readonly'] == 1 ? 'checked="checked"' : '',
            //'req_chk'   => $this->required == 1 ? 'checked="checked"' : '',
            'span_chk'  => isset($this->options['spancols']) && $this->options['spancols'] == 1 ? 'checked="checked"' : '',
            //'orderby'   => $A['orderby'],
            'format'    => $format_str,
            'doc_url'   => FRM_getDocURL('field_def.html'),
            'mask'      => isset($this->options['mask']) ? $this->options['mask'] : '',
            'vismask'   => isset($this->options['vismask']) ? $this->options['vismask'] : '',
            /*'autogen_chk' => (isset($this->options['autogen']) &&
                        $this->options['autogen']  == 1) ?
                        'checked="checked"' : '',*/
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

        // Sanitize the entry ID
        //$id = empty($this->id) ? '' : $this->id;
        $fld_id = isset($A['fld_id']) ? (int)$A['fld_id'] : 0;
        $frm_id = isset($A['frm_id']) ? COM_sanitizeID($A['frm_id']) : '';
        if ($frm_id == '') {
            return 'Invalid form ID';
        }
        // Sanitize the name, especially make sure there are no spaces
        $A['name'] = COM_sanitizeID($A['name'], false);
        if (empty($A['name']) || empty($A['type']))
            return;

        // Put this field at the end of the line by default
        if (empty($A['orderby']))
            $A['orderby'] = 255;
        else
            $A['orderby'] = (int)$A['orderby'];

        // Set the size and maxlength to reasonable values
        $A['maxlength'] = min((int)$A['maxlength'], 255);
        $A['size'] = min((int)$A['size'], 80);

        // Reset the default value to NULL if nothing is entered
        if (empty($A['defvalue']))
            $A['defvalue'] = '';

        // Set the options and default values according to the data type
        $A['options'] = '';
        $options = array();

        // Options that should be in any field, used or not.  Checkboxes get
        // their default differently, so this may be overridden.
        $options['default'] = trim($A['defvalue']);

        switch ($A['type']) {
        case 'textarea':
            $options['cols'] = (int)$A['cols'];
            $options['rows'] = (int)$A['rows'];
            if ($options['rows'] == 0)
                $options['rows'] = $_CONF_FRM['def_textarea_rows'];
            if ($options['cols'] == 0)
                $options['cols'] = $_CONF_FRM['def_textarea_cols'];
            break;

        case 'numeric':
            $options['format'] = empty($A['format']) ?
                    $_CONF_FRM['def_calc_format'] : $A['format'];
            // Fall through, numeric field is basically a text field

        case 'text':
            $options['size'] = (int)$A['size'];
            $options['maxlength'] = (int)$A['maxlength'];
            $options['autogen'] = isset($A['autogen']) ? (int)$A['autogen'] :
                        FRM_AUTOGEN_NONE;
            break;

        case 'checkbox':
            // For checkboxes, set the value to "1" automatically
            $A['value'] = '1';
            // Different default value for checkboxes
            $options['default'] = isset($A['defvalue']) &&
                $A['defvalue'] == 1 ? 1 : 0;
            break;

        case 'date':
            $options['showtime'] = ($A['showtime'] == 1 ? 1 : 0);
            $options['timeformat'] = $A['timeformat'] == '24' ? '24' : '12';
            $options['format'] = isset($A['format']) ? $A['format'] :
                        $_CONF_FRM['def_date_format'];
            $options['input_format'] = (int)$A['input_format'];
            $options['century'] = ($A['century'] == 1 ? 1 : 0);
            break;

        case 'time':
            $options['timeformat'] = $A['timeformat'] == '24' ? '24' : '12';
            break;

        case 'select':
        case 'radio':
        case 'multicheck':
            $newvals = array();
            foreach ($A['selvalues'] as $val) {
                if (!empty($val)) {
                    $newvals[] = $val;
                }
            }
            $options['default'] = '';
            if (isset($A['sel_default'])) {
                $default = (int)$A['sel_default'];
                if (isset($A['selvalues'][$default])) {
                    $options['default'] = $A['selvalues'][$default];
                }
            }
            $options['values'] = $newvals;
            break;

        case 'calc':
            $options['value'] = trim($A['valuestr']);
            $options['calc_type'] = $A['calc_type'];
            $options['format'] = empty($A['format']) ?
                    $_CONF_FRM['def_calc_format'] : $A['format'];
            break;

        case 'static':
            $options['default'] = trim($A['valuetext']);
            break;

        }

        // Mask and Visible Mask may exist for any field type, but are set
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

        // This serializes any options set
        $A['options'] = FRM_setOpts($options);

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

        $sql2 = "frm_id = '" . DB_escapeString($A['frm_id']) . "',
                name = '" . DB_escapeString($A['name']) . "',
                type = '" . DB_escapeString($A['type']) . "',
                enabled = '" . (int)$A['enabled'] . "',
                access = '" . (int)$A['access'] . "',
                prompt = '" . DB_escapeString($A['prompt']) . "',
                options = '" . DB_escapeString($A['options']) . "',
                orderby = '" . (int)$A['orderby'] . "',
                help_msg = '" . DB_escapeString($A['help_msg']) . "',
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

        switch ($this->type) {
        // Set the $newval for special cases
        case 'checkbox':
            if (isset($newval) && !empty($newval)) {
                $newval = 1;
            } else {
                $newval = 0;
            }
            break;

        case 'multicheck':
            if (is_array($newval)) {
                $newval = serialize($newval);
            } else {
                $newval = serialize(array());
            }
            break;

        case 'numeric':
            $newval = (float)$newval;
            break;

        default:
            $newval = COM_checkWords(strip_tags($newval));
            break;
        }

        // Put the new value back into the array after sanitizing
        $this->value = $newval;
        $db_value = DB_escapeString($newval);

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
        //COM_errorLog($sql);
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
    *   Calculate the result for this field based on other fields.
    *   This is the same as the code found in GetValue(), except that all
    *   the fields are provided to save time.
    *
    *   @param  array   $fields     Array of field objects
    *   @return float               Calculated value of this field
    */
    public function CalcResult($fields)
    {
        $result = '';

        // Can't calculate if this isn't a calc field.
        if ($this->type != 'calc')
            return 0;

        // Get the calculation definition, return if none defined.
        $valnames = explode(',', $this->options['value']);
        if (empty($valnames))
            return 0;

        // Get the values from the calculation definition.
        $values = array();
        foreach ($valnames as $val) {
            if (is_numeric($val))           // Normal numeric value
                $values[] = $val;
            elseif ($val == $this->name)    // Can't reference ourself
                continue;
            elseif (is_object($fields[$val])) { // Another field value
                $values[] = $fields[$val]->value;
            }
        }

        // If we have at least one value, continue to process them.
        // Note that the first value is accepted as-is; zero is valid there.
        if (!empty($values)) {
            $result = (float)$values[0];    // Convert to numeric
            $valcount = count($values);
            switch ($this->options['calc_type']) {
            case 'add':
                for ($i = 1; $i < $valcount; $i++) {
                    if (!is_numeric($values[$i])) continue;
                    $result += $values[$i];
                }
                break;
            case 'sub':
                for ($i = 1; $i < $valcount; $i++) {
                    if (!is_numeric($values[$i])) continue;
                    $result -= $values[$i];
                }
                break;
            case 'div':
                for ($i = 1; $i < $valcount; $i++) {
                    if (!is_numeric($values[$i]) || $values[$i] == 0)
                        continue;
                    $result /= $values[$i];
                }
                break;
            case 'mul':
                for ($i = 1; $i < $valcount; $i++) {
                    if (!is_numeric($values[$i])) continue;
                    $result *= $values[$i];
                }
                break;
            case 'mean':
                if ($valcount < 1) {
                    $result = 0;
                } else {
                    for ($i = 1; $i < $valcount; $i++) {
                        if (!is_numeric($values[$i])) continue;
                        $result += $values[$i];
                    }
                    $result /= $valcount;
                }
                break;
            }
            if (is_numeric($result)) {
                // Result really can't be non-numeric here, but make sure it
                // is anyway before numeric formatting.
                $format_str = empty($this->options['format']) ?
                            $_CONF_FRM['def_calc_format'] :
                            $this->options['format'];
                $result = sprintf($format_str, $result);
            }
        }

        $this->value = $result;
        $this->value_text = $result;
        return $result;
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
        //if ($this->required != 1) return $msg;    // only checking required

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

        $value = $def;      // by default
        if (isset($dev[0]) && $def[0] == '$') {
            // Look for something like "$_USER:fullname"
            list($var, $valname) = explode(':', $def);
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
    *   and time fields
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
            $ampm_fld = '&nbsp;&nbsp;' .
                COM_getAmPmFormSelection($this->name . '_ampm', $ampm_sel);
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
    public function AutoGen($A, $type, $uid = 0)
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
    protected function _elemID($val = '')
    {
        $name  = str_replace(' ', '', $this->name);
        $id = 'forms_' . $this->frm_id . '_' . $name;
        if (!empty($val)) {
            $id .= '_' . str_replace(' ', '', $val);
        }
        return $id;
    }

}

?>
