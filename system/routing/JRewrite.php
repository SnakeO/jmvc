<?php

declare(strict_types=1);

/**
 * JMVC WordPress Rewrite API Integration
 *
 * Registers custom rewrite rules using WordPress Rewrite API,
 * eliminating the need for manual .htaccess or nginx configuration.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JRewrite
{
    /**
     * Initialize rewrite system
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'registerRewriteRules'], 10);
        add_filter('query_vars', [self::class, 'registerQueryVars']);
        add_action('template_redirect', [self::class, 'handleControllerRequest'], 1);
        add_action('template_redirect', [self::class, 'handlePageRequest'], 1);
    }

    /**
     * Register rewrite rules on init
     */
    public static function registerRewriteRules(): void
    {
        // Standard controller routes (API mode)
        // /controller/{env}/{controller}/{action}/{params...}
        add_rewrite_rule(
            '^controller/(pub|admin|resource)/([^/]+)/([^/]+)/?(.*)$',
            'index.php?jmvc_route=1&jmvc_env=$matches[1]&jmvc_controller=$matches[2]&jmvc_action=$matches[3]&jmvc_params=$matches[4]',
            'top'
        );

        // Shorthand public controller routes
        // /controller/{controller}/{action}/{params...}
        add_rewrite_rule(
            '^controller/([^/]+)/([^/]+)/?(.*)$',
            'index.php?jmvc_route=1&jmvc_env=pub&jmvc_controller=$matches[1]&jmvc_action=$matches[2]&jmvc_params=$matches[3]',
            'top'
        );

        // HMVC module routes
        // /hmvc_controller/{env}/{module}/{controller}/{action}/{params...}
        add_rewrite_rule(
            '^hmvc_controller/(pub|admin|resource)/([^/]+)/([^/]+)/([^/]+)/?(.*)$',
            'index.php?jmvc_route=1&jmvc_env=$matches[1]&jmvc_module=$matches[2]&jmvc_controller=$matches[3]&jmvc_action=$matches[4]&jmvc_params=$matches[5]',
            'top'
        );

        // Page routes (theme-wrapped mode)
        // /page/{controller}/{action}/{params...}
        add_rewrite_rule(
            '^page/([^/]+)/([^/]+)/?(.*)$',
            'index.php?jmvc_page=1&jmvc_controller=$matches[1]&jmvc_action=$matches[2]&jmvc_params=$matches[3]',
            'top'
        );

        // Page routes with just controller (defaults to index action)
        // /page/{controller}
        add_rewrite_rule(
            '^page/([^/]+)/?$',
            'index.php?jmvc_page=1&jmvc_controller=$matches[1]&jmvc_action=index',
            'top'
        );
    }

    /**
     * Register custom query vars
     *
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public static function registerQueryVars(array $vars): array
    {
        return array_merge($vars, [
            'jmvc_route',
            'jmvc_page',
            'jmvc_env',
            'jmvc_module',
            'jmvc_controller',
            'jmvc_action',
            'jmvc_params',
        ]);
    }

    /**
     * Handle API/AJAX controller requests at template_redirect
     */
    public static function handleControllerRequest(): void
    {
        if (!get_query_var('jmvc_route')) {
            return;
        }

        $dispatcher = new JControllerDispatcher([
            'env' => get_query_var('jmvc_env') ?: 'pub',
            'module' => get_query_var('jmvc_module') ?: null,
            'controller' => get_query_var('jmvc_controller'),
            'action' => get_query_var('jmvc_action') ?: 'index',
            'params' => get_query_var('jmvc_params') ?: '',
            'render_mode' => 'api',
        ]);

        $dispatcher->dispatch();
        exit;
    }

    /**
     * Handle page requests (theme-wrapped) at template_redirect
     */
    public static function handlePageRequest(): void
    {
        if (!get_query_var('jmvc_page')) {
            return;
        }

        $dispatcher = new JControllerDispatcher([
            'env' => 'pub',
            'module' => null,
            'controller' => get_query_var('jmvc_controller'),
            'action' => get_query_var('jmvc_action') ?: 'index',
            'params' => get_query_var('jmvc_params') ?: '',
            'render_mode' => 'page',
        ]);

        $dispatcher->dispatch();
        exit;
    }

    /**
     * Flush rewrite rules on plugin activation
     */
    public static function activate(): void
    {
        self::registerRewriteRules();
        flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules on plugin deactivation
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Check if WordPress rewrite rules are properly configured
     *
     * @return bool True if rules are registered
     */
    public static function isConfigured(): bool
    {
        $rules = get_option('rewrite_rules');

        if (!is_array($rules)) {
            return false;
        }

        // Check for at least one JMVC rule
        foreach ($rules as $pattern => $rewrite) {
            if (strpos($pattern, 'controller/') === 0 || strpos($pattern, 'page/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate URL for a controller action
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param array $params URL parameters
     * @param string $env Environment (pub, admin, resource)
     * @return string Full URL
     */
    public static function url(string $controller, string $action = 'index', array $params = [], string $env = 'pub'): string
    {
        $path = "controller/{$env}/{$controller}/{$action}";

        if (!empty($params)) {
            $path .= '/' . implode('/', array_map('urlencode', $params));
        }

        return home_url($path);
    }

    /**
     * Generate URL for a page controller action
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param array $params URL parameters
     * @return string Full URL
     */
    public static function pageUrl(string $controller, string $action = 'index', array $params = []): string
    {
        $path = "page/{$controller}/{$action}";

        if (!empty($params)) {
            $path .= '/' . implode('/', array_map('urlencode', $params));
        }

        return home_url($path);
    }
}
