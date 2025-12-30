<?php
/**
 * JMVC Service Locator / Global Store
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JBag
{
    /**
     * Current HMVC module
     *
     * @var string|null
     */
    public static $module;

    /**
     * Storage array for services
     *
     * @var array
     */
    private static $things = array();

    /**
     * Store a value
     *
     * @param string $k Key
     * @param mixed $v Value
     */
    public static function set($k, $v)
    {
        self::$things[$k] = $v;
    }

    /**
     * Retrieve a value
     *
     * @param string $k Key
     * @return mixed|null The value or null if not found
     */
    public static function get($k)
    {
        return self::$things[$k] ?? null;
    }

    /**
     * Check if a key exists
     *
     * @param string $k Key
     * @return bool True if key exists
     */
    public static function has($k)
    {
        return array_key_exists($k, self::$things);
    }

    /**
     * Remove a value
     *
     * @param string $k Key
     */
    public static function remove($k)
    {
        unset(self::$things[$k]);
    }
}
