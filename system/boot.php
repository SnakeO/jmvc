<?php
/**
 * JMVC Bootstrap File
 *
 * Include this file in your theme's functions.php or plugin's main file:
 *   require_once get_template_directory() . '/jmvc/system/boot.php';
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

// Path to this system folder
define('JSYS', __DIR__ . '/');

// Path to the jmvc root
define('JMVC', realpath(__DIR__ . '/../') . '/');

// URL to JMVC (define this in your theme if needed)
if (!defined('JMVC_URL')) {
    define('JMVC_URL', get_template_directory_uri() . '/jmvc/');
}

// Load Composer autoloader
$autoloader = JMVC . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    wp_die('JMVC: Please run "composer install" in the jmvc directory.');
}

// Initialize configuration
JConfig::init();

// Initialize KV store
$store_config = JConfig::get('kvstore');

if (!empty($store_config['type'])) {
    if ($store_config['type'] === 'redis') {
        try {
            Predis\Autoloader::register();
            $redis = new Predis\Client();
            JBag::set('kvstore', $redis);
        } catch (Exception $e) {
            error_log('JMVC: Redis connection failed: ' . $e->getMessage());
        }
    } elseif ($store_config['type'] === 'sqlite') {
        try {
            $db_path = JMVC . 'jmvc.sqlite';
            $nsql = new NoSQLite\NoSQLite($db_path);
            $store = $nsql->getStore('jmvc');
            JBag::set('kvstore', $store);
        } catch (Exception $e) {
            error_log('JMVC: SQLite initialization failed: ' . $e->getMessage());
        }
    }
}

// Initialize core systems
JLog::init();
JControllerAjax::init();
DevAlert::init();

// Initialize REST API
add_action('rest_api_init', 'jmvc_register_rest_routes');

/**
 * Register JMVC REST API routes
 */
function jmvc_register_rest_routes()
{
    // Main controller route
    register_rest_route('jmvc/v1', '/(?P<env>pub|admin|resource)/(?P<controller>[a-zA-Z0-9_-]+)/(?P<action>[a-zA-Z0-9_-]+)(?:/(?P<params>.*))?', array(
        'methods'             => array('GET', 'POST'),
        'callback'            => 'jmvc_rest_controller_callback',
        'permission_callback' => 'jmvc_rest_permission_callback',
        'args'                => array(
            'env' => array(
                'required'          => true,
                'validate_callback' => function ($param) {
                    return in_array($param, array('pub', 'admin', 'resource'), true);
                },
            ),
            'controller' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_file_name',
            ),
            'action' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
            ),
            'params' => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Nonce endpoint
    register_rest_route('jmvc/v1', '/nonce', array(
        'methods'             => 'GET',
        'callback'            => function () {
            return rest_ensure_response(array(
                'nonce' => wp_create_nonce('wp_rest'),
            ));
        },
        'permission_callback' => '__return_true',
    ));
}

/**
 * REST API permission callback
 *
 * @param WP_REST_Request $request The request object
 * @return bool|WP_Error True if permitted, WP_Error otherwise
 */
function jmvc_rest_permission_callback($request)
{
    $env = $request->get_param('env');

    // Admin routes require authentication
    if ($env === 'admin') {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                'Authentication required',
                array('status' => 401)
            );
        }

        // Verify nonce for state-changing requests
        if ($request->get_method() !== 'GET') {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    'Invalid nonce',
                    array('status' => 403)
                );
            }
        }
    }

    return true;
}

/**
 * REST API controller callback
 *
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response|WP_Error The response
 */
function jmvc_rest_controller_callback($request)
{
    $env = $request->get_param('env');
    $controller = $request->get_param('controller');
    $action = $request->get_param('action');
    $params_string = $request->get_param('params');

    // Parse params
    $params = array();
    if (!empty($params_string)) {
        $params = array_filter(
            explode('/', $params_string),
            function ($p) {
                return $p !== '';
            }
        );
        $params = array_map('sanitize_text_field', $params);
    }

    // Load controller
    $obj = JController::load($controller, $env);

    if (!$obj) {
        return new WP_Error(
            'jmvc_not_found',
            'Controller not found: ' . $controller,
            array('status' => 404)
        );
    }

    if (!method_exists($obj, $action)) {
        return new WP_Error(
            'jmvc_not_found',
            'Method not found: ' . $action,
            array('status' => 404)
        );
    }

    // Capture output
    ob_start();
    $result = call_user_func_array(array($obj, $action), $params);
    $output = ob_get_clean();

    // If controller returned a value, use it
    if ($result !== null) {
        return rest_ensure_response($result);
    }

    // Otherwise return captured output
    if (!empty($output)) {
        // Check if output is JSON
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return rest_ensure_response($decoded);
        }

        // Return as HTML
        return new WP_REST_Response($output, 200, array(
            'Content-Type' => 'text/html',
        ));
    }

    return rest_ensure_response(array('success' => true));
}

/**
 * Enqueue JMVC scripts and provide JavaScript helpers
 *
 * Uncomment and customize in your theme's functions.php
 */
/*
add_action('wp_enqueue_scripts', function () {
    // Provide AJAX and REST API URLs to JavaScript
    wp_localize_script('jquery', 'JMVC', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => rest_url('jmvc/v1/'),
        'nonce'    => wp_create_nonce('wp_rest'),
    ));
});
*/
