<?php
/**
 * Class to define values submitted for form fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.6.0
 * @since       v0.6.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Models;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Class for product view type.
 * @package forms
 */
class Value implements \ArrayAccess
{
    /** Information properties.
     * @var array */
    private $properties = array(
        'val_id' => 0,
        'res_id' => 0,
        'frm_id' => 0,
        'value' => '',
    );


    /**
     * Initialize the properties from a supplied string or array.
     *
     * @param   array   $val    Optonal initial properties
     */
    public function __construct(?array $val=NULL)
    {
        if (is_array($val)) {
            $this->properties = array_merge($this->properties, $val);
        }
    }


    /**
     * Save a value to the database.
     *
     * @return  boolean     True on success, False on error
     */
    public function save() : bool
    {
        global $_TABLES;

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->setParameter('res_id', $this->properties['res_id'], Database::INTEGER)
           ->setParameter('fld_id', $this->properties['fld_id'], Database::INTEGER)
           ->setParameter('value', $this->properties['value'], Database::STRING);
        $status = true;
        try {
            $qb->insert($_TABLES['forms_values'])
               ->setValue('results_id', ':res_id')
               ->setValue('fld_id', ':fld_id')
               ->setValue('value', ':value')
               ->execute();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $k) {
            try {
                $qb->update($_TABLES['forms_values'])
                   ->set('value', ':value')
                   ->where('results_id = :res_id')
                   ->andWhere('fld_id = :fld_id')
                   ->execute();
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $status = false;
            }
        }
        return $status;
    }


    /**
     * Set a property when accessing as an array.
     *
     * @param   string  $key    Property name
     * @param   mixed   $value  Property value
     */
    public function offsetSet($key, $value)
    {
        $this->properties[$key] = $value;
    }


    /**
     * Check if a property is set when calling `isset($this)`.
     *
     * @param   string  $key    Property name
     * @return  boolean     True if property exists, False if not
     */
    public function offsetExists($key)
    {
        return isset($this->properties[$key]);
    }


    /**
     * Remove a property when using unset().
     *
     * @param   string  $key    Property name
     */
    public function offsetUnset($key)
    {
        unset($this->properties[$key]);
    }


    /**
     * Get a property when referencing the class as an array.
     *
     * @param   string  $key    Property name
     * @return  mixed       Property value, NULL if not set
     */
    public function offsetGet($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : NULL;
    }


    /**
     * Check if the properties array is empty.
     *
     * @return  boolean     True if empty, False if not
     */
    public function isEmpty() : bool
    {
        return empty($this->properties);
    }


    /**
     * Merge the supplied array into the internal properties.
     *
     * @param   array   $arr    Array of data to add
     * @return  object  $this
     */
    public function merge(array $arr) : self
    {
        $this->properties = array_merge($this->properties, $arr);
        return $this;
    }


    /**
     * Get the internal properties as a native array.
     *
     * @return  array   $this->properties
     */
    public function toArray() : array
    {
        return $this->properties;
    }


    /**
     * Encode the internal properties to a Base64-encode JSON string.
     *
     * @return  string      Encoded string containing the properties
     */
    public function encode() : string
    {
        return base64_encode(json_encode($this->properties));
    }


    /**
     * Sets the internal properties by decoding the supplied string.
     *
     * @param   string  $data   Base64-encoded JSON string
     * @return  array       Properties array
     */
    public function decode(string $data) : array
    {
        $this->properties = json_decode(base64_decode($data), true);
        return $this->properties;
    }


    /**
     * Return the string representation of the class.
     *
     * @return  string      JSON string of properties
     */
    public function __toString() : string
    {
        return json_encode($this->properties);
    }
}
