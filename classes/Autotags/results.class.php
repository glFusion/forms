<?php
/**
 * Class to show a results table via autotag.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020-2022 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.6.0
 * @since       v0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms\Autotags;
use Forms\Form;
use Forms\Result;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Autotag class to show form results.
 * @package forms
 */
class results
{
    /**
     * Handle the parsing of the autotag.
     *
     * @param   string  $p1         First option after the tag name
     * @param   string  $opts       Name=>Vaue array of other options
     * @param   string  $fulltag    Full autotag string
     * @return  string      Replacement HTML, if applicable.
     */
    public function parse(string $p1, array $opts=array(), string $fulltag='') : string
    {
        global $_CONF, $_TABLES, $_USER, $_GROUPS;

        $retval = '';
        $frm_id = '';
        $fieldlist = false;
        $sortfields = array('dt', 'ip', 'username', 'fullname', 'uid');
        $sortby = 'dt';
        $sortdir = 'ASC';

        foreach ($opts as $key=>$val) {
            $val = strtolower($val);
            switch ($key) {
            case 'form':
            case 'id':
                $frm_id = $val;
                break;
            case 'fields':
                $fieldlist = explode(',', $val);
                break;
            case 'sortby':
                if (in_array($val, $sortfields)) {
                    $sortby = $val;
                }
                break;
            case 'sortdir':
                $sortdir = strtoupper(substr($val, 3));
                if ($sortdir != 'ASC') {
                    $sortdir = 'DESC';
                }
                break;
            }
        }

        if (empty($frm_id)) {
            return $retval;
        }
        $fieldnames = array();

        // Instantiate the form to verify View Results access
        $Frm = new Form($frm_id, FRM_ACCESS_VIEW);

        // Return nothing if the form is invalid (e.g. no access)
        if ($Frm->isNew() || !$Frm->hasAccess(FRM_ACCESS_VIEW)) {
            return $retval;
        }

        // Get the form results. We've already verified this user's access to
        // the form by instantiating it.
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('r.*', 'u.username', 'u.fullname')
           ->from($_TABLES['forms_results'], 'r')
           ->leftJoin('r', $_TABLES['users'], 'u', 'u.uid = r.uid')
           ->where('r.frm_id = :frm_id')
           ->andWhere('r.approved = 1')
           ->orderBy($sortby, $sortdir)
           ->setParameter('frm_id', $frm_id, Database::STRING);
        if (!empty($instance_id)) {
            $qb->andWhere('r.instance_id = :instance_id')
               ->setParameter('instance_id', $instance_id, Database::STRING);
        }
        try {
            $data = $qb->execute()->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = NULL;
        }
        if (empty($data)) {
            return '';
        }

        $T = new \Template(FRM_PI_PATH . '/templates');
        $isAdmin = plugin_isadmin_forms();
        if (is_array($fieldlist)) {
            $fieldnames = $fieldlist;
        }

        $T->set_file('formresults', 'results.thtml');
        $T->set_var(array(
            'frm_id'    => $Frm->getID(),
            'frm_name'  => $Frm->getName(),
            'isAdmin'   => $isAdmin,
        ) );

        // Create the table headers
        $T->set_block('formresults', 'Headers', 'header');

        // Go through the fields and unset any that shouldn't be shown in the
        // results table, based on type and permissions
        $Fields = array();      // store fields to be shown
        foreach ($Frm->getFields() as $fldname=>$Fld) {
            $show_field = true;     // assume it will be shown

            if (!$Fld->isEnabled() || $Fld->getType() == 'static') {
                $show_field = false;
            } elseif (!empty($fieldnames) && !in_array($Fld->getName(), $fieldnames)) {
                // If we have a field list, and this isn't in it, block it.
                $show_field = false;
            } elseif (
                $Fld->getResultsGid() != $Frm->getResultsGid() &&
                !in_array($Fld->getResultsGid() , $_GROUPS)
            ) {
                // If the user doesn't have permission to see this result, block it.
                // The form's permission has already been checked.
                $show_field = false;
            }

            // If field is ok to show, then set it's header.  Otherwise, unset it
            // which will also remove it from the loop that follows.
            if ($show_field) {
                $Fields[$Fld->getID()] = $Fld;
                $T->set_var(
                    'fld_name',
                    $Fld->getPrompt() == '' ? $Fld->getName() : $Fld->getPrompt()
                );
                $T->parse('header', 'Headers', true);
            }
        }

        // Create each data row
        $T->set_block('formresults', 'DataRows', 'dataRow');
        foreach ($data as $A) {
            $R = new Result($A);
            $R->GetValues($Frm->getFields());

            // Admins always see the submitter & date, others only if requested
            if ($isAdmin) {
                $T->set_var('res_id', $R->getID());
            }
            if ($isAdmin || $fieldlist == 'all' || in_array('res_user', $fieldnames)) {
                $T->set_var('res_user', COM_getDisplayName($R->getUid()));
            }
            if ($isAdmin || $fieldlist == 'all' || in_array('res_date', $fieldnames)) {
                $T->set_var('res_date', strftime('%Y-%m-%d %H:%M', $R->getTimestamp()));
            }

            $T->set_block('formresults', 'Fields', 'fldData');
            foreach ($Fields as $Fld) {
                //$Fld->GetValue($R->getResID());
                //$T->set_var('fld_value', htmlspecialchars($Fld->value_text));
                $T->set_var('fld_value', $Fld->displayValue($Frm->getFields()));
                $T->parse('fldData', 'Fields', true);
            }
            $T->parse('dataRow', 'DataRows', true);
            $T->clear_var('fldData');
        }
        $T->parse('output', 'formresults');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

}

