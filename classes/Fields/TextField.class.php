<?php
/**
 * Class to handle text form fields.
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
 * Basic text input fields.
 * @package forms
 */
class TextField extends \Forms\Field
{
    private $T = NULL;

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

        if (!$this->canViewField()) {
            return NULL;
        }

        if ($this->T === NULL) {
            $this->T = new \Template(__DIR__ . '/../../templates/fields/');
            $this->T->set_file('field', 'text.thtml');
        }

        $attributes = array(
            'name' => $this->getName(),
            'value' => $this->renderValue($res_id, $mode),
            'id' => $this->_elemID(),
            'size' => $this->options['size'],
            'maxlength' => min($this->options['maxlength'], 255),
        );
        $attributes = array_merge($attributes, $this->renderAccess());
        $attributes = array_merge($attributes, $this->renderJS($mode));

        $this->T->set_block('field', 'Attr', 'attribs');
        foreach ($attributes as $name=>$value) {
            $this->T->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $this->T->parse('attribs', 'Attr', true);
        }
        $this->T->parse('output', 'field');
        $this->T->clear_var('attribs');
        return $this->T->finish($this->T->get_var('output'));
    }


    /**
     * Get the field options when the definition form is submitted.
     *
     * @param   array   $A  Array of all form fields
     * @return  array       Array of options for this field type
     */
    protected function optsFromForm($A)
    {
        // Call the parent function to get default options
        $options = parent::optsFromForm($A);
        // Add in options specific to this field type
        $options['size'] = (int)$A['size'];
        $options['maxlength'] = (int)$A['maxlength'];
        $options['autogen'] = isset($A['autogen']) ?
            (int)$A['autogen'] :
            FRM_AUTOGEN_NONE;
        return $options;
    }

}
