<?php
/**
 * Date field class.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.6.0
 * @since       0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;
use Forms\Models\DataArray;
use Forms\Models\Request;


/**
 * Class to handle date-entry form fields.
 * @package forms
 */
class DateField extends \Forms\Field
{

    /**
     * Get the field value when submitted by a form.
     * Need to assemble the date from the three form fields.
     *
     * @param   array   $A      Array of all form fields
     * @return  string          Data value
     */
    public function valueFromForm(array $A) : string
    {
        $dt = array('0000', '00', '00');
        if (isset($A[$this->getName() . '_month'])) {
            $dt[1] = (int)$A[$this->getName() . '_month'];
        }
        if (isset($A[$this->getName() . '_day'])) {
            $dt[2] = (int)$A[$this->getName() . '_day'];
        }
        if (isset($A[$this->getName() . '_year'])) {
            $dt[0] = (int)$A[$this->getName() . '_year'];
        }
        if ($this->getOption('century') == 1 && $dt[0] < 100) {
            $dt[0] += 2000;
        }
        $tmpval = sprintf('%04d-%02d-%02d', $dt[0], $dt[1], $dt[2]);

        if ($this->getOption('showtime') == 1) {
            $hour = isset($A[$this->getName() . '_hour']) ?
                (int)$A[$this->getName() . '_hour'] : 0;
            $minute = isset($A[$this->getName() . '_minute']) ?
                (int)$A[$this->getName() . '_minute'] : 0;
            $tmpval .= sprintf(' %02d:%02d', $hour, $minute);
        }
        return $tmpval;
    }


    /**
     * Set a value into the `value` and `value_text` properties.
     *
     * @param   string  $val    Raw value to set
     * @return  object  $this
     */
    public function setValue($val)
    {
        if (is_array($val)) {
            $this->value = sprintf('%04d-%02d-%02d',
                $val[$this->getName() . '_year'],
                $val[$this->getName() . '_month'],
                $val[$this->getName() . '_day']
            );
            if ($this->options['showtime']) {
                $this->value .= ' ' . TimeField::to24hour($val, $this->getName());
            }
        } else {
            $this->value = trim($val);
        }
        $this->value_text = $this->displayValue(NULL, false);
        return $this;
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

        if (!$this->canViewField()) {
            return NULL;
        }

        $access = $this->renderAccess();
        $Request = Request::getInstance();

        //  Create the field HTML based on the type of field.
        $fld = '';
        $dt = array();
        // Check for POSTed values first, coming from a previous form
        // If one is set, all should be set, and empty values are ok
        if (isset($Request[$this->getName() . '_month'])) {
            $dt[1] = $Request->getString($this->getName() . '_month');
        }
        if (isset($Request[$this->getName() . '_day'])) {
            $dt[2] = $Request->getString($this->getName() . '_day');
        }
        if (isset($Request[$this->getName() . '_year'])) {
            $dt[0] = $Request->getString($this->getName() . '_year');
        }

        // Nothing from POST, check for an existing value.  If none,
        // use the default.
        $value = $this->value;
        if (empty($dt)) {
            if (
                empty($value) &&
                $this->getOption('default') != ''
            ) {
                $this->value = $this->getOption('default');
            } else {
                $this->value = '_-_-_';     // dummy to make empty selections
            }
            $datestr = explode(' ', $this->value);  // separate date & time
            $dt = explode('-', $datestr[0]);        // get date components
            $month = isset($dt[1]) ? $dt[1] : '';
            $day = isset($dt[2]) ? $dt[2] : '';
            $year = $dt[0];
        }

        $T = new \Template(FRM_PI_PATH . '/templates/fields');
        $T->set_file('dt', 'date.thtml');
        $T->set_var(array(
//            'access'    => $access,
            'varname'   => $this->getName(),
            'm_options' => COM_getMonthFormOptions($month),
            'd_options' => COM_getDayFormOptions($day),
            'mdy'       => $this->options['input_format'] == 1,
            'curdate'   => $this->value,
            'curyear'   => $year,
        ) );
        $T->parse('output', 'dt');
        $fld = $T->finish($T->get_var('output'));
        if ($this->options['showtime'] == 1 && isset($datestr[1])) {
            $fld .= ' ' . $this->TimeField($datestr[1]);
        }
        return $fld;
    }


    /**
     * Rudimentary date display function to mimic strftime().
     * Timestamps don't handle dates far in the past or future.  This function
     * does a str_replace using a subset of PHP's date variables.  Only the
     * numeric variables with leading zeroes are used.
     *
     * @param   array   $fields     Array of all fields (not used here)
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  string  Date formatted for display
     */
    public function displayValue($fields = NULL, $chkaccess = true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        $dt_tm = explode(' ', $this->value);
        if (strpos($dt_tm[0], '-')) {
            list($year, $month, $day) = explode('-', $dt_tm[0]);
        } else {
            $year = '0000';
            $month = '01';
            $day = '01';
        }
        $second = '00';
        if (isset($dt_tm[1]) && strpos($dt_tm[1], ':')) {
            list($hour, $minute) = explode(':', $dt_tm[1]);
        } else {
            $hour = '00';
            $minute = '00';
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
    public function Validate(DataArray &$vals) : string
    {
        global $LANG_FORMS;

        $msg = '';
        $valid = true;

        // If not enabled or not required, consider any value as OK
        if (
            !$this->enabled ||
            !$this->checkAccess(self::ACCESS_REQUIRED)
        ) {
            return $msg;
        }

        if (isset($vals[$this->getName()]) && !empty($vals[$this->getName()])) {
            $parts = explode('-', $vals[$this->getName()]);
            $y = $parts[0];
            $m = isset($parts[1]) ? $parts[1] : 0;
            $d = isset($parts[2]) ? $parts[2] : 0;
            $valid = checkdate($d, $m, $y);
        } elseif (empty($vals[$this->getName() . '_month']) ||
                empty($vals[$this->getName() . '_day']) ||
                empty($vals[$this->getName() . '_year'])) {
                $valid = false;
        }
        if (!$valid) {
            $msg = $this->getPrompt() . ' ' . $LANG_FORMS['is_required'];
        }
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
