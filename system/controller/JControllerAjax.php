<?php

declare(strict_types=1);

/**
 * JMVC AJAX Controller System
 *
 * .htaccess rules:
 * RewriteRule ^controller/(pub|admin|resource)/(.*) wp-admin/admin-ajax.php?action=$1_controller&path=$2 [L,QSA]
 * RewriteRule ^hmvc_controller/(pub|admin|resource)/(.*?)/(.*) wp-admin/admin-ajax.php?action=$1_controller&path=$3&module=$2 [L,QSA]
 *
 * NGINX:
 * rewrite ^/controller/(pub|admin|resource)/(.*) /wp-admin/admin-ajax.php?action=$1_controller&path=$2 last; break;
 * rewrite ^/hmvc_controller/(pub|admin|resource)/(.*?)/(.*) /wp-admin/admin-ajax.php?action=$1_controller&path=$3&module=$2 last; break;
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JControllerAjax
{
    /**
     * Initialize the AJAX controller system
     *
     * @return self
     */
    public static function init(): self
    {
        return new self();
    }

    /**
     * Class constructor. Set up filters and actions.
     */
    public function __construct()
    {
        // Controllers for the admin side of WordPress
        add_action('wp_ajax_admin_controller', array($this, 'admin_controller'));

        // Controllers for the front-facing side of WordPress
        add_action('wp_ajax_pub_controller', array($this, 'pub_controller'));
        add_action('wp_ajax_nopriv_pub_controller', array($this, 'pub_controller'));

        // Controllers for AJAX resources
        add_action('wp_ajax_resource_controller', array($this, 'resource_controller'));
        add_action('wp_ajax_nopriv_resource_controller', array($this, 'resource_controller'));

        // Query vars that this class needs
        add_filter('query_vars', array($this, 'query_vars'));

        // Register nonce creation endpoint
        add_action('wp_ajax_jmvc_nonce', array($this, 'get_nonce'));
        add_action('wp_ajax_nopriv_jmvc_nonce', array($this, 'get_nonce'));
    }

    /**
     * Get a nonce for AJAX requests
     */
    public function get_nonce(): void
    {
        wp_send_json(array(
            'nonce' => wp_create_nonce('jmvc_ajax_nonce'),
        ));
    }

    /**
     * Verify AJAX nonce
     *
     * @param bool $required Whether nonce is required (admin always requires)
     * @return bool True if valid
     */
    protected function verify_nonce(bool $required = true): bool
    {
        $nonce = isset($_REQUEST['_jmvc_nonce']) ? sanitize_text_field($_REQUEST['_jmvc_nonce']) : '';

        if ($required && !wp_verify_nonce($nonce, 'jmvc_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
            exit;
        }

        return (bool) wp_verify_nonce($nonce, 'jmvc_ajax_nonce');
    }

    /**
     * Admin controller handler
     */
    public function admin_controller(): void
    {
        // Admin controllers always require nonce and login
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Authentication required'), 401);
            exit;
        }

        $this->verify_nonce(true);
        $this->ajax_controller('admin');
    }

    /**
     * Public controller handler
     */
    public function pub_controller(): void
    {
        // Public controllers optionally verify nonce
        $this->verify_nonce(false);
        $this->ajax_controller('pub');
    }

    /**
     * Resource controller handler
     */
    public function resource_controller(): void
    {
        // Resource controllers optionally verify nonce
        $this->verify_nonce(false);
        $this->ajax_controller('resource');
    }

    /**
     * Loads in the requested controller and calls the requested function
     *
     * @param string $env The environment (pub, admin, resource)
     */
    public function ajax_controller(string $env): never
    {
        // Sanitize inputs
        $module = isset($_GET['module']) ? sanitize_file_name($_GET['module']) : null;
        $path = isset($_GET['path']) ? sanitize_text_field($_GET['path']) : '';

        if (empty($path)) {
            wp_send_json_error(array('message' => 'No path specified'), 400);
            exit;
        }

        $parts = explode('/', $path);
        $controller = sanitize_file_name(array_shift($parts));

        $funk = array_shift($parts);
        if (empty($funk)) {
            $funk = 'index';
        }
        $funk = sanitize_key($funk);

        // Filter out empty params
        $params = array_filter($parts, function ($p): bool {
            return $p !== null && $p !== '';
        });
        $params = array_values($params);

        // Sanitize params
        $params = array_map('sanitize_text_field', $params);

        $obj = JController::load($controller, $env, $module);

        if (!$obj) {
            $details = array(
                'controller' => $controller,
                'env'        => $env,
                'module'     => $module,
                'funk'       => $funk,
            );

            DevAlert::slack('Controller not found: ' . $controller, $details);

            wp_send_json_error(
                array('message' => 'Controller not found: ' . esc_html($controller)),
                404
            );
            exit;
        }

        if (!method_exists($obj, $funk)) {
            $details = array(
                'controller' => $controller,
                'env'        => $env,
                'module'     => $module,
                'funk'       => $funk,
            );

            DevAlert::slack('Method not found: ' . $funk, $details);

            wp_send_json_error(
                array('message' => 'Method not found in controller ' . esc_html($controller) . ': ' . esc_html($funk)),
                404
            );
            exit;
        }

        // Call controller method
        call_user_func_array(array($obj, $funk), $params);

        exit;
    }

    /**
     * Add query vars for controller routing
     *
     * @param array $query_vars Current query vars
     * @return array Updated query vars
     */
    public function query_vars(array $query_vars): array
    {
        $query_vars[] = 'action';
        $query_vars[] = 'path';
        $query_vars[] = 'module'; // for HMVC
        return $query_vars;
    }
}

/**
 * Generate URL for controller endpoint
 *
 * @param string $url The URL path (ControllerClass/ControllerFunction/param/param)
 * @param string $env The environment (pub, admin, resource)
 * @param string|null $module The HMVC module name (optional)
 * @return string The generated URL
 */
function controller_url(string $url, string $env = 'admin', ?string $module = null): string
{
    $valid_envs = array('admin', 'public', 'pub', 'resource');

    if (!in_array($env, $valid_envs, true)) {
        wp_die(esc_html('Invalid controller environment: ' . $env));
    }

    $url_parts = explode('/', $url);

    $controller = array_shift($url_parts);
    if (empty($controller)) {
        wp_die('Controller name required');
    }

    $function = array_shift($url_parts);
    if (empty($function)) {
        wp_die('Function name required');
    }

    // Sanitize parts
    $controller = sanitize_file_name($controller);
    $function = sanitize_key($function);

    $params = count($url_parts) > 0 ? '/' . implode('/', array_map('sanitize_text_field', $url_parts)) : '';

    // Use pretty controllers
    if (!$module) {
        return site_url('/controller/' . $env . '/' . $controller . '/' . $function . $params);
    }

    $module = sanitize_file_name($module);
    return site_url('/hmvc_controller/' . $env . '/' . $module . '/' . $controller . '/' . $function . $params);
}

/**
 * Get a fresh JMVC nonce for AJAX calls
 *
 * @return string The nonce value
 */
function jmvc_get_nonce(): string
{
    return wp_create_nonce('jmvc_ajax_nonce');
}
