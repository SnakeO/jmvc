<?php

declare(strict_types=1);

/**
 * JMVC Controller Dispatcher
 *
 * Handles routing requests to controllers with support for
 * multiple render modes: API, Page (theme-wrapped), and Block.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JControllerDispatcher
{
    /**
     * Environment (pub, admin, resource)
     */
    private string $env;

    /**
     * HMVC module name
     */
    private ?string $module;

    /**
     * Controller name
     */
    private string $controller;

    /**
     * Action method name
     */
    private string $action;

    /**
     * URL parameters
     *
     * @var array<int, string>
     */
    private array $params;

    /**
     * Render mode (api, page, block)
     */
    private string $renderMode;

    /**
     * Constructor
     *
     * @param array $config Route configuration
     */
    public function __construct(array $config)
    {
        $this->env = sanitize_key($config['env'] ?? 'pub');
        $this->module = !empty($config['module']) ? sanitize_file_name($config['module']) : null;
        $this->controller = sanitize_file_name($config['controller'] ?? '');
        $this->action = sanitize_key($config['action'] ?? 'index');
        $this->params = $this->parseParams($config['params'] ?? '');
        $this->renderMode = $config['render_mode'] ?? 'api';
    }

    /**
     * Dispatch the request to the appropriate controller
     *
     * @return mixed Response from controller or void
     */
    public function dispatch(): mixed
    {
        // Validate environment
        if (!in_array($this->env, ['pub', 'admin', 'resource'], true)) {
            return $this->error('Invalid environment', 400);
        }

        // Security checks for admin environment
        if ($this->env === 'admin') {
            if (!is_user_logged_in()) {
                return $this->error('Authentication required', 401);
            }

            // Verify nonce for state-changing requests
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $nonce = $_REQUEST['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
                if (!wp_verify_nonce($nonce, 'jmvc_admin')) {
                    return $this->error('Invalid security token', 403);
                }
            }
        }

        // Load controller
        $controllerInstance = JController::load($this->controller, $this->env, $this->module);

        if (!$controllerInstance) {
            return $this->error('Controller not found: ' . esc_html($this->controller), 404);
        }

        if (!method_exists($controllerInstance, $this->action)) {
            return $this->error('Action not found: ' . esc_html($this->action), 404);
        }

        // Execute based on render mode
        return match ($this->renderMode) {
            'page' => $this->renderAsPage($controllerInstance),
            'block' => $this->renderAsBlock($controllerInstance),
            default => $this->renderAsApi($controllerInstance),
        };
    }

    /**
     * Render controller output as API response (existing behavior)
     *
     * @param object $controller Controller instance
     */
    private function renderAsApi(object $controller): void
    {
        // Set JSON content type header if not already set
        if (!headers_sent() && $this->renderMode === 'api') {
            header('Content-Type: application/json; charset=utf-8');
        }

        ob_start();
        $result = call_user_func_array([$controller, $this->action], $this->params);
        $output = ob_get_clean();

        if ($result !== null) {
            if (is_array($result) || is_object($result)) {
                wp_send_json($result);
            } else {
                echo $result;
            }
        } elseif (!empty($output)) {
            // If output looks like JSON, send as-is
            if ($this->isJson($output)) {
                echo $output;
            } else {
                // Otherwise wrap in JSON
                wp_send_json(['html' => $output]);
            }
        } else {
            wp_send_json(['success' => true]);
        }
    }

    /**
     * Render controller output wrapped in WordPress theme
     *
     * @param object $controller Controller instance
     */
    private function renderAsPage(object $controller): void
    {
        // Allow controller to set up page before rendering
        if (method_exists($controller, 'beforeRender')) {
            $controller->beforeRender();
        }

        // Capture controller output
        ob_start();
        $result = call_user_func_array([$controller, $this->action], $this->params);
        $output = ob_get_clean();

        // Determine content to display
        if ($result !== null) {
            if (is_string($result)) {
                $content = $result;
            } elseif (is_array($result)) {
                // Controller returned data array - render default layout view
                $content = JView::get('_layouts/page', $result);
            } else {
                $content = '';
            }
        } else {
            $content = $output ?: '';
        }

        // Store content for template access
        JBag::set('jmvc_page_content', $content);

        // Check for custom page template on controller
        $template = $this->getPageTemplate($controller);

        if ($template && file_exists($template)) {
            include $template;
        } else {
            // Default: output with theme header/footer
            $this->renderWithTheme($content, $controller);
        }
    }

    /**
     * Render content with WordPress theme header and footer
     *
     * @param string $content Page content
     * @param object $controller Controller instance
     */
    private function renderWithTheme(string $content, object $controller): void
    {
        // Set up title filter if controller has pageTitle
        if (property_exists($controller, 'pageTitle') && !empty($controller->pageTitle)) {
            add_filter('document_title_parts', function ($title) use ($controller) {
                $title['title'] = $controller->pageTitle;
                return $title;
            });
        }

        // Add body classes if controller has bodyClasses
        if (property_exists($controller, 'bodyClasses') && !empty($controller->bodyClasses)) {
            add_filter('body_class', function ($classes) use ($controller) {
                return array_merge($classes, (array) $controller->bodyClasses);
            });
        }

        // Get header
        get_header();

        // Output main content area
        echo '<main id="jmvc-content" class="jmvc-page-content">';
        echo $content;
        echo '</main>';

        // Get footer
        get_footer();
    }

    /**
     * Render controller output as Gutenberg block content
     *
     * @param object $controller Controller instance
     * @return string Block HTML
     */
    private function renderAsBlock(object $controller): string
    {
        // Get block context from JBag
        $attributes = JBag::get('jmvc_block_attributes') ?? [];
        $innerContent = JBag::get('jmvc_block_content') ?? '';

        ob_start();
        $result = call_user_func_array(
            [$controller, $this->action],
            [$attributes, $innerContent]
        );
        $output = ob_get_clean();

        if ($result !== null && is_string($result)) {
            return $result;
        }

        return $output ?: '';
    }

    /**
     * Get custom page template if defined on controller
     *
     * @param object $controller Controller instance
     * @return string|null Template path or null
     */
    private function getPageTemplate(object $controller): ?string
    {
        if (!property_exists($controller, 'pageTemplate') || empty($controller->pageTemplate)) {
            return null;
        }

        $templateName = sanitize_file_name($controller->pageTemplate);

        // Check theme's jmvc templates directory first
        $themePath = get_stylesheet_directory() . '/jmvc/templates/' . $templateName . '.php';
        if (file_exists($themePath)) {
            return $themePath;
        }

        // Check plugin templates directory
        $pluginPath = JMVC_PLUGIN_PATH . 'templates/' . $templateName . '.php';
        if (file_exists($pluginPath)) {
            return $pluginPath;
        }

        return null;
    }

    /**
     * Parse URL params string into array
     *
     * @param string $paramsString URL params string
     * @return array<int, string> Parsed parameters
     */
    private function parseParams(string $paramsString): array
    {
        if (empty($paramsString)) {
            return [];
        }

        $params = array_filter(
            explode('/', $paramsString),
            fn($p) => $p !== ''
        );

        return array_map('sanitize_text_field', array_values($params));
    }

    /**
     * Return error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    private function error(string $message, int $code): void
    {
        status_header($code);

        if ($this->renderMode === 'api') {
            wp_send_json_error(['message' => $message], $code);
        } elseif ($this->renderMode === 'page') {
            // Set 404 for page mode
            global $wp_query;
            if ($code === 404 && $wp_query) {
                $wp_query->set_404();
            }

            get_header();
            echo '<main id="jmvc-content" class="jmvc-error-content">';
            echo '<h1>' . esc_html__('Error', 'jmvc') . '</h1>';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</main>';
            get_footer();
        } else {
            echo '<!-- JMVC Error: ' . esc_html($message) . ' -->';
        }

        exit;
    }

    /**
     * Check if a string is valid JSON
     *
     * @param string $string String to check
     * @return bool True if valid JSON
     */
    private function isJson(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
