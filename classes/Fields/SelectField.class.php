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
        global $LANG_FORMS;

        static $T = NULL;
        $values = FRM_getOpts($this->options['values']);
        if (empty($values)) return '';

        if ($T === NULL) {
            $T = new \Template(__DIR__ . '/../../templates/fields/');
            $T->set_file('field', 'select.thtml');
        }

        $attributes = array(
            'id' => $this->_elemID(),
            'name' => $this->getName(),
        );
        $attributes = array_merge($attributes, $this->renderJS($mode));
        $attributes = array_merge($attributes, $this->renderAccess());

        /*$elem_id = $this->_elemID();
        $js = $this->renderJS($mode);
        $access = $this->renderAccess();*/
        $this->value = $this->renderValue($res_id, $mode);
        $T->set_block('field', 'Attr', 'attribs');
        foreach ($attributes as $name=>$value) {
            $T->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $T->parse('attribs', 'Attr', true);
        }
        $options = "<option value=\"\">{$LANG_FORMS['select']}</option>\n";
        foreach ($values as $id=>$value) {
            $sel = $this->value == $value ? 'selected="selected"' : '';
            $options .= "<option value=\"$value\" $sel>{$value}</option>" . LB;
        }
        $T->set_var('option_list', $options);
        $T->parse('output', 'field');
        $T->clear_var('attribs');
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
