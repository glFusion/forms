<?php
/**
 * Class to handle numeric input fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2021 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.5.0
 * @since       0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;

/**
 * Numeric field class.
 * Leverages the text field type but ensures that all saved values are numeric.
 */
class NumericField extends TextField
{

    /**
     * Gets the value to be set into the "value" field.
     * Makes sure this is a numeric value.
     *
     * @param   float   $value  Value to be set
     * @return  float           Sanitized value
     */
    public function setValue($value)
    {
        return (float)$value;
    }


    /**
     * Get the formatted value for display in the results
     *
     * @param   array   $fields     Array of all field objects (not used)
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  string              Formatted numeric display
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        $fmt = $this->getOption('format', '%f');
        return sprintf($fmt, $this->value);
    }


    /**
     * Sanitize this value before saving to the DB during form submission.
     *
     * @param   float   $value  Value, should be numeric
     * @return  float           Sanitized numeric value
     */
    protected function _prepareForDB($value)
    {
        return (float)$value;
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
        // Add options specific to this field type
        $options['format'] = empty($A['format']) ?
                    $_CONF_FRM['def_calc_format'] : $A['format'];
        return $options;
    }
 
}
