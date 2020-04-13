<?php
/**
 * Class to handle radio button form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.3.1
 * @since       v0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;


/**
 * Radio button class.
 * @package forms
 */
class RadioField extends \Forms\Field
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
        $values = FRM_getOpts($this->options['values']);
        if (!is_array($values)) {
            // Have to have some values for multiple checkboxes
            return NULL;
        }
        $this->value = $this->renderValue($res_id, $mode);
        $elem_id = $this->_elemID();
        $js = $this->renderJS($mode);
        $access = $this->renderAccess();
        $fld = '';
        foreach ($values as $id=>$value) {
            $sel = $this->value == $value ? 'checked="checked"' : '';
            $fld .= '<input ' . $access  . ' type="radio" name="' . $this->getName().
                    '"id="' . $elem_id . '_' . $value .
                    '" value="' . $value . '" ' . $sel . $js .
                    '>&nbsp;' . $value . '&nbsp;' . LB;
        }
        return $fld;
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
        // Add in options specific to this field type
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
        return $options;
    }

}

?>
