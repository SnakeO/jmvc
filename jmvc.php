<?php
/**
 * Plugin Name: JMVC
 * Plugin URI: https://github.com/SnakeO/jmvc
 * Description: WordPress MVC Framework - Build structured, maintainable WordPress applications
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: JMVC Contributors
 * License: MIT
 * Text Domain: jmvc
 */

declare(strict_types=1);

namespace JMVC;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('JMVC_VERSION', '2.0.0');
define('JMVC_PLUGIN_FILE', __FILE__);
define('JMVC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JMVC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load admin classes
require_once JMVC_PLUGIN_PATH . 'admin/Admin.php';

// Initialize admin
add_action('plugins_loaded', function(): void {
    Admin\Admin::init();
});

// Boot framework only if initialized in theme
add_action('init', function(): void {
    if (Admin\Admin::isInitialized()) {
        require_once JMVC_PLUGIN_PATH . 'system/boot.php';
    }
}, 5);

// Register activation hook
register_activation_hook(__FILE__, function(): void {
    // Load rewrite system and register rules
    if (Admin\Admin::isInitialized()) {
        require_once JMVC_PLUGIN_PATH . 'system/routing/JRewrite.php';
        \JRewrite::activate();
    } else {
        flush_rewrite_rules();
    }
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function(): void {
    // Clean up rewrite rules
    if (file_exists(JMVC_PLUGIN_PATH . 'system/routing/JRewrite.php')) {
        require_once JMVC_PLUGIN_PATH . 'system/routing/JRewrite.php';
        \JRewrite::deactivate();
    } else {
        flush_rewrite_rules();
    }
});
