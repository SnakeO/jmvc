<?php
/**
 * JMVC DevAlert Configuration
 *
 * Configure alerts for Slack and email notifications.
 *
 * Set these constants in wp-config.php:
 *   define('JMVC_DEVALERT_EMAIL', 'your@email.com');
 *   define('JMVC_SLACK_WEBHOOK', 'https://hooks.slack.com/services/...');
 *   define('JMVC_SLACK_CHANNEL', '#devalerts');
 *   define('JMVC_SLACK_USERNAME', 'Alert Bot');
 *
 * Or use environment variables:
 *   JMVC_DEVALERT_EMAIL
 *   JMVC_SLACK_WEBHOOK
 *   JMVC_SLACK_CHANNEL
 *   JMVC_SLACK_USERNAME
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get configuration value from constant or environment variable
 *
 * @param string $name The constant/env var name
 * @param mixed $default Default value if not set
 * @return mixed The configuration value
 */
function jmvc_get_config($name, $default = '') {
    if (defined($name)) {
        return constant($name);
    }

    $env_value = getenv($name);
    if ($env_value !== false) {
        return $env_value;
    }

    return $default;
}

JConfig::set('devalert', array(
    'mail' => array(
        'email' => jmvc_get_config('JMVC_DEVALERT_EMAIL', ''),
    ),
    'slack' => array(
        'username' => jmvc_get_config('JMVC_SLACK_USERNAME', 'JMVC Alert'),
        'channel'  => jmvc_get_config('JMVC_SLACK_CHANNEL', '#devalerts'),
        'endpoint' => jmvc_get_config('JMVC_SLACK_WEBHOOK', ''),
    ),
));
