<?php
/**
 * Class to handle multi-line textarea form fields.
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
 * Textarea field type.
 */
class TextareaField extends \Forms\Field
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

        if (!$this->canViewField()) {
            return NULL;
        }

        if ($T === NULL) {
            $T = new \Template(__DIR__ . '/../../templates/fields');
            $T->set_file('field', 'textarea.thtml');
        }
        $this->value = $this->renderValue($res_id, $mode);
        $attributes = array(
            'name' => $this->getName(),
            'id' => $this->_elemID(),
            'rows' => $this->getOption('rows', 5),
            'cols' => $this->getOption('cols', 80),
        );
        $attributes = array_merge($attributes, $this->renderAccess());
        $attributes = array_merge($attributes, $this->renderJS($mode));

        $T->set_block('field', 'Attr', 'attribs');
        foreach ($attributes as $name=>$value) {
            $T->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $T->parse('attribs', 'Attr', true);
        }
        $T->set_var('text_value', $this->value);
        $T->parse('output', 'field');
        $T->clear_var('attribs');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Get the formatted field to display in the results.
     *
     * @param   array   $fields     Array of all field objects (not used)
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  string              Formatted field for display
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        return nl2br($this->value);
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
        $options['cols'] = $A['cols'] > 0 ? (int)$A['cols'] : $_CONF_FRM['def_textarea_cols'];
        $options['rows'] = $A['rows'] > 0 ? (int)$A['rows'] : $_CONF_FRM['def_textarea_rows'];
        return $options;
    }
 
}
