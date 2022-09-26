<?php
/**
 * Class to manage form categories.
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
namespace Forms;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Class for form categories.
 * Each form belongs to one category.
 * @package forms
 */
class Category
{
    private static $tag = 'forms_cat';

    /** Category ID.
     * @var integer */
    private $cat_id = 0;

    /** Category Name.
     * @var string */
    private $cat_name = '';

    /** Default user to receive form submissions.
     * @var integer */
    private $cat_email_uid = 0;

    /** Default user group to receive form submissions.
     * @var integer */
    private $cat_email_gid = 0;


    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   integer|array   $id Record ID or array
     */
    public function __construct(?array $vars=NULL)
    {
        global $_USER;

        if (is_array($vars)) {
            $this->setVars($vars, true);
        }
    }


    /**
     * Sets all variables to the matching values from the supplied array.
     *
     * @param   array   $row    Array of values, from DB or $_POST
     * @param   boolean $fromDB True if read from DB, false if from a form
     */
    public function setVars(array $row) : self
    {
        $this->cat_id = (int)$row['cat_id'];
        $this->cat_name = $row['cat_name'];
        $this->cat_email_uid = $row['cat_email_uid'];
        $this->cat_email_gid = $row['cat_email_gid'];
        return $this;
    }


    /**
     * Read a specific record and populate the local values.
     * Caches the object for later use.
     *
     * @param   integer $id Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure
     */
    public function Read(int $id = 0) : bool
    {
        global $_TABLES;

        if ($id == 0) $id = $this->cat_id;
        if ($id == 0) {
            $this->error = 'Invalid ID in Read()';
            return false;
        }

        try {
            $row = Database::getInstance()->conn->executeQuery(
                "SELECT * FROM {$_TABLES['forms_cats']} WHERE cat_id = ?",
                array($id),
                array(Database::INTEGER)
            )->fetchAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $row = false;
        }
        if (is_array($row)) {
            $this->setVars($row);
            Cache::set(self::_makeCacheKey($id), $this, 'forms_cats');
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get a category instance.
     * Checks cache first and creates a new object if not found.
     *
     * @param   integer $cat_id     Category ID
     * @return  object              Category object
     */
    public static function getInstance(int $cat_id) : self
    {
        static $cats = array();
        if (!isset($cats[$cat_id])) {
            $key = self::_makeCacheKey($cat_id);
            $cats[$cat_id] = Cache::get($key);
            if (!$cats[$cat_id]) {
                $cats[$cat_id] = new self;
                $cats[$cat_id]->Read($cat_id);
            }
        }
        return $cats[$cat_id];
    }


    /**
     * Determine if this category is a new record, or one that was not found.
     *
     * @return  boolean     True if new, False if existing
     */
    public function isNew() : bool
    {
        return $this->cat_id == 0;
    }


    /**
     * Save the current values to the database.
     *
     * @param  array   $A      Optional array of values from $_POST
     * @return boolean         True if no errors, False otherwise
     */
    public function save(?array $A = NULL) : bool
    {
        global $_TABLES, $_FORMS_CONF;

        if (is_array($A)) {
            $this->setVars($A);
        }

        $db = Database::getInstance();
        $values = array(
            'cat_name' => $this->cat_name,
            'cat_email_uid' => $this->cat_email_uid,
            'cat_email_gid' => $this->cat_email_gid,
        );
        $types = array(
            Database::STRING,
            Database::INTEGER,
            Database::INTEGER,
        );

        try {
            if ($this->isNew()) {
                $db->conn->insert(
                    $_TABLES['forms_cats'],
                    $values,
                    $types
                );
                $this->cat_id = $db->conn->lastInsertId();
            } else {
                $types[] = Database::INTEGER;   // for cat_id
                $db->conn->update(
                    $_TABLES['forms_cats'],
                    $values,
                    array('cat_id' => $this->cat_id),
                    $types
                );
            }
            Cache::clear('forms_cats');
            $retval = true;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $retval = false;
        }
        return $retval;
    }


    /**
     *  Delete the current category record from the database.
     */
    public function Delete() : bool
    {
        global $_TABLES, $_FORMS_CONF;

        if ($this->cat_id <= 1) {
            return false;
        }

        try {
            Database::getInstance()->conn->delete(
                $_TABLES['forms_cats'],
                array('cat_id' => $this->cat_id),
                array(Database::INTEGER)
            );
            Cache::clear('forms_cats');
            $retval = true;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $retval = false;
        }
        return $retval;
    }


    /**
     * Creates the edit form.
     *
     * @return  string      HTML for edit form
     */
    public function edit() : string
    {
        global $_TABLES, $_CONF, $_FORMS_CONF, $LANG_FORMS, $_SYSTEM;

        $retval = '';
        $T = new \Template(FRM_PI_PATH . '/templates/admin');
        $T->set_file('category', 'editcategory.thtml');

        $T->set_var(array(
            //'action_url'    => FRM_ADMIN_URL,
            //'pi_url'        => FRM_URL,
            'cat_id'        => $this->cat_id,
            'cat_name'      => $this->cat_name,
            'email_gid_options' => COM_optionList($_TABLES['groups'], 'grp_id,grp_name', $this->cat_email_gid, 1),
            'email_uid_options' => COM_optionList($_TABLES['users'], 'uid,fullname', $this->cat_email_uid, 1),
        ) );
        if ($this->cat_id > 1) {
            $T->set_var('can_delete', 'true');
        }
        $retval .= $T->parse('output', 'category');
        $retval .= COM_endBlock();
        return $retval;
    }


    /**
     * Determine if a category is used by any products.
     * Used to prevent deletion of a category if it would orphan a product.
     *
     * @return  boolean     True if used, False if not
     */
    public function isUsed() : bool
    {
        global $_TABLES;

        try {
            $frm_count = Database::getInstance()->getCount(
                $_TABLES['forms_frmdef'],
                'cat_id',
                $this->cat_id,
                Database::INTEGER
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $frm_count = 0;
        }
        return (int)$frm_count > 0;
    }


    /**
     * Add an error message to the Errors array.
     * Also could be used to log certain errors or perform other actions.
     *
     * @param   string  $msg    Error message to append
     */
    public function AddError($msg)
    {
        $this->Errors[] = $msg;
    }


    /**
     *  Create a formatted display-ready version of the error messages.
     *
     *  @return string      Formatted error messages.
     */
    public function PrintErrors()
    {
        $retval = '';

        foreach($this->Errors as $key=>$msg) {
            $retval .= "<li>$msg</li>\n";
        }
        return $retval;
    }


    /**
     * Helper function to create the cache key.
     *
     * @param   string  $id     Unique cache ID
     * @return  string  Cache key
     */
    private static function _makeCacheKey($id)
    {
        return self::$tag . $id;
    }


    /**
     * Category Admin List View.
     *
     * @param   integer $cat_id     Optional category ID to limit listing
     * @return  string      HTML for the category list.
     */
    public static function adminList($cat_id=0)
    {
        global $_CONF, $_FORMS_CONF, $_TABLES, $LANG_FORMS, $_USER, $LANG_ADMIN, $LANG_FORMS_HELP;

        $display = '';
        $sql = "SELECT * FROM {$_TABLES['forms_cats']}";

        $header_arr = array(
            array(
                'text'  => 'ID',
                'field' => 'cat_id',
                'sort'  => true,
            ),
            array(
                'text'  => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => $LANG_FORMS['cat_name'],
                'field' => 'cat_name',
                'sort'  => true,
            ),
            array(
                'text'  => $LANG_FORMS['email_uid'],
                'field' => 'cat_email_uid',
                'sort'  => true,
            ),
            array(
                'text'  => $LANG_FORMS['email_gid'],
                'field' => 'cat_email_gid',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_ADMIN['delete'], // . '&nbsp;' .
                /*FieldList::info(array(
                    'title' => $LANG_FORMS['del_cat_instr'],
                ) ),*/
                'field' => 'delete', 'sort' => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array(
            'field' => 'cat_id',
            'direction' => 'asc',
        );

        $display .= COM_startBlock('', '', COM_getBlockTemplate('_admin_block', 'header'));
        $display .= FieldList::buttonLink(array(
            'text' => $LANG_FORMS['new_item'],
            'url' => FRM_ADMIN_URL . '/index.php?editcat=0',
            'style' => 'success',
        ) );

        $query_arr = array(
            'table' => 'forms_cats',
            'sql' => $sql,
            'query_fields' => array('cat_name'),
            'default_filter' => 'WHERE 1=1',
        );

        $text_arr = array(
            'has_extras' => true,
            'form_url' => FRM_ADMIN_URL . '/index.php?categories=x',
        );

        $display .= ADMIN_list(
            'forms_catlist',
            array(__CLASS__,  'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            '', '', '', ''
        );
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $display;
    }


    /**
     * Get an individual field for the category list.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @return  string              HTML for field display in the table
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_FORMS_CONF, $LANG_FORMS, $_TABLES, $LANG_ADMIN;

        $retval = '';
        static $grp_names = array();

        switch($fieldname) {
        case 'edit':
            $retval .= FieldList::edit(array(
                'url' => FRM_ADMIN_URL . "/index.php?editcat={$A['cat_id']}",
            ) );
            break;

        case 'cat_email_gid':
            $fieldvalue = (int)$fieldvalue;
            if ($fieldvalue > 0) {
                if (!isset($grp_names[$fieldvalue])) {
                    $grp_names[$fieldvalue] = DB_getItem(
                        $_TABLES['groups'],
                        'grp_name',
                        "grp_id = $fieldvalue"
                    );
                }
                $retval = $grp_names[$fieldvalue];
            } else {
                $retval = '--' . $LANG_FORMS['none'] . '--';
            }
            break;

        case 'cat_email_uid':
            $fieldvalue = (int)$fieldvalue;
            if ($fieldvalue > 0) {
                if (!isset($user_names[$fieldvalue])) {
                    $user_names[$fieldvalue] = DB_getItem(
                        $_TABLES['users'],
                        'fullname',
                        "uid = $fieldvalue"
                    );
                }
                $retval = $user_names[$fieldvalue];
            } else {
                $retval = '--' . $LANG_FORMS['none'] . '--';
            }
            break;

        case 'delete':
            //if (!self::isUsed($A['cat_id'])) {
            if ($A['cat_id'] > 1) {
                $retval .= FieldList::delete(array(
                    'delete_url' => FRM_ADMIN_URL. '/index.php?deletecat=' . $A['cat_id'],
                    'attr' => array(
                        'onclick' => "return confirm('{$LANG_FORMS['confirm_delete']}');",
                    ),
                 ));
            }
            break;

        case 'cat_name':
        default:
            $retval = htmlspecialchars($fieldvalue, ENT_QUOTES, COM_getEncodingt());
            break;
        }
        return $retval;
    }


    /**
     * Load all categories from the database into an array.
     *
     * @return  array       Array of category objects
     */
    public static function getAll() : array
    {
        global $_TABLES;


        $cache_key = 'forms_all_cats';
        $retval = Cache::get($cache_key);
        if ($retval !== NULL) {
            return $retval;
        }

        $retval = array();
        try {
            $data = Database::getInstance()->conn->executeQuery(
                "SELECT * FROM {$_TABLES['forms_cats']}"
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = false;
        }
        if (is_array($data)) {
            foreach ($data as $row) {
                $retval[$row['cat_id']] = new self($row);
            }
        }
        return $retval;
    }


    /**
     * Get the record ID for a category.
     *
     * @return  integer     Category DB record ID
     */
    public function getID() : int
    {
        return $this->cat_id;
    }


    /**
     * Set the name, allows external functions to override.
     *
     * @param   string  $name   Category Name
     * @return  object  $this
     */
    public function withName(string $name) : self
    {
        $this->cat_name = $name;
        return $this;
    }


    /**
     * Get the category name.
     *
     * @return  string  Category name
     */
    public function getName() : string
    {
        return $this->cat_name;
    }


    /**
     * Get the email user ID.
     *
     * @return  integer     User ID
     */
    public function getEmailUid() : int
    {
        return (int)$this->cat_email_uid;
    }


    /**
     * Get the email group ID.
     *
     * @return  integer     Group ID
     */
    public function getEmailGid() : int
    {
        return (int)$this->cat_email_gid;
    }


    public static function optionList(int $selected) : string
    {
        global $_TABLES;

        return COM_optionList($_TABLES['forms_cats'], 'cat_id,cat_name', $selected, 1);
    }

}
