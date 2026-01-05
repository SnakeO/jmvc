<?php

declare(strict_types=1);

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
     */
    public static ?string $module = null;

    /**
     * Storage array for services
     *
     * @var array<string, mixed>
     */
    private static array $things = [];

    /**
     * Store a value
     *
     * @param string $k Key
     * @param mixed $v Value
     */
    public static function set(string $k, mixed $v): void
    {
        self::$things[$k] = $v;
    }

    /**
     * Retrieve a value
     *
     * @param string $k Key
     * @return mixed The value or null if not found
     */
    public static function get(string $k): mixed
    {
        return self::$things[$k] ?? null;
    }

    /**
     * Check if a key exists
     *
     * @param string $k Key
     * @return bool True if key exists
     */
    public static function has(string $k): bool
    {
        return array_key_exists($k, self::$things);
    }

    /**
     * Remove a value
     *
     * @param string $k Key
     */
    public static function remove(string $k): void
    {
        unset(self::$things[$k]);
    }
}
