<?php
/**
 * JMVC Health Controller
 *
 * Provides health check endpoint for testing rewrite rules
 *
 * @package JMVC
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Internal JMVC controller for health checks
 */
class JmvcController extends APIController
{
    /**
     * Health check endpoint
     *
     * Used by admin panel to verify rewrite rules are working
     * URL: /controller/jmvc/health
     */
    public function health(): void
    {
        $this->api_success([
            'status' => 'jmvc_ok',
            'version' => JMVC_VERSION,
            'timestamp' => time(),
        ]);
    }

    /**
     * Version info endpoint
     *
     * URL: /controller/jmvc/version
     */
    public function version(): void
    {
        $this->api_success([
            'version' => JMVC_VERSION,
            'php' => PHP_VERSION,
            'wordpress' => get_bloginfo('version'),
        ]);
    }
}
