<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019-2020 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.5.0
 * @since       v0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms;

/**
 * Class to provide admin and user-facing menus.
 * @package forms
 */
class Menu
{
    /**
     * Create the admin menu at the top of the list and form pages.
     *
     * @param   string  $view       Current view, used to select menu options
     * @param   string  $help_text  Text to display below menu
     * @return  string      HTML for admin menu section
     */
    public static function User($view ='', $help_text = '')
    {
        global $_CONF, $LANG_FORMS, $LANG01, $_CONF_FRM;

        USES_lib_admin();

        $retval = "<h1>{$LANG_FORMS['menu_title']} (Ver. {$_CONF_FRM['pi_version']})</h1>";
        $menu_arr = array(
            array(
                'url' => FRM_PI_URL . '/index.php?listforms',
                'text' => $LANG_FORMS['list_forms'],
            ),
        );
        $text = isset($LANG_FORMS[$help_text]) ? $LANG_FORMS[$help_text] : '';
        $retval .= ADMIN_createMenu($menu_arr, $text, plugin_geticon_forms());
        return $retval;
    }


    /**
     * Create the admin menu at the top of the list and form pages.
     *
     * @param   string  $view       Current view, used to select menu options
     * @param   string  $help_text  Text to display below menu
     * @return  string      HTML for admin menu section
     */
    public static function Admin($view ='', $help_text = '')
    {
        global $_CONF, $LANG_FORMS, $LANG01;

        USES_lib_admin();

        $menu_arr = array ();
        if ($help_text == '') {
            $help_text = 'admin_text';
        }

        if ($view == 'listforms') {
            $menu_arr[] = array(
                'url' => FRM_ADMIN_URL . '/index.php?action=editform',
                'text' => $LANG_FORMS['add_form'],
            );
        } else {
            $menu_arr[] = array(
                'url' => FRM_ADMIN_URL . '/index.php?view=listforms',
                'text' => $LANG_FORMS['list_forms'],
            );
        }
        $menu_arr[] = array(
            'url' => $_CONF['site_admin_url'],
            'text' => $LANG01[53],
        );

        $text = isset($LANG_FORMS[$help_text]) ? $LANG_FORMS[$help_text] : '';
        return ADMIN_createMenu($menu_arr, $text, plugin_geticon_forms());
    }


    /**
     * Show the site header, with or without left blocks according to config.
     *
     * @see     COM_siteHeader()
     * @param   string  $subject    Text for page title (ad title, etc)
     * @param   string  $meta       Other meta info
     * @param   integer $blocks     Indicator of blocks to display
     * @return  string              HTML for site header
     */
    public static function siteHeader($subject='', $meta='', $blocks = -1)
    {
        global $_CONF_FRM, $LANG_FRM;

        $retval = '';

        $blocks = $blocks > -1 ? $blocks : $_CONF_FRM['displayblocks'];
        switch($blocks) {
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
     * Show the site footer, with or without right blocks according to config.
     * If zero is given as an argument, then COM_siteFooter() is called to
     * finish output but is not displayed. This is so a popup form will not have
     * the complete site content but only the form.
     *
     * @see     COM_siteFooter()
     * @param   integer $blocks Zero to hide sitefooter
     * @return  string          HTML for site header
     */
    public static function siteFooter($blocks = -1)
    {
        global $_CONF_FRM, $_CONF;

        $retval = '';

        if ($blocks == 0) {
            // Run siteFooter to finish the page, but return nothing
            COM_siteFooter();
            return;
        }

        if ($_CONF['show_right_blocks']) {
            $retval .= COM_siteFooter(true);
            return $retval;
        }
        $blocks = $blocks > -1 ? $blocks : $_CONF_FRM['displayblocks'];
        switch($blocks) {
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

}
