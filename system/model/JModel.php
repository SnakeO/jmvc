<?php
/**
 * JMVC Model Loader
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JModel
{
    /**
     * Singleton instances
     *
     * @var array
     */
    public static $singletons = array();

    /**
     * Load a model class
     *
     * @param string $modelpath Model path relative to models directory
     * @param string|null $module HMVC module name
     * @param bool $get_singleton Whether to return a singleton instance
     * @return object|null Model instance
     * @throws Exception If model not found
     */
    public static function load($modelpath, $module = null, $get_singleton = false)
    {
        // Sanitize path
        $modelpath = sanitize_file_name($modelpath);
        $module = $module ? sanitize_file_name($module) : null;

        $cache_key = $modelpath . '.' . ($module ?? '');

        if ($get_singleton && isset(self::$singletons[$cache_key])) {
            return self::$singletons[$cache_key];
        }

        $modelname = basename($modelpath);

        // Load from main or module
        if (!$module) {
            $path = JMVC . 'models/' . $modelpath . '.php';

            if (!file_exists($path)) {
                throw new Exception('Could not find global model: ' . esc_html($modelpath));
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath(JMVC . 'models/');

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                throw new Exception('Invalid model path');
            }

            $class = $modelname;
            require_once $path;
        } else {
            $path = JMVC . 'modules/' . $module . '/models/' . $modelpath . '.php';

            if (!file_exists($path)) {
                throw new Exception('Could not find model ' . esc_html($modelpath) . ' in ' . esc_html($module));
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath(JMVC . 'modules/');

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                throw new Exception('Invalid model path');
            }

            // Modules are namespaced
            $class = $module . '\\' . $modelname;
            require_once $path;
        }

        if (!class_exists($class)) {
            throw new Exception('Class does not exist: ' . esc_html($class));
        }

        if ($get_singleton) {
            self::$singletons[$cache_key] = new $class();
            return self::$singletons[$cache_key];
        }

        return null;
    }

    /**
     * Check if a model exists
     *
     * @param string $modelpath Model path
     * @param string|null $module HMVC module name
     * @return string|false Path to model or false if not found
     */
    public static function exists($modelpath, $module = null)
    {
        $modelpath = sanitize_file_name($modelpath);
        $module = $module ? sanitize_file_name($module) : null;

        $cache_key = $modelpath . '.' . ($module ?? '');

        if (isset(self::$singletons[$cache_key])) {
            return true;
        }

        if (!$module) {
            $path = JMVC . 'models/' . $modelpath . '.php';
        } else {
            $path = JMVC . 'modules/' . $module . '/models/' . $modelpath . '.php';
        }

        if (!file_exists($path)) {
            return false;
        }

        return $path;
    }
}
