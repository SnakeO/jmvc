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
     * Get instructions for fixing rewrite rules
     *
     * @return string
     */
    public static function getInstructions(): string
    {
        $permalinksUrl = admin_url('options-permalink.php');

        return <<<HTML
<h4>Flush Permalinks Required</h4>
<p>JMVC uses WordPress's native Rewrite API - no manual server configuration needed.</p>
<p>To fix routing issues:</p>
<ol>
    <li>Go to <a href="{$permalinksUrl}"><strong>Settings → Permalinks</strong></a></li>
    <li>Click <strong>Save Changes</strong> (this flushes rewrite rules)</li>
</ol>
<p>Routes like <code>/controller/pub/Task/index</code> will work automatically on any server.</p>
HTML;
    }
}
