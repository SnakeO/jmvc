<?php
/**
 * JMVC Admin - Main admin class
 *
 * @package JMVC\Admin
 */

declare(strict_types=1);

namespace JMVC\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once __DIR__ . '/Installer.php';
require_once __DIR__ . '/ConfigWriter.php';
require_once __DIR__ . '/Browser.php';
require_once __DIR__ . '/RewriteTest.php';

/**
 * Main admin class for JMVC plugin
 */
class Admin
{
    private static bool $initialized = false;

    /**
     * Initialize admin functionality
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Register admin menu
        add_action('admin_menu', [self::class, 'registerMenu']);

        // Register admin assets
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);

        // Handle AJAX actions
        add_action('wp_ajax_jmvc_initialize', [self::class, 'ajaxInitialize']);
        add_action('wp_ajax_jmvc_install_deps', [self::class, 'ajaxInstallDeps']);
        add_action('wp_ajax_jmvc_save_config', [self::class, 'ajaxSaveConfig']);
        add_action('wp_ajax_jmvc_test_rewrite', [self::class, 'ajaxTestRewrite']);
    }

    /**
     * Get path to JMVC directory in active theme
     */
    public static function getThemeJmvcPath(): string
    {
        return get_stylesheet_directory() . '/jmvc/';
    }

    /**
     * Get URL to JMVC directory in active theme
     */
    public static function getThemeJmvcUrl(): string
    {
        return get_stylesheet_directory_uri() . '/jmvc/';
    }

    /**
     * Check if JMVC is initialized in the active theme
     *
     * Verifies all essential files and directories exist:
     * - controllers directory
     * - composer.json
     * - config directory with default config files
     */
    public static function isInitialized(): bool
    {
        $basePath = self::getThemeJmvcPath();

        // Check for essential directories
        if (!is_dir($basePath . 'controllers')) {
            return false;
        }

        // Check for composer.json
        if (!file_exists($basePath . 'composer.json')) {
            return false;
        }

        // Check for config directory and essential config files
        if (!is_dir($basePath . 'config')) {
            return false;
        }

        return true;
    }

    /**
     * Check if composer dependencies are installed
     */
    public static function hasVendor(): bool
    {
        return is_dir(self::getThemeJmvcPath() . 'vendor');
    }

    /**
     * Get active theme name
     */
    public static function getThemeName(): string
    {
        $theme = wp_get_theme();
        return $theme->get('Name');
    }

    /**
     * Register admin menu pages
     */
    public static function registerMenu(): void
    {
        add_menu_page(
            __('JMVC', 'jmvc'),
            __('JMVC', 'jmvc'),
            'manage_options',
            'jmvc',
            [self::class, 'renderDashboard'],
            'dashicons-layout',
            30
        );

        add_submenu_page(
            'jmvc',
            __('Dashboard', 'jmvc'),
            __('Dashboard', 'jmvc'),
            'manage_options',
            'jmvc',
            [self::class, 'renderDashboard']
        );

        add_submenu_page(
            'jmvc',
            __('Settings', 'jmvc'),
            __('Settings', 'jmvc'),
            'manage_options',
            'jmvc-settings',
            [self::class, 'renderSettings']
        );

        add_submenu_page(
            'jmvc',
            __('Components', 'jmvc'),
            __('Components', 'jmvc'),
            'manage_options',
            'jmvc-components',
            [self::class, 'renderComponents']
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueueAssets(string $hook): void
    {
        // Only load on JMVC pages
        if (strpos($hook, 'jmvc') === false) {
            return;
        }

        wp_enqueue_style(
            'jmvc-admin',
            JMVC_PLUGIN_URL . 'admin/assets/admin.css',
            [],
            JMVC_VERSION
        );

        wp_enqueue_script(
            'jmvc-admin',
            JMVC_PLUGIN_URL . 'admin/assets/admin.js',
            ['jquery'],
            JMVC_VERSION,
            true
        );

        wp_localize_script('jmvc-admin', 'jmvcAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jmvc_admin'),
            'strings' => [
                'initializing' => __('Initializing...', 'jmvc'),
                'installing' => __('Installing dependencies...', 'jmvc'),
                'testing' => __('Testing rewrite rules...', 'jmvc'),
                'saving' => __('Saving...', 'jmvc'),
                'success' => __('Success!', 'jmvc'),
                'error' => __('Error occurred', 'jmvc'),
            ],
        ]);
    }

    /**
     * Render dashboard page
     */
    public static function renderDashboard(): void
    {
        require_once __DIR__ . '/views/dashboard.php';
    }

    /**
     * Render settings page
     */
    public static function renderSettings(): void
    {
        require_once __DIR__ . '/views/config.php';
    }

    /**
     * Render components page
     */
    public static function renderComponents(): void
    {
        require_once __DIR__ . '/views/browser.php';
    }

    /**
     * AJAX: Initialize JMVC in theme
     */
    public static function ajaxInitialize(): void
    {
        check_ajax_referer('jmvc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'jmvc')]);
        }

        $result = Installer::install();

        if ($result['success']) {
            wp_send_json_success(['message' => __('JMVC initialized successfully', 'jmvc')]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
        }
    }

    /**
     * AJAX: Install composer dependencies
     */
    public static function ajaxInstallDeps(): void
    {
        check_ajax_referer('jmvc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'jmvc')]);
        }

        $result = Installer::installDependencies();

        if ($result['success']) {
            wp_send_json_success(['message' => __('Dependencies installed successfully', 'jmvc')]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Could not install dependencies', 'jmvc'),
                'instructions' => $result['instructions'] ?? null,
            ]);
        }
    }

    /**
     * AJAX: Save configuration
     */
    public static function ajaxSaveConfig(): void
    {
        check_ajax_referer('jmvc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'jmvc')]);
        }

        $config = $_POST['config'] ?? [];

        if (empty($config)) {
            wp_send_json_error(['message' => __('No configuration provided', 'jmvc')]);
        }

        $result = ConfigWriter::saveAll($config);

        if ($result['success']) {
            wp_send_json_success(['message' => __('Configuration saved', 'jmvc')]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
        }
    }

    /**
     * AJAX: Test rewrite rules
     */
    public static function ajaxTestRewrite(): void
    {
        check_ajax_referer('jmvc_admin', 'nonce');

        $result = RewriteTest::check();

        wp_send_json_success([
            'status' => $result['status'],
            'serverType' => RewriteTest::getServerType(),
            'instructions' => $result['status'] ? null : RewriteTest::getInstructions(),
        ]);
    }
}
