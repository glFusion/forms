<?php
/**
 * Class to handle field collections.
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
use Forms\Field;


/**
 * Class to display the product catalog.
 * @package forms
 */
class FieldCollection extends Collection
{

    /**
     * Fields are almost always retrieved relative to a form.
     * The form ID can be included here for convenience. Otherwise withFormId()
     * can be called to set the form ID.
     *
     * @param   string  $frm_id     Optional form ID
     */
    public function __construct(?string $frm_id=NULL)
    {
        global $_TABLES, $_CONF;

        parent::__construct();

        $this->_qb->select('fld.*')
                  ->from($_TABLES['forms_flddef'], 'fld');
        if (!empty($frm_id)) {
            $this->withFormId($frm_id);
        }
    }


    /**
     * Set the field record IDs to limit searching.
     *
     * @param   array   $fld_ids    Field record IDs
     * @return  object  $this
     */
    public function withFieldIds(array $fld_ids) : self
    {
        $this->_qb->andWhere('fld.fld_id IN :fld_ids')
                  ->setParameter('fld_ids', $fld_ids, Database::PARAM_INT_ARRAY);
        return $this;
    }


    /**
     * Set the brand ID to limit results.
     *
     * @param   string  $frm_id     Form ID to limit results
     * @return  object  $this
     */
    public function withFormId(string $frm_id) : self
    {
        $this->_qb->andWhere('fld.frm_id = :frm_id')
                  ->setParameter('frm_id', $frm_id, Database::STRING);
        return $this;
    }


    /**
     * Get an array of Result objects.
     *
     * @return  array   Array of Result objects
     */
    public function getObjects() : array
    {
        $Field = array();
        $rows = $this->getRows();
        foreach ($rows as $row) {
            $Field[$row['res_id']] = new Field($row);
        }
        return $Field;
    }

}
