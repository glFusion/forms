<?php
/**
*   Class to cache DB and web lookup results
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    forms
*   @version    0.3.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Forms;

/**
*   Class for Meetup events
*   @package forms
*/
class Cache
{
    private static $tag = 'forms';

    /**
    *   Update the cache
    *
    *   @param  string  $key    Item key
    *   @param  mixed   $data   Data, typically an array
    *   @param  
    */
    public static function set($key, $data, $tag='')
    {
        global $_CONF_LIB;

        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        if ($tag == '')
            $tag = array(self::$tag);
        elseif (is_array($tag))
            $tag[] = self::$tag;
        else
            $tag = array($tag, self::$tag);
        $key = self::_makeKey($key);
        \glFusion\Cache::getInstance()->set($key, $data, $tag);
    }


    /**
    *   Completely clear the cache.
    *   Called after upgrade.
    *   Entries matching all tags, including default tag, are removed.
    *
    *   @param  mixed   $tag    Single or array of tags
    */
    public static function clear($tag = '')
    {
        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        $tags = array(self::$tag);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        \glFusion\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
    *   Create a unique cache key.
    *
    *   @return string          Encoded key string to use as a cache ID
    */
    private static function _makeKey($key)
    {
        return self::$tag . '_' . md5($key);
    }

    
    public static function get($key, $tag='')
    {
        global $_EV_CONF;

        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        $key = self::_makeKey($key);
        if (\glFusion\Cache::getInstance()->has($key)) {
            return \glFusion\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}   // class Library\Cache

?>
