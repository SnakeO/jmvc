<?php
/**
 * JMVC Library Loader
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JLib
{
    /**
     * Loaded library instances
     *
     * @var array
     */
    public static $libs = array();

    /**
     * Current module
     *
     * @var string|null
     */
    public $module;

    /**
     * Constructor
     *
     * @param string|null $module HMVC module name
     */
    public function __construct($module = null)
    {
        $this->module = $module;
    }

    /**
     * Load a library class
     *
     * @param string $librarypath Library path relative to libraries directory
     * @param string|null $module HMVC module name
     * @return object|false Library instance or false if not found
     * @throws Exception If class doesn't exist
     */
    public static function load($librarypath, $module = null)
    {
        // Already loaded?
        if (isset(self::$libs[$librarypath])) {
            return self::$libs[$librarypath];
        }

        // Sanitize inputs
        $librarypath = preg_replace('/[^a-zA-Z0-9_\/\-]/', '', $librarypath);
        $module = $module ? sanitize_file_name($module) : null;

        // Allow for libraries to live in nested subdirectories
        $pathinfo = pathinfo($librarypath);
        $libraryname = $pathinfo['basename'];
        $librarydir = $pathinfo['dirname'];

        if ($librarydir === '.') {
            $librarydir = '';
        }

        $library_filename = $libraryname . '.php';

        // Load from main or module
        if (!$module) {
            $base_path = JMVC . 'libraries/';
            $path = $base_path . ($librarydir ? $librarydir . '/' : '') . $library_filename;

            if (!file_exists($path)) {
                // Try lowercase
                $library_filename = strtolower($library_filename);
                $path = $base_path . ($librarydir ? $librarydir . '/' : '') . $library_filename;

                if (!file_exists($path)) {
                    return false;
                }
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath($base_path);

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                return false;
            }

            require_once $path;
            $class = $librarydir ? '\\libraries\\' . $librarydir . '\\' . $libraryname : '\\libraries\\' . $libraryname;
        } else {
            $base_path = JMVC . 'modules/' . $module . '/libraries/';
            $path = $base_path . ($librarydir ? $librarydir . '/' : '') . $library_filename;

            if (!file_exists($path)) {
                // Try lowercase
                $library_filename = strtolower($library_filename);
                $path = $base_path . ($librarydir ? $librarydir . '/' : '') . $library_filename;

                if (!file_exists($path)) {
                    return false;
                }
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath(JMVC . 'modules/');

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                return false;
            }

            require_once $path;
            $class = $librarydir
                ? $module . '\\libraries\\' . $librarydir . '\\' . $libraryname
                : $module . '\\libraries\\' . $libraryname;
        }

        if (!class_exists($class)) {
            throw new Exception('Class does not exist: ' . esc_html($class));
        }

        self::$libs[$librarypath] = new $class();
        return self::$libs[$librarypath];
    }
}
