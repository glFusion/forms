<?php
/**
*   Class to handle individual form fields.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2017 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms\Fields;

/**
*   Class for form fields
*/
class checkbox extends \Forms\Field
{

    /**
    *   Create a single form field for data entry.
    *
    *   @param  integer $res_id Results ID, zero for new form
    *   @param  string  $mode   Mode, e.g. "preview"
    *   @return string      HTML for this field, including prompt
    */
    public function displayField($res_id = 0, $mode = NULL)
    {
        global $_CONF, $LANG_FORMS, $_CONF_FRM;

        $this->value = $this->renderValue($res_id, $mode);
        $elem_id = $this->_elemID();
        $js = $this->renderJS($mode);
        $access = $this->renderAccess();

        $chk = $this->value == 1 ? 'checked="checked"' : '';
        $fld = "<input $access name=\"{$this->name}\"
                    id=\"$elem_id\" type=\"checkbox\" value=\"1\"
                    $chk $js />" . LB;
        return $fld;
    }


    /**
    *   Get the value to set in the "value" field for this object
    *   Checkboxes are always either "0" or "1"
    *
    *   @param  integer $value  Value to set
    *   @return integer         Sanitized value
    */
    public function setValue($value)
    {
        return $value == 0 ? 0 : 1;
    }


    /**
    *   Get the formatted value for display in the results
    *
    *   @param  array   $fields     Array of all field objects
    *   @return string              Language string corresponding to value
    */
    public function displayValue($fields)
    {
        global $LANG_FORMS;

        if (!$this->canViewResults()) return NULL;
        return $this->value == 1 ? $LANG_FORMS['yes'] : $LANG_FORMS['no'];
    }


    /**
    *   Sanitize this value to be saved to the database
    *
    *   @param  integer $value      Raw value
    *   @return integer             Sanitized value
    */
    protected function prepareForDB($value)
    {
        return $value == 0 ? 0 : 1;
    }


    /**
    *   Get the field options when the definition form is submitted.
    *
    *   @param  array   $A  Array of all form fields
    *   @return array       Array of options for this field type
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

?>
