<?php
/**
 * Class to cache records for the Forms plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     forms
 * @version     0.4.0
 * @since       0.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 */
namespace Forms;

/**
 * Cache DB lookups for performance.
 */
class Cache
{
    /** Tag to prepend to all cache keys.
     * @const string */
    const TAG = 'forms';

    /** Minimum glFusion version that supports caching.
     * @const string */
    const MIN_GVERSION = '2.0.0';

    /**
     * Update the cache.
     *
     * @param   string  $key    Item key
     * @param   mixed   $data   Data, typically an array
     * @param   mixed   $tag    Single tag, or an array
     * @return  boolean     True on success, False on error
     */
    public static function set($key, $data, $tag='')
    {
        if ($tag == '') {
            $tag = array(self::TAG);
        } elseif (is_array($tag)) {
            $tag[] = self::TAG;
        } else {
            $tag = array($tag, self::TAG);
        }
        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->set($key, $data, $tag, 86400);
    }


    /**
     * Delete a single item from the cache by key
     *
     * @param   string  $key    Base key, e.g. item ID
     * @return  boolean     True on success, False on error
     */
    public static function delete($key)
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            return;     // glFusion version doesn't support caching
        }
        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->delete($key);
    }


    /**
     * Completely clear the cache.
     * Entries matching all tags, including default tag, are removed.
     *
     * @param   mixed   $tag    Single or array of tags. Empty to remove all.
     * @return  boolean     True on success, False on error
     */
    public static function clear($tag = '')
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return NULL;

        $tags = array(self::TAG);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Create a unique cache key.
     * Prepends the plugin name to any provided key.
     *
     * @param   string  $key    Base key
     * @return  string          Encoded key string to use as a cache ID
     */
    private static function _makeKey($key)
    {
        return \glFusion\Cache\Cache::getInstance()
            ->createKey(self::TAG . '_' . $key);
    }


    /**
     * Get a specific item from cache.
     * Wraps \glFusion\Cache
     *
     * @param   string  $key    Key to retrieve
     * @return  mixed           Value of key, NULL if not found
     */
    public static function get($key)
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return NULL;

        $key = self::_makeKey($key);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            return \glFusion\Cache\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}   // class Forms\Cache

?>
