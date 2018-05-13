<?php
/**
*   English language file for the Forms plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$LANG_FORMS = array(
'menu_title' => 'Forms',
'admin_text' => 'Create and edit custom form fields',
'admin_title' => 'Forms Administration',
'frm_id' => 'Form ID',
'add_form' => 'New Form',
'add_field' => 'New Field',
'list_forms' => 'List Forms',
'list_fields' => 'List Fields',
'action'    => 'Action',
'yes'       => 'Yes',
'no'        => 'No',
'undefined' => 'Undefined',
//'submit'    => 'Submit',
//'cancel'    => 'Cancel',
'reset'     => 'Reset Form',
'reset_fld_perm' => 'Reset Field Permissions',
'save_to_db'    => 'Save to Database',
'email_owner'   => 'Email Owner',
'email_group'   => 'Email Group',
'email_admin'   => 'Email Site Admin',
'onsubmit'      => 'Action upon Submission',
'preview'       => 'Preview Form',
'formsubmission'    => 'New Form Submission: %s',
'introtext'     => 'Intro Text',
'def_submit_msg' => 'Thank you for your submission',
'submit_msg'    => 'Message after Submission',
'noaccess_msg'  => 'Message shown if user can\'t access the form',
'noedit_msg'    => 'Message if user can\'t resubmit the form',
'max_submit_msg' => 'Message if the max submissions is reached',
'moderate'      => 'Moderate',
'results_group' => 'Group with access to results',
'new_frm_saved_msg' => 'Form saved. Now scroll down to add fields',
'help_msg' => 'Help Text',
'hlp_edit_form' => 'Create or edit an existing form. When creating a form, the form must be saved before fields can be added to it.',
'hlp_fld_order' => 'The Order indicates where the item will appear on forms relative to other items, and may be changed later.',
'hlp_fld_value' => 'The Value has several meanings, depending upon the Field Type:<br><ul><li>For Text fields, this is the default value used in new entries</li><li>For Checkboxes, this is the value returned when the box is checked (usually 1).</li><li>For Radio Buttons and Dropdowns, this is a string of value:prompt pairs to be used. This is in the format "value1:prompt1;value2:prompt2;..."</li></ul>',
'hlp_fld_mask' => 'Enter the data mask (e.g. "99999-9999" for a US zip code).',
'hlp_fld_enter_format' => 'Enter the format string',
'hlp_fld_enter_default' => 'Enter the default value for this field',
'hlp_fld_def_option' => 'Enter the option name to be used for the default',
'hlp_fld_def_option_name' => 'Enter the default option name',
'hlp_fld_chkbox_default' => 'Check this box if it should be checked by default.',
'hlp_fld_default_date' => 'Default date ("YYYY-MM-DD hh:mm", 24-hour format)',
'hlp_fld_default_time' => 'Default time ("hh:mm", 24-hour format)',
'hlp_fld_autogen'   => 'Select whether the data for this field is automatically created.',

'hdr_form_preview' => 'This is a fully functional preview of how your form will look. If you fill out the data fields and click "Submit", the data will be saved and/or emailed to the form\'s owner. If the post-submit action is to display the results, or a redirect url is configured for the form, you will be taken to that page.',
'hdr_field_edit' => 'Editing the field definition.',
'hdr_form_list'  => 'Select a form to edit, or create a new form by clicking "New Form" above. Other available actions are shown in the selection under the "Action" column.',
'hdr_field_list' => 'Select a field to edit, or create a new field by clicking "New Field" above.',
'hdr_form_results' => 'Here are the results for your form. You can delete them by clicking on the checkbox and selecting "delete"',

'form_results' => 'Form Results',
'del_selected' => 'Delete Selected',
'export'    => 'Export CSV',
'submitter' => 'Submitter',
'submitted' => 'Submitted',
'orderby'   => 'Order',
'name'      => 'Name',
'type'      => 'Type',
'enabled'   => 'Enabled',
'required'  => 'Required',
'hidden'    => 'Hidden',
'normal'    => 'Normal',
'spancols'  => 'Span all columns',
'user_reg'  => 'Registration',
'readonly'  => 'Read-Only',
'select'    => 'Select',
'move'      => 'Move',
'rmfld'     => 'Remove from Form',
'killfld'   => 'Remove and Delete Data',
'usermenu'  => 'View Members',

//'description'   => 'Description',
'textprompt'    => 'Text Prompt',
'fieldname'     => 'Field Name',
'formname'      => 'Form Name',
'fieldtype'     => 'Field Type',
'inputlen'      => 'Input Field Length',
'maxlen'        => 'Maximum Entry Length',
'columns'       => 'Columns',
'rows'          => 'Rows',
'value'         => 'Value',
'defvalue'      => 'Default Value',
'showtime'      => 'Show Time',
'hourformat'    => '12 or 24 hour format',
'hour12'        => '12-hour',
'hour24'        => '24-hour',
'format'        => 'Format',
'input_format'  => 'Input Format',
'month'         => 'Month',
'day'           => 'Day',
'year'          => 'Year',
'autogen'       => 'Auto-Generate',
'mask'          => 'Field Mask',
'stripmask'     => 'Strip Mask Characters from Value',
//'ent_registration' => 'Enter at Registration',
'pos_after'     => 'Position After',
'nochange'      => 'No Change',
'first'         => 'First',
'permissions'   => 'Permissions',
'group'         => 'Group',
'owner'         => 'Owner',
'admin_group'   => 'Admin Group',
'user_group'    => 'Group that can fill out',
'results_group' => 'Group that can see results',
'redirect'  => 'Redirect URL after submission',
'fieldset1' => 'Additional Form Settings',
'entered_by' => 'Submitter',
'datetime'  => 'Date/Time',
'is_required' => 'cannot be empty',
'frm_invalid' => 'The form contains missing or invalid fields.',
'add_century'   => 'Add century if missing',
'err_name_required' => 'Error: The form\'s name cannot be empty',
'confirm_form_delete' => 'Are you sure you want to delete this form?  All associated fields and data will be removed.',
'confirm_delete' => 'Are you sure you want to delete this item?',
'fld_access'    => 'Field Access',

'fld_types' => array(
    'text' => 'Text', 
    'textarea' => 'TextArea',
    'numeric'   => 'Numeric',
    'checkbox' => 'Checkbox',
    'multicheck' => 'Multi-Checkboxes',
    'select' => 'Dropdown',
    'radio' => 'Radio Buttons',
    'date' => 'Date',
    'time'  => 'Time',
    'static' => 'Static',
    'calc'  => 'Calculation',
    'hidden'  => 'Hidden',
),
'calc_type' => 'Calculation Type',
'calc_types' => array(
    'add' => 'Addition',
    'sub' => 'Subtraction',
    'mul' => 'Multiplication',
    'div' => 'Division',
    'mean' => 'Average',
),
'submissions'   => 'Submissions',
'frm_url'       => 'Form URL',
'req_captcha' => 'Require CAPTCHA',
'inblock' => 'Show in a block?',
'preview_on_save' => 'Display results',
'ip_addr'       => 'IP Address',
'view_html'     => 'View HTML',
'never'     => 'Never',
'when_fill' => 'When filling out the form',
'when_save' => 'When saving the form',
'max_submit' => 'Maximum Total Submissions',
'onetime'  => 'Per-User Submission Limit',
'pul_once' => 'One entry, No Edit',
'pul_edit' => 'One entry, Edit allowed',
'pul_mult' => 'Multiple Entries',
'other_emails' => 'Other email addresses (separate with ";")',
'instance' => 'Instance',
'showing_instance' => 'Showing results for instance &quot;%s&quot;',
'clear_instance' => 'Show all',
'datepicker' => 'Click for a date picker',
'print' => 'Print',
'toggle_success' => 'Item has been updated.',
'toggle_failure' => 'Error updating item.',
'edit_result_header' => 'Editing the submission by %1$s (%2$d) from %3$s at %4$s',
'form_type' => 'Form Type',
'regular' => 'Regular Form',
'field_updated' => 'Field updated',
'save_disabled' => 'Saving disabled in form preview.',
);

$PLG_forms_MESSAGE1 = 'Thank you for your submission.';
$PLG_forms_MESSAGE2 = 'The form contains missing or invalid fields.';
$PLG_forms_MESSAGE3 = 'The form has been updated.';
$PLG_forms_MESSAGE4 = 'Error updating the Forms plugin version.';
$PLG_forms_MESSAGE5 = 'A database error occurred. Check your site\'s error.log';
$PLG_forms_MESSAGE6 = 'Your form has been created. You may now create fields.';
$PLG_forms_MESSAGE7 = 'Sorry, the maximum number of submissions has been reached.';

/** Language strings for the plugin configuration section */
$LANG_configsections['forms'] = array(
    'label' => 'Forms',
    'title' => 'Forms Configuration'
);

$LANG_configsubgroups['forms'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_fs['forms'] = array(
    'fs_main' => 'General Settings',
    'fs_flddef' => 'Default Field Settings',
);

$LANG_confignames['forms'] = array(
    'displayblocks'  => 'Display glFusion Blocks',
    'default_permissions' => 'Default Permissions',
    'defgroup' => 'Default Group',
    'fill_gid'  => 'Default group to fill forms',
    'results_gid' => 'Default group to view results',

    'def_text_size' => 'Default Text Field Size',
    'def_text_maxlen' => 'Default "maxlen" for Text Fields',
    'def_textarea_rows' => 'Default textarea "rows" value',
    'def_textarea_cols' => 'Default textarea "cols" value',
    'def_date_format'   => 'Default date format string',
    'def_calc_format'   => 'Default format for calculated fields',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['forms'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    2 => array('As Submitted' => 'submitorder', 'By Votes' => 'voteorder'),
    3 => array('Yes' => 1, 'No' => 0),
    6 => array('Normal' => 'normal', 'Blocks' => 'blocks'),
    9 => array('Never' => 0, 'If Submission Queue' => 1, 'Always' => 2),
    10 => array('Never' => 0, 'Always' => 1, 'Accepted' => 2, 'Rejected' => 3),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('None' => 0, 'Left' => 1, 'Right' => 2, 'Both' => 3),
);


?>
