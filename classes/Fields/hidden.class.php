<?php
/**
 * Class to handle hideen form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.3.1
 * @since       0.3.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Fields;

/**
 * Handles hidden form fields.
 */
class hidden extends \Forms\Field
{

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
        $fld = '<input type="hidden" name="' . $this->name .
                    '" value="' . $this->value .
                    '" id="' . $elem_id . '"/>';
       return $fld;
    }


    /**
     * Override displayValue() to prevent any display for hidden fields.
     *
     * @param   array   $fields     Array of field objects (not used)
     * @return  null                No return for hidden fields.
     */
    public function displayValue($fields)
    {
        return NULL;
    }

}

?>
