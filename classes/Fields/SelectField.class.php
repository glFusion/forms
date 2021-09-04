<?php
/**
 * Class to handle dropdown form fields.
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
 * Dropdown form field class.
 * @package forms
 */
class SelectField extends \Forms\Field
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
        $values = FRM_getOpts($this->options['values']);
        if (empty($values)) return '';

        $attributes = array(
            'id' => $this->_elemID(),
            'name' => $this->getName(),
        );
        $attributes = array_merge($attributes, $this->renderJS($mode));
        $attributes = array_merge($attributes, $this->renderAccess());

        $this->value = $this->renderValue($res_id, $mode);
        $options = array();
        foreach ($values as $id=>$value) {
            $options[$value] = array(
                'value' => $value,
                'selected' => $this->value == $value,
            );
        }
        $attributes['options'] = $options;
        return FieldList::select($attributes);
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
