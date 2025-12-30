<?php
/**
 * JMVC Logging System
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class JLog
{
    /**
     * Whether to send logs to Slack
     *
     * @var bool
     */
    public static $should_slack = true;

    /**
     * Flash log messages for this request
     *
     * @var array
     */
    public static $flash_log = array();

    /**
     * Initialize the logging system
     */
    public static function init()
    {
        register_shutdown_function(array(__CLASS__, 'slackOutLog'));
    }

    /**
     * Send accumulated logs to Slack on shutdown
     */
    public static function slackOutLog()
    {
        if (static::$should_slack && count(static::$flash_log) > 0) {
            DevAlert::slack('JLog', implode("\n", static::$flash_log));
        }
    }

    /**
     * Log a message
     *
     * @param string $which Log level/category
     * @param string $msg Log message
     * @param mixed $deets Additional details
     */
    public static function log($which, $msg, $deets = '')
    {
        $kvstore = JBag::get('kvstore');

        if (!$kvstore) {
            error_log(sprintf('[JMVC %s] %s %s', $which, $msg, print_r($deets, true)));
            return;
        }

        $log = $kvstore->get('Jlog/' . $which);
        $log = $log ?: '';

        $logline = sprintf(
            '%s [%s] - %s %s',
            gmdate('Y-m-d H:i:s'),
            esc_html($which),
            esc_html($msg),
            is_string($deets) ? esc_html($deets) : print_r($deets, true)
        );

        // Store to log
        $log .= $logline . "\n";
        $kvstore->set('Jlog/' . $which, $log);
        static::$flash_log[] = $logline;
    }

    /**
     * Log an info message
     *
     * @param string $msg Message
     * @param mixed $deets Details
     */
    public static function info($msg, $deets = '')
    {
        self::log('info', $msg, $deets);
    }

    /**
     * Log a warning message
     *
     * @param string $msg Message
     * @param mixed $deets Details
     */
    public static function warn($msg, $deets = '')
    {
        self::log('warn', $msg, $deets);
    }

    /**
     * Log an error message
     *
     * @param string $msg Message
     * @param mixed $deets Details
     */
    public static function error($msg, $deets = '')
    {
        self::log('error', $msg, $deets);
    }
}
