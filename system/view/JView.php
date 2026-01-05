<?php

declare(strict_types=1);

/**
 * JMVC View System
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JView
{
    /**
     * Current HMVC module
     */
    public static ?string $module = null;

    /**
     * Display a view
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @param string|false $force_module Force specific module
     */
    public static function show(string $view, array $data = [], string|false $force_module = false): void
    {
        echo self::get($view, $data, $force_module);
    }

    /**
     * Get view content as string
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @param string|false $force_module Force specific module
     * @return string Rendered view content
     * @throws Exception If view not found or data is invalid
     */
    public static function get(string $view, array $data = [], string|false $force_module = false): string
    {
        if (!is_array($data)) {
            throw new Exception(sprintf('Data for view %s is not an array.', esc_html($view)));
        }

        // Sanitize view name to prevent directory traversal
        $view = sanitize_file_name(str_replace('/', DIRECTORY_SEPARATOR, $view));
        $view = str_replace(DIRECTORY_SEPARATOR, '/', $view);

        $module = $force_module !== false ? $force_module : self::$module;
        $view_pathinfo = pathinfo($view);

        // Check HMVC module first
        if ($module) {
            $module = sanitize_file_name($module);
            $viewdir = dirname(__FILE__) . '/../../modules/' . $module . '/views';
            $fullview = $viewdir . '/' . $view . '.php';
            $view_url = JMVC_URL . 'modules/' . $module . '/views/' . ($view_pathinfo['dirname'] ? $view_pathinfo['dirname'] . '/' : '');
        }

        // If the module's view doesn't exist, try in the global scope
        if (!$module || !file_exists($fullview)) {
            $viewdir = dirname(__FILE__) . '/../../views';
            $fullview = $viewdir . '/' . $view . '.php';
            $view_url = JMVC_URL . 'views/' . ($view_pathinfo['dirname'] ? $view_pathinfo['dirname'] . '/' : '');
        }

        if (!file_exists($fullview)) {
            throw new Exception(sprintf('View not found: %s', esc_html($fullview)));
        }

        // Verify the view file is within allowed directories
        $real_fullview = realpath($fullview);
        $real_viewdir = realpath(dirname(__FILE__) . '/../../');
        if ($real_fullview === false || strpos($real_fullview, $real_viewdir) !== 0) {
            throw new Exception('Invalid view path.');
        }

        // Set up view context - explicitly assign variables instead of using extract()
        // This prevents variable injection vulnerabilities
        $__jmvc_view_data = $data;
        $__jmvc_view_url = $view_url;
        $__jmvc_fullview = $fullview;

        // Output buffer to capture view
        ob_start();

        // Create a closure to isolate variable scope and prevent pollution
        $__jmvc_render = function (string $__file, array $__data, string $view_url): void {
            // Make data available as individual variables
            foreach ($__data as $__key => $__value) {
                // Skip reserved variable names
                if (in_array($__key, array('__file', '__data', '__key', '__value', 'view_url', 'this'), true)) {
                    continue;
                }
                $$__key = $__value;
            }
            unset($__key, $__value, $__data);

            include $__file;
        };

        $__jmvc_render($__jmvc_fullview, $__jmvc_view_data, $__jmvc_view_url);

        return ob_get_clean() ?: '';
    }

    /**
     * Render view within WordPress theme (with header/footer)
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @param array $options Rendering options (title, body_class, template)
     * @param string|false $force_module Force specific module
     */
    public static function showPage(string $view, array $data = [], array $options = [], string|false $force_module = false): void
    {
        $defaults = [
            'template' => null,      // Custom template file
            'title' => '',           // Page title
            'body_class' => [],      // Additional body classes
        ];

        $options = array_merge($defaults, $options);

        // Capture view content
        $content = self::get($view, $data, $force_module);

        // Store content for template access
        JBag::set('jmvc_page_content', $content);
        JBag::set('jmvc_page_title', $options['title']);

        // Add title filter
        if (!empty($options['title'])) {
            add_filter('document_title_parts', function ($title) use ($options) {
                $title['title'] = $options['title'];
                return $title;
            });
        }

        // Add body classes
        if (!empty($options['body_class'])) {
            add_filter('body_class', function ($classes) use ($options) {
                return array_merge($classes, (array) $options['body_class']);
            });
        }

        // Use custom template or default
        if (!empty($options['template'])) {
            // Check theme's jmvc templates directory
            $template_path = get_stylesheet_directory() . '/jmvc/templates/' . sanitize_file_name($options['template']) . '.php';

            if (file_exists($template_path)) {
                include $template_path;
                return;
            }

            // Check plugin templates
            $plugin_template = JMVC_PLUGIN_PATH . 'templates/' . sanitize_file_name($options['template']) . '.php';
            if (file_exists($plugin_template)) {
                include $plugin_template;
                return;
            }
        }

        // Default: wrap in theme header/footer
        get_header();
        echo '<main id="jmvc-content" class="jmvc-page-content">';
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</main>';
        get_footer();
    }
}
