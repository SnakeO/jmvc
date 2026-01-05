<?php

declare(strict_types=1);

/**
 * JMVC Configuration Manager
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JConfig
{
    /**
     * Configuration storage
     *
     * @var array<string, mixed>
     */
    public static array $config = [];

    /**
     * Initialize configuration by loading all config files
     */
    public static function init(): void
    {
        // Load in config
        $config_files = glob(JMVC . 'config/*.php');
        if ($config_files) {
            foreach ($config_files as $config_file) {
                require_once $config_file;
            }
        }
    }

    /**
     * Set a configuration value
     *
     * @param string $what Configuration key
     * @param mixed $val Configuration value
     */
    public static function set(string $what, mixed $val): void
    {
        self::$config[$what] = $val;
    }

    /**
     * Get a configuration value using path notation
     *
     * @param string $what_path Path to configuration (e.g., 'array/path/to/item')
     * @return mixed The configuration value or null if not found
     */
    public static function get(string $what_path): mixed
    {
        $what_parts = explode('/', $what_path);
        $first_key = array_shift($what_parts);
        $val = self::$config[$first_key] ?? null;

        foreach ($what_parts as $what_part) {
            if (!is_array($val)) {
                return null;
            }
            $val = $val[$what_part] ?? null;
        }

        return $val;
    }
}
