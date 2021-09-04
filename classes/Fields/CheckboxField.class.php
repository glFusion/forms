<?php
/**
 * Class to handle single checkbox form fields.
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
use Forms\FieldList;


/**
 * Checkbox field handler.
 */
class CheckboxField extends \Forms\Field
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
        global $_CONF, $LANG_FORMS, $_CONF_FRM;

        static $T = NULL;
        if ($T === NULL) {
            $T = new \Template(__DIR__ . '/../../templates/fields/');
            $T->set_file('field', 'checkbox.thtml');
        }

        $this->value = $this->renderValue($res_id, $mode);
        $attributes = array(
            'name' => $this->getName(),
            'id' => $this->_elemID(),
            'checked' => $this->value == 1,
            'value' => 1,
        );
        $attributes = array_merge($attributes, $this->renderJS($mode));
        $attributes = array_merge($attributes, $this->renderAccess());

        return FieldList::checkbox($attributes);

        $T->set_block('field', 'Attr', 'attribs');
        foreach ($attributes as $name=>$value) {
            $T->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $T->parse('attribs', 'Attr', true);
        }
        $T->parse('output', 'field');
        $T->clear_var('attribs');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Get the value to set in the "value" field for this object.
     * Checkboxes are always either "0" or "1"
     *
     * @param   integer $value  Value to set
     * @return  integer         Sanitized value
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            if (isset($value[$this->getName()])) {
                $this->value = (int)$value[$this->getName()] == 0 ? 0 : 1;
            } else {
                $this->value = 0;
            }
        } else {
            $this->value = $value == 0 ? 0 : 1;
        }
        return $this;
    }


    /**
     * Get the value form a form.
     * Checkbox is unset for zero, set for the value.
     *
     * @param   array   $A      Array of submitted values
     * @return  integer     0 if not set, configured value if set
     */
    public function valueFromForm($A)
    {
        return isset($A[$this->fld_name]) ? (int)$A[$this->fld_name] : 0;
    }


    /**
     * Get the value to show in the CSV export of results.
     * For checkboxes, return "1" if checked and "0" if not.
     * The default behavior is to call displayValue().
     *
     * @return  mixed       Value to show in CSV export.
     */
    public function getValueForCSV($fields)
    {
        return $this->value;
    }


    /**
     * Get the formatted value for display in the results.
     *
     * @param   array   $fields     Array of all field objects
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  string              Language string corresponding to value
     */
    public function displayValue($fields, $chkaccess=true)
    {
        global $LANG_FORMS;

        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        return $this->value == 1 ? $LANG_FORMS['yes'] : $LANG_FORMS['no'];
    }


    /**
     * Sanitize this value to be saved to the database.
     *
     * @param   integer $value      Raw value
     * @return  integer             Sanitized value, either 0 or 1
     */
    protected function _prepareForDB($value)
    {
        return $value == 0 ? 0 : 1;
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
        $options['default'] = isset($A['defvalue']) && $A['defvalue'] == 1 ? 1 : 0;
        $options['value'] = 1;
        return $options;
    }

}
