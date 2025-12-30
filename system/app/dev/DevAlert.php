<?php
/**
 * JMVC Developer Alert System
 *
 * Sends alerts to Slack or email for debugging and error tracking.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

use GuzzleHttp\Client as Guzzle;

class DevAlert
{
    /**
     * Pending async promises
     *
     * @var array
     */
    public static $promises = array();

    /**
     * Send an alert out to the site admins via email
     *
     * @param string $topic Alert subject
     * @param mixed $deets Details of error message
     */
    public static function mail($topic, $deets = '')
    {
        $body = self::constructBody($topic, $deets);
        $email = JConfig::get('devalert/mail/email');

        if ($email) {
            wp_mail(sanitize_email($email), sanitize_text_field($topic), $body);
        }
    }

    /**
     * Wait for all async DevAlert promises to complete
     */
    public static function waitForPromises()
    {
        foreach (self::$promises as $promise) {
            try {
                $promise->wait();
            } catch (Exception $e) {
                // Log but don't throw - we don't want alert failures to break the app
                error_log('DevAlert promise failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send slack alert out to the site admins
     *
     * @param string $topic Alert subject
     * @param mixed $deets Details of error message
     */
    public static function slack($topic, $deets = '')
    {
        try {
            $slack_config = JConfig::get('devalert/slack');

            if (empty($slack_config['endpoint'])) {
                error_log('DevAlert: Slack endpoint not configured');
                return;
            }

            $settings = array(
                'username'   => $slack_config['username'] ?? 'JMVC Alert',
                'channel'    => $slack_config['channel'] ?? '#devalerts',
                'link_names' => true,
            );

            $guzzle = new Guzzle();
            $endpoint = $slack_config['endpoint'];

            $client = new Maknz\Slack\Client($endpoint, $settings, $guzzle);
            $body = self::constructBody($topic, $deets);

            // Send async
            $message = $client->createMessage();
            $message->setText($body);

            $payload = $client->preparePayload($message);
            $encoded = wp_json_encode($payload);

            self::$promises[] = $guzzle->requestAsync('POST', $endpoint, array('body' => $encoded));
        } catch (Exception $e) {
            error_log('DevAlert Slack error: ' . $e->getMessage());
        }
    }

    /**
     * Send an alert (facade for easy switching between mail and slack)
     *
     * @param string $topic Alert subject
     * @param mixed $deets Details of error message
     */
    public static function send($topic, $deets = '')
    {
        self::slack($topic, $deets);
    }

    /**
     * Initialize the DevAlert system
     */
    public static function init()
    {
        register_shutdown_function(array(__CLASS__, 'waitForPromises'));

        add_action('wp_ajax_devalert', array(__CLASS__, 'ajax_handler'));
    }

    /**
     * Handle AJAX requests for devalert viewing
     */
    public static function ajax_handler()
    {
        // Only admins can view dev alerts
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'), 403);
            exit;
        }

        $kvstore = JBag::get('kvstore');
        $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

        if (empty($id)) {
            wp_send_json_error(array('message' => 'No ID specified'), 400);
            exit;
        }

        $res = $kvstore->get($id);

        if ($res) {
            // Output is HTML content stored by constructBody
            echo wp_kses_post($res);
        } else {
            echo esc_html('DevAlert not found');
        }

        exit;
    }

    /**
     * Construct the alert body with debugging information
     *
     * @param string $topic Alert subject
     * @param mixed $deets Details of error message
     * @return string Formatted message body
     */
    private static function constructBody($topic, $deets = '')
    {
        $uid = uniqid('devalert_', true);

        $deets_output = $deets;

        if ($deets instanceof Exception) {
            $deets_output = array(
                'message' => $deets->getMessage(),
                'file'    => $deets->getFile(),
                'line'    => $deets->getLine(),
                'trace'   => $deets->getTraceAsString(),
            );
        }

        // Convenient to pass in an array with the keys as the headings
        if (is_array($deets_output)) {
            $formatted = '';
            foreach ($deets_output as $heading => $content) {
                if (!is_string($content)) {
                    $content = print_r($content, true);
                }
                $formatted .= "\n\n=======" . esc_html($heading) . "======\n" . esc_html($content);
            }
            $deets_output = $formatted;
        } else {
            $deets_output = esc_html($deets_output);
        }

        $msg = '<pre>';

        // Topic + details
        $msg .= esc_html($topic) . "\n\n";
        $msg .= $deets_output;

        // Add in global debugging info (sanitized)
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : 'CLI';
        $http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';

        $msg .= "\n\n=========URL==========\n";
        $msg .= esc_html($request_method . ' ' . $http_host . $request_uri) . "\n";
        $msg .= 'referrer: ' . esc_html($referer);

        $msg .= "\n\n=========HEADERS==========\n";
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // Remove sensitive headers
            unset($headers['Cookie'], $headers['Authorization']);
            $msg .= esc_html(print_r($headers, true));
        }

        $msg .= "\n\n=========GET==========\n";
        $msg .= esc_html(print_r(array_map('sanitize_text_field', $_GET), true));

        $msg .= "\n\n=========POST (keys only)==========\n";
        $msg .= esc_html(print_r(array_keys($_POST), true));

        $msg .= "\n\n=======CALL STACK=======\n";
        $msg .= esc_html(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));

        $msg .= "\n\n==========CURRENT BLOG ID==========\n";
        $msg .= esc_html(get_current_blog_id()) . "\n\n";

        $msg .= "\n\n==========CURRENT USER ID==========\n";
        $msg .= esc_html(get_current_user_id()) . "\n\n";

        $msg .= '</pre>';

        // Save msg to kvstore memory
        $kvstore = JBag::get('kvstore');
        if ($kvstore) {
            $kvstore->set($uid, $msg);
        }

        return esc_html($topic) . "\n\n" . esc_url(admin_url('admin-ajax.php?action=devalert&id=' . urlencode($uid)));
    }
}
