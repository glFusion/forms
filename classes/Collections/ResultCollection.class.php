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
use Forms\Result;


/**
 * Class to display the product catalog.
 * @package forms
 */
class ResultCollection extends Collection
{

    public function __construct()
    {
        global $_TABLES, $_CONF;

        parent::__construct();

        $this->_qb->select('res.*')
              ->from($_TABLES['forms_results'], 'res');
    }


    /**
     * Set the result set IDs to limit searching.
     *
     * @param   array   $res_ids    Resultset record IDs
     * @return  object  $this
     */
    public function withResultIds(array $res_ids) : self
    {
        $this->_qb->andWhere('res.res_id IN :res_ids')
                  ->setParameter('res_ids', $res_ids, Database::PARAM_INT_ARRAY);
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
        $this->_qb->andWhere('res.frm_id = :frm_id')
                  ->setParameter('frm_id', $frm_id, Database::STRING);
        return $this;
    }


    /**
     * Set the submitter user id to limit results.
     *
     * @param   integer $uid    Submitter user ID
     * @return  object  $this
     */
    public function withUserId(int $uid) : self
    {
        $this->_qb->andWhere('res.uid = (:uid)')
                  ->setParameter('uid', $uid, Database::INTEGER);
        return $this;
    }


    /**
     * Set the token value to limit results.
     *
     * @param   string  $token  Token string
     * @return  object  $this
     */
    public function withToken(string $token) : self
    {
        $this->_qb->andWhere('res.token = (:token)')
                  ->setParameter('token', $token, Database::STRING);
        return $this;
    }


    /**
     * Add a field and direction for sorting results.
     *
     * @param   string  $fld    Field name
     * @param   string  $dir    Direction
     * @return  object  $this
     */
    public function withOrderBy(string $fld, string $dir='ASC') : self
    {
        $dir = strtoupper($dir);
        if ($dir != 'ASC') {
            $dir = 'DESC';
        }
        $fld = $tihs->_db->conn->quoteIdentifier($fld);
        $this->qb->addOrderBy($fld, $dir);
        return $this;
    }


    /**
     * Get an array of Result objects.
     *
     * @return  array   Array of Result objects
     */
    public function getObjects() : array
    {
        $Results = array();
        $rows = $this->getRows();
        foreach ($rows as $row) {
            $Results[$row['res_id']] = Result::fromArray($row);
        }
        return $Results;
    }

}
