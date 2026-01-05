<?php
/**
 * JMVC RewriteTest - Rewrite rules checker
 *
 * @package JMVC\Admin
 */

declare(strict_types=1);

namespace JMVC\Admin;

/**
 * Tests if rewrite rules are properly configured
 */
class RewriteTest
{
    /**
     * Test if rewrite rules are working
     *
     * @return array{status: bool, error?: string, code?: int}
     */
    public static function check(): array
    {
        // Build test URL
        $testUrl = site_url('/controller/jmvc/health');

        // Make request with short timeout
        $response = wp_remote_get($testUrl, [
            'timeout' => 5,
            'sslverify' => false,
            'redirection' => 0,
        ]);

        if (is_wp_error($response)) {
            return [
                'status' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Check for successful response with our marker
        $success = ($code === 200 && strpos($body, 'jmvc_ok') !== false);

        return [
            'status' => $success,
            'code' => $code,
        ];
    }

    /**
     * Detect server type
     *
     * @return string 'apache'|'nginx'|'litespeed'|'unknown'
     */
    public static function getServerType(): string
    {
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if (stripos($server, 'apache') !== false) {
            return 'apache';
        }

        if (stripos($server, 'nginx') !== false) {
            return 'nginx';
        }

        if (stripos($server, 'litespeed') !== false) {
            return 'litespeed';
        }

        return 'unknown';
    }

    /**
     * Get installation instructions based on server type
     *
     * @return string
     */
    public static function getInstructions(): string
    {
        $serverType = self::getServerType();

        switch ($serverType) {
            case 'apache':
            case 'litespeed':
                return self::getApacheInstructions();

            case 'nginx':
                return self::getNginxInstructions();

            default:
                return self::getGenericInstructions();
        }
    }

    /**
     * Get Apache/LiteSpeed .htaccess instructions
     */
    private static function getApacheInstructions(): string
    {
        $htaccessPath = ABSPATH . '.htaccess';

        return <<<HTML
<h4>Apache / LiteSpeed Instructions</h4>
<p>Add the following rules to your <code>.htaccess</code> file (located at <code>{$htaccessPath}</code>),
   <strong>before</strong> the WordPress rewrite rules:</p>
<pre><code># JMVC Controller Routing
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine On
RewriteBase /

# JMVC Clean URLs
RewriteRule ^controller/(.*)$ /wp-admin/admin-ajax.php?action=pub_controller&path=$1 [L,QSA]
RewriteRule ^admin_controller/(.*)$ /wp-admin/admin-ajax.php?action=admin_controller&path=$1 [L,QSA]
RewriteRule ^resource_controller/(.*)$ /wp-admin/admin-ajax.php?action=resource_controller&path=$1 [L,QSA]

# HMVC Module Routing
RewriteRule ^hmvc_controller/(.*)$ /wp-admin/admin-ajax.php?action=hmvc_controller&path=$1 [L,QSA]
&lt;/IfModule&gt;
# END JMVC</code></pre>
<p><strong>Note:</strong> Make sure <code>mod_rewrite</code> is enabled on your server.</p>
HTML;
    }

    /**
     * Get NGINX instructions
     */
    private static function getNginxInstructions(): string
    {
        return <<<HTML
<h4>NGINX Instructions</h4>
<p>Add the following location blocks to your NGINX configuration file (usually in <code>/etc/nginx/sites-available/</code>),
   <strong>inside</strong> your server block:</p>
<pre><code># JMVC Controller Routing
location /controller/ {
    rewrite ^/controller/(.*)$ /wp-admin/admin-ajax.php?action=pub_controller&path=$1 last;
}

location /admin_controller/ {
    rewrite ^/admin_controller/(.*)$ /wp-admin/admin-ajax.php?action=admin_controller&path=$1 last;
}

location /resource_controller/ {
    rewrite ^/resource_controller/(.*)$ /wp-admin/admin-ajax.php?action=resource_controller&path=$1 last;
}

# HMVC Module Routing
location /hmvc_controller/ {
    rewrite ^/hmvc_controller/(.*)$ /wp-admin/admin-ajax.php?action=hmvc_controller&path=$1 last;
}</code></pre>
<p><strong>After adding these rules, restart NGINX:</strong></p>
<pre><code>sudo nginx -t && sudo systemctl reload nginx</code></pre>
HTML;
    }

    /**
     * Get generic instructions
     */
    private static function getGenericInstructions(): string
    {
        return <<<HTML
<h4>Server Configuration Required</h4>
<p>Your server type could not be detected. You need to configure URL rewriting to map:</p>
<ul>
    <li><code>/controller/{path}</code> → <code>/wp-admin/admin-ajax.php?action=pub_controller&path={path}</code></li>
    <li><code>/admin_controller/{path}</code> → <code>/wp-admin/admin-ajax.php?action=admin_controller&path={path}</code></li>
    <li><code>/resource_controller/{path}</code> → <code>/wp-admin/admin-ajax.php?action=resource_controller&path={path}</code></li>
    <li><code>/hmvc_controller/{path}</code> → <code>/wp-admin/admin-ajax.php?action=hmvc_controller&path={path}</code></li>
</ul>
<p>Please consult your server's documentation for URL rewriting configuration.</p>
HTML;
    }

    /**
     * Check if .htaccess is writable (for Apache)
     *
     * @return bool
     */
    public static function isHtaccessWritable(): bool
    {
        $htaccessPath = ABSPATH . '.htaccess';

        if (!file_exists($htaccessPath)) {
            return is_writable(ABSPATH);
        }

        return is_writable($htaccessPath);
    }
}
