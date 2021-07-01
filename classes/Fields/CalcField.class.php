<?php
/**
 * Calculated field class.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2018-2021 Lee Garner <lee@leegarner.com>
 * @package    forms
 * @version    0.5.0
 * @since      0.3.1
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;


/**
 * Class to handle calculated fields.
 * Calculated fields show a value based on the submitted content in other fields.
 */
class CalcField extends \Forms\Field
{

    /**
     * Create the input field for the form.
     * Calculated fields do not appear on the rendered form.
     *
     * @param   integer $res_id     Result set ID (not used)
     * @param   string  $mode       View mode (not used)
     * @return  null            Empty value to prevent display
     */
    public function displayField($res_id = 0, $mode = NULL)
    {
        return NULL;
    }


    /**
     * Calculate the result for this field based on other fields.
     * This is the same as the code found in GetValue(), except that all
     * the fields are provided to save time.
     *
     * @param   array   $fields     Array of field objects
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  float               Calculated value of this field
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        $result = '';

        // Get the calculation definition, return if none defined.
        $valnames = explode(',', $this->options['value']);
        if (empty($valnames))
            return 0;

        // Get the values from the calculation definition.
        $values = array();
        foreach ($valnames as $val) {
            if (is_numeric($val))           // Normal numeric value
                $values[] = $val;
            elseif ($val == $this->getName())    // Can't reference ourself
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
            $this->value = $result;
            if (is_numeric($result)) {
                // Result really can't be non-numeric here, but make sure it
                // is anyway before numeric formatting.
                $format_str = empty($this->options['format']) ?
                            $_CONF_FRM['def_calc_format'] :
                            $this->options['format'];
                $result = sprintf($format_str, $result);
            }
        }
        $this->value_text = $result;
        return $result;
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

        // Call the parent to get default options
        $options = parent::optsFromForm($A);
        // Get options specific to this field type
        $options['value'] = trim($A['valuestr']);
        $options['calc_type'] = $A['calc_type'];
        $options['format'] = empty($A['format']) ? $_CONF_FRM['def_calc_format'] : $A['format'];
        return $options;
    }

}
