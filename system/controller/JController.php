<?php

declare(strict_types=1);

/**
 * JMVC Controller Loader
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JController
{
    /**
     * Current HMVC module
     */
    protected ?string $module = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Override in subclasses
    }

    /**
     * Load a controller class
     *
     * @param string $controllername The controller name
     * @param string $env The environment (admin, pub, resource)
     * @param string|null $module The HMVC module name
     * @return object|false The controller instance or false if not found
     */
    public static function load(string $controllername, string $env = 'admin', ?string $module = null): object|false
    {
        $valid_envs = array('admin', 'pub', 'resource');

        if (!in_array($env, $valid_envs, true)) {
            wp_die(esc_html('Invalid JController::load environment: ' . $env));
        }

        // Sanitize controller name to prevent directory traversal
        $controllername = sanitize_file_name($controllername);
        $controller_filename = $controllername . 'Controller.php';
        $classname = $controllername . 'Controller';

        // Load from main or module
        if (!$module) {
            $path = JMVC . 'controllers/' . $env . '/' . $controller_filename;

            if (!file_exists($path)) {
                return false;
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath(JMVC . 'controllers/');

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                return false;
            }

            require_once $path;
            $class = $classname;
        } else {
            // Sanitize module name
            $module = sanitize_file_name($module);
            $path = JMVC . 'modules/' . $module . '/controllers/' . $env . '/' . $controller_filename;

            if (!file_exists($path)) {
                return false;
            }

            // Verify path is within allowed directory
            $real_path = realpath($path);
            $real_base = realpath(JMVC . 'modules/');

            if ($real_path === false || strpos($real_path, $real_base) !== 0) {
                return false;
            }

            // Modules are namespaced
            require_once $path;
            $class = $module . '\\' . $env . '\\' . $classname;
        }

        if (!class_exists($class)) {
            return false;
        }

        return new $class();
    }
}
