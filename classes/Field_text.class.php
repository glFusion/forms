<?php
/**
*   Class to handle individual form fields.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @since      0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;

/**
*   Class for form fields
*/
class Field_text extends Field
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

        if (!$this->canViewField()) return NULL;
        $value = $this->renderValue($res_id, $mode);
        $elem_id = $this->_elemID();
        $js = $this->renderJS($mode);
        $access = $this->renderAccess();
        $size = $this->options['size'];
        $maxlength = min($this->options['maxlength'], 255);

        $fld = "<input $access name=\"{$this->name}\"
                    id=\"$elem_id\"
                    size=\"$size\" maxlength=\"$maxlength\"
                    type=\"text\" value=\"{$value}\" $js />";
        return $fld;
    }


    /**
    *   Get the field options when the definition form is submitted.
    *
    *   @param  array   $A  Array of all form fields
    *   @return array       Array of options for this field type
    */
    public function optsFromForm($A)
    {
        // Call the parent function to get default options
        $options = parent::optsFromForm($A);
        // Add in options specific to this field type
        $options['size'] = (int)$A['size'];
        $options['maxlength'] = (int)$A['maxlength'];
        $options['autogen'] = isset($A['autogen']) ? (int)$A['autogen'] :
                        FRM_AUTOGEN_NONE;
        return $options;
    }
 
}

?>
