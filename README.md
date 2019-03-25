# Forms plugin for glFusion
Form creation plugin for glFusion. Forms can be embedded in a page using an
autotag or displayed via the URL.

Submitted data can be saved or used in any combination of several ways:
* Saved to the database
* Emailed to the site administator
* Emailed to the form's owner
* Emailed to members of the form's designated group
* Emailed to any other email addresses
* Simply displayed in a new page

Forms can also use AJAX to save fields to session variables as they are changed.
The `onchange` Javascript function is used so the field must be changed
to record a value. Some field types (radio buttons, checkboxes, dropdowns) are
better suited to AJAX submission than others such as text fields, but all should work.

When using AJAX forms none of the usual form-action selections are available.

## Autotags
The Forms plugin supports sevaral autotags

### forms:show
Displays a form in the page, or the "no access" message if permission is denied.

Usage: `[forms:show form:<form_id>]`

Parameters:
* `form_id`: ID of the form to display. Required.

### forms:results
Displays the submitted form data in a table. Only fields for which the current
user has &quot;View Results&quot; access are displayed.

Usage: `[forms:results form:<form_id> field1,field2,field3,...]`

Parameters:
* `form_id`: ID of the form to display. Required.
* field1, etc.: Specific field names to display. If any fields are specified,
only those fields are displayed. If no fields are specified then all fields are shown.

### forms:checkbox|radio
Displays a single form field, either a checkbox or a radio button. Data is
submitted via AJAX and saved to the PHP session variable. No database or
email options are available.

Usage:
* `[forms:checkbox name:<fld_name> check]`
* `[forms:radio name:<fld_name> value:<fld_value> check]`

Parameters:
* `fld_name`: A name for this field or radio button group. Required.
This should normally be globally unique but may be duplicated between
pages if you wish the pages to show the same data.
* `fld_value`: The field value. Required for radio buttons, not used for checkboxes.
* `check`: Indicates that the field should be checked initially. Once the guest has
checked or unchecked the item this has no effect.
