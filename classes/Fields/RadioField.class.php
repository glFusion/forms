<?php
/**
 * Class to handle radio button form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2021 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.5.0
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
        static $T = NULL;

        if (!$this->canViewField()) {
            return NULL;
        }
        $values = FRM_getOpts($this->options['values']);
        if (!is_array($values)) {
            // Have to have some values for multiple checkboxes
            return NULL;
        }
        $this->value = $this->renderValue($res_id, $mode);

        if ($T === NULL) {
            $T = new \Template(__DIR__ . '/../../templates/fields/');
            $T->set_file('field', 'radio.thtml');
        }

        $attributes = array(
            'name' => $this->getName(),
        );
        $attributes = array_merge($attributes, $this->renderJS($mode));
        $attributes = array_merge($attributes, $this->renderAccess());

        $elem_id = $this->_elemID();

        $T->set_block('field', 'Options', 'opts');
        foreach ($values as $id=>$value) {
            $attributes['id'] = $elem_id . '_' . $value;
            if ($this->value == $value) {
                $attributes['checked'] = 'checked';
            } else {
                unset($attributes['checked']);
            }
            $T->set_block('field', 'Attr', 'attribs');
            foreach ($attributes as $attr_name=>$attr_value) {
                $T->set_var(array(
                    'name' => $attr_name,
                    'value' => $attr_value,
                ) );
                $T->parse('attribs', 'Attr', true);
            }
            $T->set_var('value', $value);
            $T->parse('opts', 'Options', true);
            $T->clear_var('attribs');
        }
        $T->parse('output', 'field');
        return $T->finish($T->get_var('output'));
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
