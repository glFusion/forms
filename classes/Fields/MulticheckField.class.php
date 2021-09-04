<?php
/**
 * Class to handle multi-checkbox form fields.
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
use Forms\FieldList;


/**
 * Handle multi-checkbox form fields.
 * @package forms
 */
class MulticheckField extends \Forms\Field
{
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
        }

        $values = $this->options['values'];
        if (!is_array($values) || empty($values)) {
            // Have to have some values for multiple checkboxes
            return NULL;
        }

        // Get the values. If the field has no value (no submission),
        // use the default. Otherwise use the value from the result.
        if ($this->value === NULL) {
            $checked = (array)$this->getOption('default');  // todo
        } elseif (is_array($this->value)) {
            $checked = $this->value;
        } else {
            $checked = array();
        }

        $attributes = $this->renderJS($mode);
        $attributes = array_merge($attributes, $this->renderAccess());
        $attributes['name'] = $this->getName() . '[]';
        $boxes = array();
        foreach ($values as $id=>$value) {
            $attributes['id'] = $this->_elemID($value);
            $attributes['checked'] = in_array($value, $checked);
            $attributes['value'] = $value;
            $boxes[] = FieldList::checkbox($attributes) . '&nbsp;' . $value;
        }
        return implode('&nbsp;', $boxes);
    }


    /**
     * Prepare the field data for the database
     * Multi-check data must be in an array, if not it is assumed to
     * be empty.
     *
     * @param   array   $newval     Array of field values
     * @return  string      Serialized string
     */
    protected function _prepareForDB($newval)
    {
        if (is_array($newval)) {
            $newval = serialize($newval);
        } else {
            $newval = serialize(array($newval));
        }
        return $newval;
    }


    /**
     * Override function to get the checked vs. unchecked value.
     *
     * @param   integer $res_id     Result set ID
     * @param   string  $mode       'preview' or not
     * @param   string  $valname    Value name to pass to _elemID()
     */
    protected function renderValue($res_id, $mode, $valname='')
    {
        $chk = false;
        if (isset($_POST[$this->getName()])) {
            // First, check for a POSTed value. The form is being redisplayed.
            $chk = true;
        } elseif ($this->getSubType() == 'ajax' && SESS_isSet($this->_elemID($valname))) {
            // Second, if this is an AJAX form check the session variable.
            $chk = SESS_getVar($this->_elemID($valname)) == 1 ? true : false;
        } else {
            if (is_array($this->value)) {
                $chk = in_array($valname, $this->value) ? true : false;
            }
        }
        return $chk;
    }


    /**
     * Get the array to set into the "value" field.
     * Expands the supplied string into an array, if necessary
     *
     * @param   mixed   $value  Value, either array or string
     * @return  array           Array of selections
     */
    public function setValue($value)
    {
        if (!is_array($value)) {
            $value = @unserialize($value);
            if ($value === false) {
                $value = array();
            }
        }
        $this->value = $value;
        return $this;
    }


    /**
     * Get the field options when the definition form is submitted.
     *
     * @param   array   $A  Array of all form fields
     * @return  array       Array of options for this field type
     */
    protected function optsFromForm($A)
    {
        // Call the parent to get default options
        $options = parent::optsFromForm($A);
        // Add options specific to this field type
        $newvals = array();
        foreach ($A['selvalues'] as $val) {
            if (!empty($val)) {
                $newvals[] = $val;
            }
        }
        $options['default'] = array();  // override, fill in below
        if (isset($A['sel_default'])) {
            foreach ($A['sel_default'] as $key) {
                if (isset($A['selvalues'][$key])) {
                    $options['default'][] = $A['selvalues'][$key];
                }
            }
        }
        $options['values'] = $newvals;
        return $options;
    }


    /**
     * Get the formatted string to display this value in the results.
     * Displays the selected options as "opt1, opt2, opt3..."
     *
     * @param   array   $fields     Array of all field objects (not used)
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  string              Formatted value for display
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        if (is_array($this->value)) {
            return implode(', ', $this->value);
        } else {
            return '';
        }
    }

}
