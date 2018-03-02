<?php
/**
*   Class to handle individual AJAX form fields from autotags
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
class Field_autotag
{
    private $type;
    private $name;
    private $value;

    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  integer $id     ID of the existing field, empty if new
    *   @param  object  $Form   Form object to which this field belongs
    */
    public function __construct($type, $name, $value=1)
    {
        $this->type = $type;
        $this->value = $value;
        $this->name = COM_sanitizeID($name);
    }


    /**
    *   Create a single form field for data entry.
    *
    *   @param  integer     Results ID, zero for new form
    *   @return string      HTML for this field, including prompt
    */
    public function Render($chk_default=false)
    {
        $elem_id = $this->_elemID();
        $value = $this->_getValue($elem_id);
        if ($value === NULL) {
            $chk = $chk_default ? 'checked="checked"' : '';
        } else {
            $chk = $value  == $this->value ? 'checked="checked"' : '';
        }

        $js = "onclick=\"FORMS_autotagSave('{$this->name}', this);\"";
        $fld = '';
        //  Create the field HTML based on the type of field.
        switch ($this->type) {
        case 'checkbox':
            $fld = "<input id=\"$elem_id\" type=\"checkbox\" value=\"1\" $chk $js />" . LB;
            break;
        case 'radio':
            $fld = "<input id=\"$elem_id\" type=\"radio\" value=\"{$this->value}\"
                    name=\"{$this->name}\" $chk $js />" . LB;
            break;
        }
        return $fld;
    }


    /**
    *   Save this field value to a session variable.
    *
    *   @param  mixed   $newval Data value to save
    *   @param  integer $res_id Result ID associated with this field
    *   @return boolean     True on success, False on failure
    */
    public function SaveData($newval)
    {
        switch ($this->type) {
        // Set the $newval for special cases
        case 'checkbox':
            $newval = $newval == 1 ? 1 : 0;
            break;
        }
        SESS_setVar($this->_elemID(), $newval);
    }


    private function _elemID()
    {
        return 'forms_autotag_' . $this->type . '_' . $this->name;
    }


    private function _getValue($name)
    {
        return SESS_isSet($name) ? SESS_getVar($name) : NULL;
    }

}

?>
