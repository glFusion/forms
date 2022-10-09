<?php
/**
 * Class to create custom admin list fields.
 * Based on the FieldList class included in glFusion 2.0, provided
 * until that version is released.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     v0.5.0
 * @since       v0.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Forms;


/**
 * Class to handle custom fields.
 * @package forms
 */
class FieldList extends \glFusion\FieldList
{
    /** Template variable.
     * @var object */
    private static $t = NULL;


    /**
     * Initialize the template variable.
     *
     * @return  object      Template object
     */
    protected static function init()
    {
        global $_CONF;

        static $t = NULL;
        if (self::$t === NULL) {
            $t = new \Template($_CONF['path'] .'/plugins/forms/templates/');
            $t->set_file('field','fieldlist.thtml');
        } else {
            $t->unset_var('attributes');
            $t->unset_var('output');
        }
        return $t;
    }


    /**
     * Create a preview link.
     *
     * @param   array   $args   Argument array
     * @return  string      HTML for field
     */
    public static function preview($args)
    {
        $t = self::init();

        $t->set_block('field','field-preview');
        if (isset($args['url'])) {
            $t->set_var('url', $args['url']);
        } else {
            $t->set_var('url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-preview');
        return $t->finish($t->get_var('output'));
    }


    /**
     * Create a HTML view link.
     *
     * @param   array   $args   Argument array
     * @return  string      HTML for field
     */
    public static function codeview($args)
    {
        $t = self::init();
        $t->set_block('field','field-codeview');
        if (isset($args['url'])) {
            $t->set_var('url', $args['url']);
        } else {
            $t->set_var('url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-codeview');
        return $t->finish($t->get_var('output'));
    }


    /**
     * Create a reset link field.
     *
     * @param   array   $args   Argument array
     * @return  string      HTML for field
     */
    public static function reset($args)
    {
        $t = self::init();
        $t->set_block('field','field-reset');
        if (isset($args['url'])) {
            $t->set_var('url', $args['url']);
        } else {
            $t->set_var('url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-reset');
        return $t->finish($t->get_var('output'));
    }


    /**
     * Create a reset link field.
     *
     * @param   array   $args   Argument array
     * @return  string      HTML for field
     */
    public static function print($args)
    {
        $t = self::init();
        $t->set_block('field','field-print');
        if (isset($args['url'])) {
            $t->set_var('url', $args['url']);
        } else {
            $t->set_var('url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-print');
        return $t->finish($t->get_var('output'));
    }


    public static function buttonlink($args)
    {
        $def_args = array(
            'url' => '!#',
            'size' => '',   // mini
            'style' => 'default',  // success, danger, etc.
            'type' => '',   // submit, reset, etc.
            'class' => '',  // additional classes
        );
        $args = array_merge($def_args, $args);

        $t = self::init();
        $t->set_block('field','field-buttonlink');

        $t->set_var(array(
            'url' => $args['url'],
            'size' => $args['size'],
            'style' => $args['style'],
            'type' => $args['type'],
            'other_cls' => $args['class'],
            'text' => $args['text'],
        ) );

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field-button','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-buttonlink',true);
        return $t->finish($t->get_var('output'));
    }



}
