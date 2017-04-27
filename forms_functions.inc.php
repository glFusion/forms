<?php
/**
*   Plugin-specific functions for the forms plugin
*   Load by calling USES_forms_functions()
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2011 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.2.3
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;

/**
*   Display the results for a given form in tabular format.
*
*   @param  string  $frm_id     ID of form to display
*   @param  mixed   $fieldlist  Normal user- array of field names, false for all
*                               Admin user- true, always gets all fields
*   @param  string  $instance_id    Specific instance ID, e.g. story ID
*   @return string              HTML for form results table
*/
function FRM_ResultsTable($frm_id, $fieldlist=false, $instance_id = '')
{
    global $_TABLES, $_USER, $_GROUPS;

    USES_forms_class_form();
    USES_forms_class_result();
    USES_forms_class_field();

    //$grp_list = implode(',', $_GROUPS);
    //$perm_sql = " AND (f.owner_id='". (int)$_USER['uid'] . "'
    //    OR f.results_gid IN ($grp_list) )";
    $retval = '';
    $fields = array();

    // Instantiate the form to verify View Results access
    $Frm = new frmForm($frm_id, FRM_ACCESS_VIEW);

    // Return nothing if the form is invalid (e.g. no access)
    if ($Frm->id == '' || !$Frm->access)
        return '';

    // Get the form results.  We've already verified this user's access to
    // the form by instantiating it.
    /*$sql = "SELECT r.* FROM {$_TABLES['forms_results']} r
            LEFT JOIN {$_TABLES['forms_frmdef']} f
            ON f.id = r.frm_id
            WHERE frm_id='$frm_id'
            $perm_sql
            ORDER BY dt ASC";*/
    $sql = "SELECT * FROM {$_TABLES['forms_results']} 
            WHERE frm_id='$frm_id'";
    if (!empty($instance_id)) {
        $sql .= " AND instance_id = '" . DB_escapeString($instance_id) . "'";
    }
    $sql .= ' ORDER BY dt ASC';
    //echo $sql;die;
    $res = DB_query($sql, 1);
    if (DB_numRows($res) < 1)
        return '';          // Nothing to show

    $R = new frmResult();
    if ($fieldlist === true) {
        $T = new \Template(FRM_PI_PATH . '/templates/admin');
        $isAdmin = true;
    } else {
        $T = new \Template(FRM_PI_PATH . '/templates');
        $isAdmin = false;
        if (is_array($fieldlist)) {
            $fields = $fieldlist;
        }
    }

    $T->set_file('formresults', 'results.thtml');
    $T->set_var(array(
        'frm_id'    => $Frm->id,
        'frm_name'  => $Frm->name,
        'isAdmin'   => $isAdmin,
    ) );

    // Create the table headers
    $T->set_block('formresults', 'Headers', 'header');

    // Go through the fields and unset any that shouldn't be shown in the
    // results table, based on type and permissions
    foreach ($Frm->fields as $fldname=>$Fld) {
        $show_field = true;     // assume it will be shown

        if (!$Fld->enabled || $Fld->type == 'static') {
            $show_field = false;
        } elseif (!empty($fields) && !in_array($Fld->name, $fields)) {
            // If we have a field list, and this isn't in it, block it.
            $show_field = false;
        } elseif ($Fld->results_gid != $Frm->results_gid &&
                !in_array($Fld->results_gid, $_GROUPS)) {
            // If the user doesn't have permission to see this result, block it.
            // The form's permission has already been checked.
            $show_field = false;
        }

        // If field is ok to show, then set it's header.  Otherwise, unset it
        // which will also remove it from the loop that follows.
        if ($show_field) {
            $T->set_var('fld_name', 
                    $Fld->prompt == '' ? $Fld->name : $Fld->prompt);
            $T->parse('header', 'Headers', true);
        } else {
            unset($Frm->fields[$fldname]);
        }
    }

    // Create each data row
    $T->set_block('formresults', 'DataRows', 'dataRow');
    while ($A = DB_fetchArray($res, false)) {
        $R->Read($A['id']);
        // Admins always see the submitter & date, others only if requested
        if ($isAdmin) {
            $T->set_var('res_id', $R->id);
        }
        if ($isAdmin || $fieldlist == 'all' || in_array('res_user', $fields)) {
            $T->set_var('res_user', COM_getDisplayName($R->uid));
        }
        if ($isAdmin || $fieldlist == 'all' || in_array('res_date', $fields)) {
            $T->set_var('res_date', strftime('%Y-%m-%d %H:%M', $R->dt));
        }

        $T->set_block('formresults', 'Fields', 'fldData');
        foreach ($Frm->fields as $Fld) {
            $Fld->GetValue($R->id);
            $T->set_var('fld_value', htmlspecialchars($Fld->value_text));
            $T->parse('fldData', 'Fields', true);
        }

        $T->parse('dataRow', 'DataRows', true);
        $T->clear_var('fldData');
    }
    $T->parse('output', 'formresults');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;

}


/**
*   Create the fValidator class string for input fields.  If no options
*   are supplied, then the fValidator is empty.
*
*   @deprecated
*   @param  array   $opts   Options to include ('required', 'email', etc).
*   @param  array   $data   All field data, to get the mask
*   @return string          String for 'class="fValidate[] iMask...'
*/
function FRM_make_fValidator($opts, $data)
{
    $retval = 'class="fValidate[';

    if (is_array($opts)) {
        $opt = "'" . join("','", $opts) . "'";
    }
    $retval .= $opt . ']';

    if (isset($data['options']['mask'])) {
        $stripmask = isset($data['options']['stripmask']) &&
                        $data['options']['stripmask'] == '1' ? 'true' : 'false';

        $retval .= " iMask\" alt=\"{
        type: 'fixed',
        mask: '{$data['options']['mask']}',
        stripMask: $stripmask }\"";
    } else {
        $retval .= '"';
    }

    return $retval;
}


/**
*   Convert a field mask (9999-AA-XX) to a visible mask (____-__-__)
*
*   @since  version 0.0.2
*   @param  string  $mask   Field mask
*   @return string          Field mask converted to visible mask
*/
function FRM_mask2vismask($mask)
{
    $old = array('9', 'X', 'A', 'a', 'x');
    $new = array('_', '_', '_', '_', '_');
    return str_replace($old, $new, $mask);
}


/**
*   Automatically generate a string value.
*
*   The site admin can effectively override this function by creating a
*   CUSTOM_forms_autogen() function which takes the field name & type
*   as arguments, or a CUSTOM_forms_autogen_{fieldname} function which
*   takes no arguments.  The second form takes precedence over the first.
*
*   All fields and values are passed to the auto-generation function so
*   it may use them in the creation of the new value.
*
*   @param  array   $A      Field definition and values
*   @param  integer $uid    Optional user ID.  Zero is acceptable here.
*   @return string          Value to give the field
*/
function FRM_autogen($A, $type, $uid=0)
{
    if (!is_array($A) || empty($A)) {
        return COM_makeSID();
    }
    if ($uid == 0) $uid = (int)$_USER['uid'];
    if ($type != 'fill') $type = 'save';

    $function = 'CUSTOM_forms_autogen_' . $type;
    if (function_exists($function . '_' . $var)) 
        $retval = $function . '_' . $var($this->properties, $uid);
    elseif (function_exists($function)) 
        $retval =  $function($this->properties, $uid);
    elseif (function_exists('CUSTOM_forms_autogen'))
        $retval = CUSTOM_forms_autogen($A, $uid);
    else
        $retval = COM_makeSID();

    return $retval;
}


function FRM_UserDropdown($sel=0, $users='', $not='')
{
    global $_TABLES;

    $sel = (int)$sel;

    $sql = "SELECT uid,username
            FROM {$_TABLES['users']} 
            WHERE uid <> 1 ";
    if ($users != '') {
        $not = $not == '' ? '' : 'NOT ';
        $sql .= "AND uid $not IN (" . 
                DB_escapeString($users). ") ";
    }
    $sql .= " ORDER BY username ASC";
    $result = DB_query($sql);
    if (!$result)
        return '';

    $retval = '';
    while ($row = DB_fetcharray($result)) {
        $selected = $row['uid'] == $sel ? ' selected' : '';
        $retval .= "<option value=\"{$row['uid']}\" $selected>{$row['username']}</option>\n";
    }
    return $retval;

}


function FRM_GroupDropdown($group_id, $access)
{
    global $_TABLES;

    $groupdd = '';

    if ($access == 3) {
        $usergroups = SEC_getUserGroups ();

        foreach ($usergroups as $ug_name => $ug_id) {
            $groupdd .= '<option value="' . $ug_id . '"';
            if ($group_id == $ug_id) {
                $groupdd .= ' selected="selected"';
            }
            $groupdd .= '>' . $ug_name . '</option>' . LB;
        }
    } else {
        // They can't set the group then
        $groupdd .= DB_getItem ($_TABLES['groups'], 'grp_name',
                                "grp_id = '".DB_escapeString($group_id)."'")
                 . '<input type="hidden" name="group_id" value="' . $group_id
                 . '" />';
    }

    return $groupdd;
}


/**
*   Show the site header, with or without left blocks according to config.
*
*   @see    COM_siteHeader()
*   @param  string  $subject    Text for page title (ad title, etc)
*   @param  string  $meta       Other meta info
*   @return string              HTML for site header
*/
function FRM_siteHeader($subject='', $meta='')
{
    global $_CONF_FRM, $LANG_FRM;

    $retval = '';

    switch($_CONF_FRM['displayblocks']) {
    case 2:     // right only
    case 0:     // none
        $retval .= COM_siteHeader('none', $subject, $meta);
        break;

    case 1:     // left only
    case 3:     // both
    default :
        $retval .= COM_siteHeader('menu', $subject, $meta);
        break;
    }

    return $retval;

}


/**
*   Show the site footer, with or without right blocks according to config.
*
*   @see    COM_siteFooter()
*   @return string              HTML for site header
*/
function FRM_siteFooter()
{
    global $_CONF_FRM, $_CONF;

    $retval = '';

    if ($_CONF['show_right_blocks']) {
        $retval .= COM_siteFooter(true);
        return $retval;
    }

    switch($_CONF_FRM['displayblocks']) {
    case 2 : // right only
    case 3 : // left and right
        $retval .= COM_siteFooter(true);
        break;

    case 0: // none
    case 1: // left only
    default :
        $retval .= COM_siteFooter();
        break;
    }

    return $retval;

}


?>
