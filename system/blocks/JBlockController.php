<?php

declare(strict_types=1);

/**
 * JMVC Block Controller Base Class
 *
 * Extend this class for controllers that render Gutenberg blocks.
 * Provides helper methods for accessing block attributes and rendering views.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class JBlockController
{
    /**
     * Current block attributes
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Inner block content
     */
    protected string $innerContent = '';

    /**
     * Is this being rendered in the editor?
     */
    protected bool $isEditor = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get block context from JBag
        $this->attributes = JBag::get('jmvc_block_attributes') ?? [];
        $this->innerContent = JBag::get('jmvc_block_content') ?? '';
        $this->isEditor = defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Get block attribute with default value
     *
     * @param string $key Attribute key
     * @param mixed $default Default value if attribute not set
     * @return mixed Attribute value
     */
    protected function attr(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all block attributes
     *
     * @return array<string, mixed> All attributes
     */
    protected function attrs(): array
    {
        return $this->attributes;
    }

    /**
     * Check if attribute exists and is not empty
     *
     * @param string $key Attribute key
     * @return bool True if attribute exists and is not empty
     */
    protected function hasAttr(string $key): bool
    {
        return isset($this->attributes[$key]) && $this->attributes[$key] !== '';
    }

    /**
     * Get inner block content
     *
     * @return string Inner content HTML
     */
    protected function content(): string
    {
        return $this->innerContent;
    }

    /**
     * Check if inner content exists
     *
     * @return bool True if has inner content
     */
    protected function hasContent(): bool
    {
        return !empty(trim($this->innerContent));
    }

    /**
     * Check if rendering in editor context
     *
     * @return bool True if in editor
     */
    protected function inEditor(): bool
    {
        return $this->isEditor;
    }

    /**
     * Check if rendering on frontend
     *
     * @return bool True if on frontend
     */
    protected function onFrontend(): bool
    {
        return !$this->isEditor;
    }

    /**
     * Render a view for this block
     *
     * @param string $viewName View name (relative to views directory)
     * @param array<string, mixed> $data Additional data for view
     * @param string|false $module HMVC module name or false for global scope
     * @return string Rendered HTML
     */
    protected function blockView(string $viewName, array $data = [], string|false $module = false): string
    {
        // Merge in block context
        $data = array_merge([
            'attributes' => $this->attributes,
            'innerContent' => $this->innerContent,
            'isEditor' => $this->isEditor,
        ], $data);

        return JView::get($viewName, $data, $module);
    }

    /**
     * Render placeholder for editor when data is missing
     *
     * @param string $message Placeholder message
     * @param string $icon Dashicon name (without dashicons- prefix)
     * @return string Placeholder HTML
     */
    protected function placeholder(string $message, string $icon = 'admin-post'): string
    {
        if (!$this->isEditor) {
            return '';
        }

        return sprintf(
            '<div class="jmvc-block-placeholder">
                <span class="dashicons dashicons-%s"></span>
                <p>%s</p>
            </div>',
            esc_attr($icon),
            esc_html($message)
        );
    }

    /**
     * Render loading state for editor
     *
     * @param string $message Loading message
     * @return string Loading HTML
     */
    protected function loading(string $message = ''): string
    {
        if (!$this->isEditor) {
            return '';
        }

        $message = $message ?: __('Loading...', 'jmvc');

        return sprintf(
            '<div class="jmvc-block-loading">
                <span class="spinner is-active"></span>
                <p>%s</p>
            </div>',
            esc_html($message)
        );
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML (only in editor) or empty (frontend)
     */
    protected function error(string $message): string
    {
        if (!$this->isEditor) {
            return '<!-- JMVC Block Error: ' . esc_html($message) . ' -->';
        }

        return sprintf(
            '<div class="jmvc-block-error">
                <span class="dashicons dashicons-warning"></span>
                <p>%s</p>
            </div>',
            esc_html($message)
        );
    }

    /**
     * Wrap block content with standard wrapper
     *
     * @param string $content Block content HTML
     * @param array<string, mixed> $wrapperAttrs Wrapper element attributes
     * @return string Wrapped content
     */
    protected function wrap(string $content, array $wrapperAttrs = []): string
    {
        $defaultClass = 'jmvc-block';

        // Build class string
        $classes = [$defaultClass];
        if (isset($wrapperAttrs['class'])) {
            $classes[] = $wrapperAttrs['class'];
            unset($wrapperAttrs['class']);
        }

        // Add alignment class if set
        if ($this->hasAttr('align')) {
            $classes[] = 'align' . sanitize_html_class($this->attr('align'));
        }

        // Build attributes string
        $attrStr = 'class="' . esc_attr(implode(' ', $classes)) . '"';

        // Add anchor ID if set
        if ($this->hasAttr('anchor')) {
            $attrStr .= ' id="' . esc_attr($this->attr('anchor')) . '"';
        }

        // Add any additional attributes
        foreach ($wrapperAttrs as $name => $value) {
            $attrStr .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }

        return sprintf('<div %s>%s</div>', $attrStr, $content);
    }

    /**
     * Get WordPress block wrapper attributes
     * Use this for compatibility with block.json-based blocks
     *
     * @param array<string, mixed> $extra Extra attributes to merge
     * @return string Attributes string for wrapper element
     */
    protected function getBlockWrapperAttributes(array $extra = []): string
    {
        if (function_exists('get_block_wrapper_attributes')) {
            return get_block_wrapper_attributes($extra);
        }

        // Fallback for older WordPress versions
        $classes = ['wp-block-jmvc'];
        if (isset($extra['class'])) {
            $classes[] = $extra['class'];
            unset($extra['class']);
        }

        $attrs = 'class="' . esc_attr(implode(' ', $classes)) . '"';
        foreach ($extra as $name => $value) {
            $attrs .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }

        return $attrs;
    }

    /**
     * Check if current user can edit this block
     *
     * @return bool True if user can edit
     */
    protected function canEdit(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Escape output for block content
     *
     * @param string $content Content to escape
     * @return string Escaped content
     */
    protected function esc(string $content): string
    {
        return esc_html($content);
    }

    /**
     * Allow limited HTML in output
     *
     * @param string $content Content to filter
     * @return string Filtered content
     */
    protected function kses(string $content): string
    {
        return wp_kses_post($content);
    }
}
