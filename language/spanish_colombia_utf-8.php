<?php
/**
*   Spanish Colombia language file for the Forms plugin.
*   @author     Lee Garner <lee@leegarner.com>
*   @translator John Toro <john.toro@newroute.net>
*   @copyright  Copyright (c) 2010 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$LANG_FORMS = array(
'menu_title' => 'Formas',
'admin_text' => 'Create and edit custom form fields',
'admin_title' => 'Formas - Administración',
'frm_id' => 'ID',
'add_form' => 'Crear',
'add_field' => 'Crear Campo',
'list_forms' => 'Formas',
'list_fields' => 'Campos',
'action'    => 'Acción',
'yes'       => 'Sí',
'no'        => 'No',
'undefined' => 'Indefinido',
//'submit'    => 'Submit',
//'cancel'    => 'Cancel',
'reset'     => 'Restablecer',
'reset_fld_perm' => 'Establecer Permisos de los Campos',
'save_to_db'    => 'Guardar en la Base de Datos',
'email_owner'   => 'Email Propietario',
'email_group'   => 'Email Grupo',
'email_admin'   => 'Email Administrador del Sitio',
'onsubmit'      => 'Acción después de Envío',
'preview'       => 'Previsualizar Forma',
'formsubmission'    => 'New Form Submission: %s',
'introtext'     => 'Introducción',
'def_submit_msg' => 'Gracias por tu envío',
'submit_msg'    => 'Mensaje después de Envío',
'noaccess_msg'  => 'Mensaje si el usuario no puede acceder a la forma',
'noedit_msg'    => 'Mensaje si el usuario no puede reenviar la forma',
'max_submit_msg' => 'Mensaje si alcanza el máximo de envíos',
'moderate'      => '¿Moderar',
'results_group' => 'Grupo con acceso a los resultados',
'new_frm_saved_msg' => 'Forma guardada. Ahora puedes bajar y agregar campos',
'help_msg' => 'Texto de Ayuda',
'hlp_edit_form' => 'Crea o modifica una forma existente. Cuando se crea una forma, esta se debe guardar primero para poder agregarle campos.',
'hlp_fld_order' => 'The Order indicates where the item will appear on forms relative to other items, and may be changed later.',
'hlp_fld_value' => 'The Value has several meanings, depending upon the Field Type:<br><ul><li>For Text fields, this is the default value used in new entries</li><li>For Checkboxes, this is the value returned when the box is checked (usually 1).</li><li>For Radio Buttons and Dropdowns, this is a string of value:prompt pairs to be used. This is in the format "value1:prompt1;value2:prompt2;..."</li></ul>',
'hlp_fld_mask' => 'Indica la mascara de datos (ejm. "99999-9999" para un código postal).',
'hlp_fld_enter_format' => 'Enter the format string',
'hlp_fld_enter_default' => 'Indica el valor predeterminado para este campo',
'hlp_fld_def_option' => 'Enter the option name to be used for the default',
'hlp_fld_def_option_name' => 'Enter the default option name',
'hlp_fld_chkbox_default' => 'Check this box if it should be checked by default.',
'hlp_fld_default_date' => 'Default date ("YYYY-MM-DD hh:mm", 24-hour format)',
'hlp_fld_default_time' => 'Default time ("hh:mm", 24-hour format)',
'hlp_fld_autogen'   => 'Indica cuando se crean automáticamente los datos para este campo.',

'hdr_form_preview' => 'This is a fully functional preview of how your form will look. If you fill out the data fields and click "Submit", the data will be saved and/or emailed to the form\'s owner. If the post-submit action is to display the results, or a redirect url is configured for the form, you will be taken to that page.',
'hdr_field_edit' => 'Modificando la definición de campos.',
'hdr_form_list'  => 'Selecciona una forma a modificar, o crea una haciendo clic arriba en "Crear". Otras acciones disponibles se muestran en el menú desplegable bajo la columna "Acción".',
'hdr_field_list' => 'Select a field to edit, or create a new field by clicking "New Field" above.',
'hdr_form_results' => 'Here are the results for your form. You can delete them by clicking on the checkbox and selecting "delete"',

'form_results' => 'Ver Resultados',
'del_selected' => 'Delete Selected',
'export'    => 'Exportar CSV',
'submitter' => 'Remitente',
'submitted' => 'Enviado',
'orderby'   => 'Orden',
'name'      => 'Nombre',
'type'      => 'Tipo',
'enabled'   => 'Habilitado',
'required'  => 'Requerido',
'hidden'    => 'Oculto',
'normal'    => 'Normal',
'spancols'  => 'Abarcar todas las columnas',
'user_reg'  => 'Registration',
'readonly'  => 'Solo-Lectura',
'select'    => 'Seleccionar',
'move'      => 'Mover',
'rmfld'     => 'Remove from Form',
'killfld'   => 'Remove and Delete Data',
'usermenu'  => 'View Members',

//'description'   => 'Description',
'textprompt'    => 'Etiqueta',
'fieldname'     => 'Campo',
'formname'      => 'Forma',
'fieldtype'     => 'Tipo',
'inputlen'      => 'Longitud',
'maxlen'        => 'Longitud Máxima',
'columns'       => 'Columnas',
'rows'          => 'Filas',
'value'         => 'Valor',
'defvalue'      => 'Valor Predeterminado',
'showtime'      => 'Show Time',
'hourformat'    => 'formato de 12 o 24 horas',
'hour12'        => '12-horas',
'hour24'        => '24-horas',
'format'        => 'Formato',
'input_format'  => 'Input Format',
'month'         => 'Mes',
'day'           => 'Día',
'year'          => 'Año',
'autogen'       => 'Auto-Generar',
'mask'          => 'Mascara',
'stripmask'     => 'Quitar caracteres de la Mascara',
//'ent_registration' => 'Enter at Registration',
'pos_after'     => 'Ubicar después de',
'nochange'      => 'Sin Cambios',
'first'         => 'Primero',
'permissions'   => 'Permisos',
'group'         => 'Grupo',
'owner'         => 'Propietario',
'admin_group'   => 'Puede Administrarla',
'user_group'    => 'Puede Llenarla',
'results_group' => 'Puede Ver Resultados',
'redirect'  => 'URL de redirección después del envío',
'fieldset1' => 'Additional Form Settings',
'entered_by' => 'Remitente',
'datetime'  => 'Fecha/Hora',
'is_required' => 'no puede estar vacío',
'frm_invalid' => 'The form contains missing or invalid fields.',
'add_century'   => 'Add century if missing',
'err_name_required' => 'Error: El nombre noo puede estar vacío',
'confirm_form_delete' => '¿Seguro quieres borrar esta forma?  Todos los campos asociados y datos se borraran.',
'confirm_delete' => '¿Seguro quieres borrar este ítem?',
'fld_access'    => 'Acceso',

'fld_types' => array(
    'text' => 'Texto', 
    'textarea' => 'Área de Texto',
    'numeric'   => 'Numérico',
    'checkbox' => 'Caja de Chequeo',
    'multicheck' => 'Multiples-Cajas de Chequeo',
    'select' => 'Menú Desplegable',
    'radio' => 'Botón de Radio',
    'date' => 'Fecha',
    'time'  => 'Hora',
    'statictext' => 'Estático',
    'calc'  => 'Calculo',
    'hidden'  => 'Oculto',
),
'calc_type' => 'Calculation Type',
'calc_types' => array(
    'add' => 'Adición',
    'sub' => 'Sustracción',
    'mul' => 'Multiplicación',
    'div' => 'División',
    'mean' => 'Promedio',
),
'submissions'   => 'Envíos',
'frm_url'       => 'URL de la Forma',
'req_captcha' => 'Requiere CAPTCHA',
'inblock' => '¿Mostrar en un bloque?',
'preview_on_save' => 'Mostrar resultados',
'ip_addr'       => 'Dirección IP',
'view_html'     => 'Ver HTML',
'never'     => 'Nunca',
'when_fill' => 'Cuando se llena la forma',
'when_save' => 'Cuando se guarda la forma',
'max_submit' => 'Total Máximo de Envíos',
'onetime'  => 'Limite de Envíos por Usuario',
'pul_once' => 'Una entrada, No Modificable',
'pul_edit' => 'Una entrada, Permite Modificar',
'pul_mult' => 'Múltiples Entradas',
'other_emails' => 'Otras direcciones de email (separar con ";")',
'instance' => 'Instancia',
'showing_instance' => 'Showing results for instance &quot;%s&quot;',
'clear_instance' => 'Mostrar todo',
'datepicker' => 'Click for a date picker',
'print' => 'Print',
'toggle_success' => 'Item has been updated.',
'toggle_failure' => 'Error updating item.',
'edit_result_header' => 'Editing the submission by %1$s (%2$d) from %3$s at %4$s',
'form_type' => 'Tipo',
'regular' => 'Forma Regular',
'field_updated' => 'Field updated',
'save_disabled' => 'Saving disabled in form preview.',
'filled_out_by' => 'Filled out by %1$s on %2$s',
'use_spamx' => 'Use SPAMX Plugin?',
'no_results' => 'No results found',
'reset_results' => 'Remove all results for this form.',
'confirm_form_reset' => 'Are you sure you want to delete all submissions for this form?',
'edit_field' => 'Editing Field',
'now_add_fields' => 'Form has been updated, now add fields.',
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
