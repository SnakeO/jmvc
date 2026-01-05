<?php

declare(strict_types=1);

/**
 * JMVC Gutenberg Block Integration
 *
 * Provides a system for registering Gutenberg blocks that are
 * powered by JMVC controllers for server-side rendering.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JBlock
{
    /**
     * Registered blocks
     *
     * @var array<string, array>
     */
    private static array $blocks = [];

    /**
     * Whether the system has been initialized
     */
    private static bool $initialized = false;

    /**
     * Initialize block system
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        add_action('init', [self::class, 'registerBlocks'], 20);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorAssets']);
        add_filter('block_categories_all', [self::class, 'registerBlockCategory']);

        self::$initialized = true;
    }

    /**
     * Register a JMVC-powered block
     *
     * @param string $blockName Block name (e.g., 'jmvc/task-list')
     * @param array $config Block configuration
     */
    public static function register(string $blockName, array $config): void
    {
        // Ensure block name has namespace
        if (strpos($blockName, '/') === false) {
            $blockName = 'jmvc/' . $blockName;
        }

        self::$blocks[$blockName] = array_merge([
            'controller' => '',           // Controller class name (required)
            'action' => 'render',         // Action method to call
            'env' => 'pub',               // Environment
            'module' => null,             // HMVC module
            'attributes' => [],           // Block attributes schema
            'title' => '',                // Block title
            'description' => '',          // Block description
            'category' => 'jmvc-blocks',  // Block category
            'icon' => 'admin-generic',    // Dashicon name
            'keywords' => [],             // Search keywords
            'supports' => [
                'html' => false,
                'align' => ['wide', 'full'],
                'anchor' => true,
            ],
            'example' => [],              // Example data for preview
            'editor_script' => null,      // Custom editor script handle
            'editor_style' => null,       // Custom editor style handle
            'style' => null,              // Frontend style handle
        ], $config);
    }

    /**
     * Register block category
     *
     * @param array $categories Existing categories
     * @return array Modified categories
     */
    public static function registerBlockCategory(array $categories): array
    {
        // Check if category already exists
        foreach ($categories as $category) {
            if ($category['slug'] === 'jmvc-blocks') {
                return $categories;
            }
        }

        return array_merge($categories, [[
            'slug' => 'jmvc-blocks',
            'title' => __('JMVC Blocks', 'jmvc'),
            'icon' => 'layout',
        ]]);
    }

    /**
     * Register all blocks with WordPress
     */
    public static function registerBlocks(): void
    {
        foreach (self::$blocks as $name => $config) {
            self::registerSingleBlock($name, $config);
        }
    }

    /**
     * Register a single block
     *
     * @param string $name Block name
     * @param array $config Block configuration
     */
    private static function registerSingleBlock(string $name, array $config): void
    {
        $blockArgs = [
            'render_callback' => function ($attributes, $content) use ($config) {
                return self::renderBlock($config, $attributes, $content);
            },
            'attributes' => self::buildAttributesSchema($config['attributes']),
        ];

        // Add optional settings
        if (!empty($config['editor_script'])) {
            $blockArgs['editor_script'] = $config['editor_script'];
        }
        if (!empty($config['editor_style'])) {
            $blockArgs['editor_style'] = $config['editor_style'];
        }
        if (!empty($config['style'])) {
            $blockArgs['style'] = $config['style'];
        }
        if (!empty($config['supports'])) {
            $blockArgs['supports'] = $config['supports'];
        }

        register_block_type($name, $blockArgs);
    }

    /**
     * Build attributes schema from simplified config
     *
     * @param array $attributes Simplified attribute definitions
     * @return array WordPress block attributes schema
     */
    private static function buildAttributesSchema(array $attributes): array
    {
        $schema = [];

        foreach ($attributes as $key => $attr) {
            if (is_string($attr)) {
                // Shorthand: 'count' => 'number'
                $schema[$key] = ['type' => $attr];
            } elseif (is_array($attr)) {
                // Full schema - normalize it
                $normalized = [
                    'type' => $attr['type'] ?? 'string',
                ];

                if (isset($attr['default'])) {
                    $normalized['default'] = $attr['default'];
                }

                if (isset($attr['enum'])) {
                    $normalized['enum'] = $attr['enum'];
                }

                $schema[$key] = $normalized;
            }
        }

        return $schema;
    }

    /**
     * Render block via controller
     *
     * @param array $config Block configuration
     * @param array $attributes Block attributes
     * @param string $content Inner block content
     * @return string Rendered HTML
     */
    private static function renderBlock(array $config, array $attributes, string $content): string
    {
        // Store attributes for controller access
        JBag::set('jmvc_block_attributes', $attributes);
        JBag::set('jmvc_block_content', $content);

        // Load controller
        $controllerInstance = JController::load(
            $config['controller'],
            $config['env'],
            $config['module']
        );

        if (!$controllerInstance) {
            return self::renderError('Controller not found: ' . esc_html($config['controller']));
        }

        $action = $config['action'];
        if (!method_exists($controllerInstance, $action)) {
            return self::renderError('Action not found: ' . esc_html($action));
        }

        // Call controller action
        ob_start();
        $result = call_user_func([$controllerInstance, $action], $attributes, $content);
        $output = ob_get_clean();

        // Clean up
        JBag::set('jmvc_block_attributes', null);
        JBag::set('jmvc_block_content', null);

        // Handle return value or output
        if (is_string($result)) {
            return $result;
        }

        return $output ?: '';
    }

    /**
     * Render error message (only visible in editor)
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private static function renderError(string $message): string
    {
        // Only show detailed errors in editor context
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return sprintf(
                '<div class="jmvc-block-error" style="padding: 20px; background: #fee; border: 1px solid #c00; color: #900;">
                    <strong>JMVC Block Error:</strong> %s
                </div>',
                esc_html($message)
            );
        }

        return '<!-- JMVC Block Error: ' . esc_html($message) . ' -->';
    }

    /**
     * Enqueue editor assets for JMVC blocks
     */
    public static function enqueueEditorAssets(): void
    {
        if (empty(self::$blocks)) {
            return;
        }

        // Register and enqueue core JMVC block editor script
        wp_enqueue_script(
            'jmvc-blocks-editor',
            JMVC_PLUGIN_URL . 'assets/js/blocks-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
            JMVC_VERSION,
            true
        );

        // Pass block configurations to JavaScript
        $blocksForJs = [];
        foreach (self::$blocks as $name => $config) {
            $blocksForJs[$name] = [
                'title' => $config['title'] ?: $name,
                'description' => $config['description'],
                'category' => $config['category'],
                'icon' => $config['icon'],
                'keywords' => $config['keywords'],
                'supports' => $config['supports'],
                'attributes' => self::prepareAttributesForJs($config['attributes']),
                'example' => $config['example'],
            ];
        }

        wp_localize_script('jmvc-blocks-editor', 'jmvcBlocks', [
            'blocks' => $blocksForJs,
            'i18n' => [
                'blockSettings' => __('Block Settings', 'jmvc'),
            ],
        ]);

        // Editor styles
        wp_enqueue_style(
            'jmvc-blocks-editor',
            JMVC_PLUGIN_URL . 'assets/css/blocks-editor.css',
            [],
            JMVC_VERSION
        );
    }

    /**
     * Prepare attributes for JavaScript with UI metadata
     *
     * @param array $attributes Attribute definitions
     * @return array Attributes with UI metadata
     */
    private static function prepareAttributesForJs(array $attributes): array
    {
        $prepared = [];

        foreach ($attributes as $key => $attr) {
            if (is_string($attr)) {
                $prepared[$key] = [
                    'type' => $attr,
                    'label' => self::keyToLabel($key),
                ];
            } else {
                $prepared[$key] = array_merge([
                    'label' => self::keyToLabel($key),
                ], $attr);
            }
        }

        return $prepared;
    }

    /**
     * Convert attribute key to human-readable label
     *
     * @param string $key Attribute key
     * @return string Human-readable label
     */
    private static function keyToLabel(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Get registered blocks
     *
     * @return array<string, array> Registered blocks
     */
    public static function getBlocks(): array
    {
        return self::$blocks;
    }

    /**
     * Check if a block is registered
     *
     * @param string $name Block name
     * @return bool True if registered
     */
    public static function isRegistered(string $name): bool
    {
        return isset(self::$blocks[$name]);
    }
}
