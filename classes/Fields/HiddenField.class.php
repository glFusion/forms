<?php
/**
 * Class to handle hideen form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 20182021 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.5.0
 * @since       0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;


/**
 * Handles hidden form fields.
 * @package forms
 */
class HiddenField extends \Forms\Field
{
    /**
     * Get the display prompt to show on the form.
     * Normally just the prompt string, but for hidden fields use nothing.
     *
     * @return  string      Prompt to show on entry form
     */
    public function displayPrompt()
    {
        return '';
    }


    /**
     * Create the hidden input field.
     * No access check is done, all users are assumed to have access
     * if a hidden field is defined.
     *
     * @param   integer $res_id     Results ID, zero for new form
     * @param   string  $mode       View mode, e.g. "preview"
     * @return  string      HTML for this field, including prompt
     */
    public function displayField($res_id = 0, $mode = NULL)
    {
        global $_CONF, $LANG_FORMS, $_CONF_FRM;

        $elem_id = $this->_elemID();
        $fld = '<input type="hidden" name="' . $this->getName() .
                    '" value="' . $this->value .
                    '" id="' . $elem_id . '"/>';
       return $fld;
    }


    /**
     * Override displayValue() to prevent any display for hidden fields.
     *
     * @param   array   $fields     Array of field objects (not used)
     * @param   boolean $chkaccess  True to check user access, False to skip
     * @return  null                No return for hidden fields.
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($this->canViewResults()) {
            return $this->value;
        } else {
            return NULL;
        }
    }

}
