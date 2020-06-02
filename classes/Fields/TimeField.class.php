<?php
/**
 * Class to handle time-entry form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2020 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;


/**
 * Time-entry field type.
 */
class TimeField extends \Forms\Field
{

    /**
     * Get the value when submitted from a form.
     * Assembles the form fields into a single time value.
     *
     * @param   array   $vals   Array of all form fields
     * @return  string          Time value
     */
    public function valueFromForm($vals)
    {
        $hour = isset($vals[$this->getName().'_hour']) ?
            (int)$vals[$this->getName().'_hour'] : 0;
        $minute = isset($vals[$this->getName().'_minute']) ?
            (int)$vals[$this->getName().'_minute'] : 0;
        $second = isset($vals[$this->getName().'_second']) ?
            (int)$vals[$this->getName().'_second'] : 0;
        $ampm = isset($vals[$this->getName().'_ampm']) ?
            $vals[$this->getName().'_ampm'] : 'am';
        if ($this->getOption('timeformat') == '12') {
            list($hour, $ampm) = $this->hour24to12($hour);
            $this->value_text = sprintf('%02d:%02d %s', $hour, $minute, $ampm);
        } else {
            $this->value_text = sprintf('%02d:%02d', $hour, $minute);
        }
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }


    /**
     * Gets the string to set into the "value" field.
     *
     * @param   string  $val    String time value
     * @return  string          Time value in 12 or 24-hour format
     */
    public function setValue($val)
    {
        $value = trim($val);
        $A = explode(':', $value);
        $hour = $A[0];
        $min = isset($A[1]) ? $A[1] : '00';
        $sec = isset($A[2]) ? $A[2] : '00'; // just used for the SQL value
        if ($this->getOption('timeformat') == '12') {
            list($hour, $ampm) = $this->hour24to12($hour);
            $this->value_text = sprintf('%02d:%02d %s', $hour, $min, $ampm);
        } else {
            $this->value_text = sprintf('%02d:%02d', $hour, $min);
        }
        return $value;
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
        if (!$this->canViewField()) {
            return NULL;
        } else {
            return $this->TimeField($this->value);
        }
    }


    /**
     * Validate the submitted field value(s).
     *
     * @param  array   $vals  All form values
     * @return string      Empty string for success, or error message
     */
    public function Validate(&$vals)
    {
        global $LANG_FORMS;

        $msg = '';
        if (
            !$this->isEnabled() ||
            !$this->checkAccess(FRM_FIELD_REQUIRED)
        ) {
            return $msg;        // not required
        }
        if (
            empty($vals[$this->getName() . '_hour']) ||
            empty($vals[$this->getName() . '_minute'])
        ) {
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
        // Call the parent for default options
        $options = parent::optsFromForm($A);
        // Add options specific to this field type
        $options['timeformat'] = $A['timeformat'] == '24' ? '24' : '12';
        return $options;
    }


    public static function to24hour($parts, $var)
    {
        if (isset($parts[$var . '_ampm']) && $parts[$var . '_ampm'] == 'pm') {
            $parts[$var . '_hour'] += 12;
        }
        return $parts[$var . '_hour'] . ':' . $parts[$var . '_minute'];
    }

}

?>
