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
namespace Forms;

/**
*   Class for form fields
*/
class Field_static extends Field
{

    /**
    *   Render the input field.
    *   Static fields just display the default text.
    *
    *   @param  integer $res_id     Result set ID (not used)
    *   @param  string  $mode       Mode, e.g. "preview" (not used)
    *   @return string      Defined static field text
    */
    public function displayField($res_id = 0, $mode=NULL)
    {
        if (!$this->canViewField()) return NULL;
        return $this->GetDefault($this->options['default']);
    }


    /**
    *   Get the formatted value for display
    *
    *   @param  array   $fields     Array of all field objects
    *   @return string      Defined static field value
    */
    public function displayValue($fields)
    {
        if (!$this->canViewResults()) return NULL;
        return $this->GetDefault($this->options['default']);
    }


    /*
    *   Get the value for this field when read from the DB.
    *   Static fields always use the "default" option and have no actual value.
    *
    *   @param  array   $A  Array of all DB fields
    *   @return string      Static field value
    */
    public function valueFromDB($A)
    {
        if (isset($this->options['defvalue'])) {
            return $this->options['defvalue'];
        } else {
            return '';
        }
    }


    /**
    *   Get the field options when submitted from a form.
    *
    *   @param  array   $A  Array of all form fields
    *   @return array       Array of options for this field
    */
    public function optsFromForm($A)
    {
        // Call parent to get the default options
        $options = parent::optsFromForm($A);
        // Override the default, this uses the valuetext field
        $options['default'] = trim($A['valuetext']);
        return $options;
    }


    /**
    *   Return the XML element for privacy export.
    *   Static fields do not need to be exported.
    *
    *   @return string  Empty string
    */
    public function XML()
    {
        return '';
    }

}

?>
