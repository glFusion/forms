<?php
/**
 * Class to handle resultset collections.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner
 * @package     forms
 * @version     v0.7.0
 * @since       v0.7.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Collections;
use glFusion\Database\Database;
use Forms\Value;


/**
 * Class to display the product catalog.
 * @package forms
 */
class ValueCollection extends Collection
{

    public function __construct()
    {
        global $_TABLES, $_CONF;

        parent::__construct();

        $this->_qb->select('val.*')
              ->from($_TABLES['forms_values'], 'val');
    }


    /**
     * Set the result set IDs to limit searching.
     *
     * @param   array   $val_ids    Value record IDs
     * @return  object  $this
     */
    public function withResultIds(array $val_ids) : self
    {
        $this->_qb->andWhere('val.results_id IN :res_ids')
                  ->setParameter('val_ids', $val_ids, Database::PARAM_INT_ARRAY);
        return $this;
    }


    /**
     * Get an array of Result objects.
     *
     * @return  array   Array of Result objects
     */
    public function getObjects() : array
    {
        $Values = array();
        $rows = $this->getRows();
        foreach ($rows as $row) {
            $Values[$row['val_id']] = Value::fromArray($row);
        }
        return $Values;
    }

}
