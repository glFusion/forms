<?php
/**
 * Class to handle static text form fields.
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
 * Static Text Fields.
 * There is no input for these fields, they are simply displayed.
 */
class StaticField extends \Forms\Field
{

    /**
     * Render the input field.
     * Static fields just display the default text.
     *
     * @param   integer $res_id     Result set ID (not used)
     * @param   string  $mode       Mode, e.g. "preview" (not used)
     * @return  string      Defined static field text
     */
    public function displayField($res_id = 0, $mode=NULL)
    {
        if (!$this->canViewField()) return NULL;
        return $this->GetDefault($this->options['default']);
    }


    /**
     * Get the formatted value for display.
     *
     * @param   array   $fields     Array of all field objects
     * @return  string      Defined static field value
     */
    public function displayValue($fields, $chkaccess=true)
    {
        if ($chkaccess && !$this->canViewResults()) {
            return NULL;
        }
        return $this->GetDefault($this->options['default']);
    }


    /**
     * Return the XML element for privacy export.
     * Static fields do not need to be exported.
     *
     * @return  string  Empty string
     */
    public function XML()
    {
        return '';
    }


    /**
     * A static text field value is always the defined default value.
     *
     * @param   mixed   $vals   Value to set (unused)
     * @return  object  $this
     */
    public function setValue($vals)
    {
        $this->value = $this->options['default'];
        return $this;
    }


    /**
     * Save this field to the database.
     *
     * @param   mixed   $newval Data value to save
     * @param   integer $res_id Result ID associated with this field
     * @return  boolean     True on success, False on failure
     */
    public function SaveData($newval, int $res_id) : bool
    {
        return true;
    }

}
