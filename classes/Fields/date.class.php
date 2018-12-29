<?php
/**
 * Date field class.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.3.1
 * @since       0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;

/**
 * Class to handle date-entry form fields.
 */
class date extends \Forms\Field
{

    /**
     * Get the field value when submitted by a form.
     * Need to assemble the date from the three form fields.
     *
     * @param   array   $A      Array of all form fields
     * @return  string          Data value
     */
    public function valueFromForm($A)
    {
        $dt = array('0000', '00', '00');
        if (isset($A[$this->name . '_month'])) {
            $dt[1] = (int)$A[$this->name . '_month'];
        }
        if (isset($A[$this->name . '_day'])) {
            $dt[2] = (int)$A[$this->name . '_day'];
        }
        if (isset($A[$this->name . '_year'])) {
            $dt[0] = (int)$A[$this->name . '_year'];
        }
        if (isset($this->options['century']) && $this->options['century'] == 1 && $dt[0] < 100) {
            $dt[0] += 2000;
        }
        $tmpval = sprintf('%04d-%02d-%02d', $dt[0], $dt[1], $dt[2]);

        if (isset($this->options['showtime']) &&
                    $this->options['showtime'] == 1) {
            $hour = isset($A[$this->name . '_hour']) ?
                        (int)$A[$this->name . '_hour'] : 0;
            $minute = isset($A[$this->name . '_minute']) ?
                        (int)$A[$this->name . '_minute'] : 0;
            $tmpval .= sprintf(' %02d:%02d', $hour, $minute);
        }
        return $tmpval;
    }


    /**
     * Set a value into the `value` and `value_text` properties.
     *
     * @param   string  $val    Raw value to set
     * @return  string          Contents of `value` property
     */
    public function setValue($val)
    {
        $this->value = trim($val);
        $this->value_text = $this->DateDisplay();
        return $this->value;
    }


    /**
     * Create a single form field for data entry.
     *
     * @param   integer $res_id Results ID, zero for new form
     * @param   string  $mode   Mode, e.g. "preview"
     * @return  string      HTML for this field, including prompt
     */
    public function displayField($res_id = 0, $mode = NULL)
    {
        global $_CONF, $LANG_FORMS, $_CONF_FRM;

        if (!$this->canViewField()) return NULL;

        $elem_id = $this->_elemID();
        $access = $this->renderAccess();

        //  Create the field HTML based on the type of field.
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
                ": <select $access id=\"{$this->name}_month\" name=\"{$this->name}_month\">\n";
        $m_fld .= "<option value=\"0\">--{$LANG_FORMS['select']}--</option>\n";
        $m_fld .= COM_getMonthFormOptions($dt[1]) . "</select>\n";

        $d_fld = $LANG_FORMS['day'] .
                ": <select $access id=\"{$this->name}_day\" name=\"{$this->name}_day\">\n";
        $d_fld .= "<option value=\"0\">--{$LANG_FORMS['select']}--</option>\n";
        $d_fld .= COM_getDayFormOptions($dt[2]) . "</select>\n";

        $y_fld = $LANG_FORMS['year'] .
                ': <input ' . $access . ' type="text" id="' . $this->name.'_year" name="'.$this->name.'_year"
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
                    '_trigger" class="uk-icon uk-icon-calendar tooltip" ' .
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
        return $fld;
    }


    /**
     * Rudimentary date display function to mimic strftime().
     * Timestamps don't handle dates far in the past or future.  This function
     * does a str_replace using a subset of PHP's date variables.  Only the
     * numeric variables with leading zeroes are used.
     *
     * @param   array   $fields     Array of all fields (not used here)
     * @return  string  Date formatted for display
     */
    public function displayValue($fields = NULL)
    {
        if (!$this->canViewResults()) return NULL;
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
        return $retval;
    }


    /**
     * Validate the submitted field value(s)
     *
     * @param   array   $vals  All form values
     * @return  string      Empty string for success, or error message
     */
    public function Validate(&$vals)
    {
        global $LANG_FORMS;

        $msg = '';
        $valid = true;

        // If not enabled or not required, consider any value as OK
        if (!$this->enabled ||
            (($this->access & FRM_FIELD_REQUIRED) != FRM_FIELD_REQUIRED) ) {
            return $msg;
        }

        if (isset($vals[$this->name]) && !empty($vals[$this->name])) {
            $parts = explode('-', $vals[$this->name]);
            $y = $parts[0];
            $m = isset($parts[1]) ? $parts[1] : 0;
            $d = isset($parts[2]) ? $parts[2] : 0;
            $valid = checkdate($d, $m, $y);
        } elseif (empty($vals[$this->name . '_month']) ||
                empty($vals[$this->name . '_day']) ||
                empty($vals[$this->name . '_year'])) {
                $valid = false;
        }
        if (!valid) $msg = $this->prompt . ' ' . $LANG_FORMS['is_required'];
        return $msg;
    }


    /**
     * Get the field options when the definition form is submitted.
     *
     * @param   array   $A  Array of all form fields
     * @return  array       Array of options for this field type
     */
    protected function optsFromForm($A)
    {
        global $_CONF_FRM;

        // Call the parent function to get default options
        $options = parent::optsFromForm($A);
        // Add in options specific to this field type
        $options['showtime'] = isset($A['showtime']) && $A['showtime'] == 1 ? 1 : 0;
        $options['timeformat'] = $A['timeformat'] == '24' ? '24' : '12';
        $options['format'] = isset($A['format']) ? $A['format'] :
                        $_CONF_FRM['def_date_format'];
        $options['input_format'] = (int)$A['input_format'];
        $options['century'] = isset($A['century']) && $A['century'] == 1 ? 1 : 0;
        return $options;
    }

}

?>
