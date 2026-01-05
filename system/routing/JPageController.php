<?php

declare(strict_types=1);

/**
 * JMVC Page Controller Base Class
 *
 * Extend this class for controllers that render full WordPress pages
 * with theme integration (header/footer from active theme).
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class JPageController
{
    /**
     * Page template to use (without .php extension)
     * Set to null for default header/footer wrapping
     */
    public ?string $pageTemplate = null;

    /**
     * Page title for wp_title and <title> tag
     */
    public string $pageTitle = '';

    /**
     * Body classes to add
     *
     * @var array<int, string>
     */
    public array $bodyClasses = ['jmvc-page'];

    /**
     * Scripts to enqueue
     *
     * @var array<string, array{src: string, deps?: array, ver?: string, in_footer?: bool}>
     */
    protected array $scripts = [];

    /**
     * Styles to enqueue
     *
     * @var array<string, array{src: string, deps?: array, ver?: string, media?: string}>
     */
    protected array $styles = [];

    /**
     * Data to pass to JavaScript
     *
     * @var array<string, mixed>
     */
    protected array $jsData = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Register hooks for page rendering
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 20);
    }

    /**
     * Called before rendering - use for setup
     */
    public function beforeRender(): void
    {
        // Override in child classes for setup logic
    }

    /**
     * Enqueue registered assets
     */
    public function enqueueAssets(): void
    {
        // Enqueue styles
        foreach ($this->styles as $handle => $style) {
            wp_enqueue_style(
                $handle,
                $style['src'],
                $style['deps'] ?? [],
                $style['ver'] ?? JMVC_VERSION,
                $style['media'] ?? 'all'
            );
        }

        // Enqueue scripts
        foreach ($this->scripts as $handle => $script) {
            wp_enqueue_script(
                $handle,
                $script['src'],
                $script['deps'] ?? [],
                $script['ver'] ?? JMVC_VERSION,
                $script['in_footer'] ?? true
            );
        }

        // Localize script data
        if (!empty($this->jsData)) {
            $firstScript = array_key_first($this->scripts);
            if ($firstScript) {
                wp_localize_script($firstScript, 'jmvcPageData', $this->jsData);
            }
        }
    }

    /**
     * Set page title
     *
     * @param string $title Page title
     * @return static
     */
    protected function setTitle(string $title): static
    {
        $this->pageTitle = $title;
        return $this;
    }

    /**
     * Add body class
     *
     * @param string $class CSS class name
     * @return static
     */
    protected function addClass(string $class): static
    {
        $this->bodyClasses[] = sanitize_html_class($class);
        return $this;
    }

    /**
     * Add multiple body classes
     *
     * @param array<int, string> $classes CSS class names
     * @return static
     */
    protected function addClasses(array $classes): static
    {
        foreach ($classes as $class) {
            $this->addClass($class);
        }
        return $this;
    }

    /**
     * Set page template
     *
     * @param string $template Template name (without .php)
     * @return static
     */
    protected function setTemplate(string $template): static
    {
        $this->pageTemplate = $template;
        return $this;
    }

    /**
     * Add script to enqueue
     *
     * @param string $handle Script handle
     * @param string $src Script URL
     * @param array $deps Dependencies
     * @param bool $inFooter Load in footer
     * @return static
     */
    protected function addScript(string $handle, string $src, array $deps = [], bool $inFooter = true): static
    {
        $this->scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'in_footer' => $inFooter,
        ];
        return $this;
    }

    /**
     * Add style to enqueue
     *
     * @param string $handle Style handle
     * @param string $src Style URL
     * @param array $deps Dependencies
     * @param string $media Media query
     * @return static
     */
    protected function addStyle(string $handle, string $src, array $deps = [], string $media = 'all'): static
    {
        $this->styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media,
        ];
        return $this;
    }

    /**
     * Add data to pass to JavaScript
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return static
     */
    protected function addJsData(string $key, mixed $value): static
    {
        $this->jsData[$key] = $value;
        return $this;
    }

    /**
     * Render a view
     *
     * @param string $viewName View name
     * @param array<string, mixed> $data Data to pass to view
     * @param string|null $module HMVC module name
     */
    protected function view(string $viewName, array $data = [], ?string $module = null): void
    {
        JView::show($viewName, $data, $module);
    }

    /**
     * Render a view and return as string
     *
     * @param string $viewName View name
     * @param array<string, mixed> $data Data to pass to view
     * @param string|null $module HMVC module name
     * @return string Rendered view HTML
     */
    protected function render(string $viewName, array $data = [], ?string $module = null): string
    {
        return JView::get($viewName, $data, $module);
    }

    /**
     * Redirect to another URL
     *
     * @param string $url URL to redirect to
     * @param int $status HTTP status code (301, 302, etc.)
     */
    protected function redirect(string $url, int $status = 302): never
    {
        wp_safe_redirect($url, $status);
        exit;
    }

    /**
     * Redirect to a page controller action
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param array $params URL parameters
     * @param int $status HTTP status code
     */
    protected function redirectToAction(string $controller, string $action = 'index', array $params = [], int $status = 302): never
    {
        $url = JRewrite::pageUrl($controller, $action, $params);
        $this->redirect($url, $status);
    }

    /**
     * Return 404 not found
     *
     * @param string $message Optional error message
     */
    protected function notFound(string $message = ''): void
    {
        global $wp_query;

        if ($wp_query) {
            $wp_query->set_404();
        }

        status_header(404);

        if (!empty($message)) {
            echo '<div class="jmvc-error jmvc-not-found">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Check if current user has capability
     *
     * @param string $capability WordPress capability
     * @return bool True if user has capability
     */
    protected function can(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * Require user to be logged in
     *
     * @param string $redirectTo URL to redirect to after login
     */
    protected function requireLogin(?string $redirectTo = null): void
    {
        if (!is_user_logged_in()) {
            $redirectTo = $redirectTo ?? $this->getCurrentUrl();
            wp_safe_redirect(wp_login_url($redirectTo));
            exit;
        }
    }

    /**
     * Require user to have capability
     *
     * @param string $capability WordPress capability
     */
    protected function requireCapability(string $capability): void
    {
        $this->requireLogin();

        if (!current_user_can($capability)) {
            $this->setTitle(__('Access Denied', 'jmvc'));
            echo '<div class="jmvc-error jmvc-access-denied">';
            echo '<h2>' . esc_html__('Access Denied', 'jmvc') . '</h2>';
            echo '<p>' . esc_html__('You do not have permission to access this page.', 'jmvc') . '</p>';
            echo '</div>';
            exit;
        }
    }

    /**
     * Get current request URL
     *
     * @return string Current URL
     */
    protected function getCurrentUrl(): string
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        return $protocol . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    }

    /**
     * Get request input (GET or POST)
     *
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed Input value
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        if (isset($_POST[$key])) {
            return sanitize_text_field(wp_unslash($_POST[$key]));
        }

        if (isset($_GET[$key])) {
            return sanitize_text_field(wp_unslash($_GET[$key]));
        }

        return $default;
    }

    /**
     * Get all request input
     *
     * @return array<string, mixed> All input data
     */
    protected function allInput(): array
    {
        $input = array_merge($_GET, $_POST);
        return array_map(function ($value) {
            if (is_array($value)) {
                return array_map('sanitize_text_field', $value);
            }
            return sanitize_text_field(wp_unslash($value));
        }, $input);
    }

    /**
     * Verify nonce
     *
     * @param string $action Nonce action
     * @param string $name Nonce field name
     * @return bool True if valid
     */
    protected function verifyNonce(string $action, string $name = '_wpnonce'): bool
    {
        $nonce = $this->input($name);
        return $nonce && wp_verify_nonce($nonce, $action);
    }

    /**
     * Create nonce field HTML
     *
     * @param string $action Nonce action
     * @return string Nonce field HTML
     */
    protected function nonceField(string $action): string
    {
        return wp_nonce_field($action, '_wpnonce', true, false);
    }
}
