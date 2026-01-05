<?php

declare(strict_types=1);

/**
 * Base API Controller for JSON endpoints
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class APIController
{
    protected string $api_result_mode = 'json';

    /**
     * Extract only specified fields from an associative array
     *
     * @param array $assoc_array The source array
     * @param array $fields Fields to extract (empty returns all)
     * @return array Filtered array
     */
    protected function extractFields(array $assoc_array, array $fields): array
    {
        if (!$fields) {
            return $assoc_array;
        }

        foreach ($assoc_array as $k => $v) {
            if (!in_array($k, $fields, true)) {
                unset($assoc_array[$k]);
            }
        }

        return $assoc_array;
    }

    /**
     * Clean up and sanitize request parameters
     *
     * @param array $params Raw parameters
     * @return array Cleaned parameters
     */
    protected function cleanParams(array $params): array
    {
        // string -> boolean conversion
        foreach ($params as $k => $v) {
            if ($v === 'true' || $v === 'false') {
                $params[$k] = ($v === 'true');
            }
        }

        if (!empty($params['fields'])) {
            $params['fields'] = array_map('sanitize_text_field', explode('|', $params['fields']));
        }

        return $params;
    }

    /**
     * Set the API result mode
     *
     * @param string $mode 'json' or 'return'
     */
    public function api_set_mode(string $mode): void
    {
        $this->api_result_mode = sanitize_key($mode);
    }

    /**
     * Output API result as JSON
     *
     * @param bool $success Whether the request was successful
     * @param array $result Result data
     * @return mixed JSON output or object depending on mode
     */
    public function api_result(bool $success, array $result = []): mixed
    {
        $result['success'] = $success;

        if ($this->api_result_mode === 'json') {
            $json = wp_json_encode($result);

            // JSONP support with sanitized callback
            $callback = isset($_REQUEST['callback']) ? $_REQUEST['callback'] : null;

            if ($callback !== null) {
                // Sanitize callback to prevent XSS - only allow valid JS function names
                $callback = preg_replace('/[^a-zA-Z0-9_\$]/', '', $callback);

                if (!empty($callback)) {
                    $json = $callback . '(' . $json . ')';
                    header('Content-Type: text/javascript; charset=utf-8');
                } else {
                    header('Content-Type: application/json; charset=utf-8');
                }
            } else {
                header('Content-Type: application/json; charset=utf-8');
            }

            echo $json;
            return null;
        }

        if ($this->api_result_mode === 'return') {
            return json_decode(wp_json_encode($result));
        }

        wp_die(esc_html('Invalid result mode: ' . $this->api_result_mode));
    }

    /**
     * Convert all values to strings recursively
     *
     * @param array $fields Array to stringify
     */
    public function api_stringify(array &$fields): void
    {
        foreach ($fields as $k => $v) {
            if (is_string($v)) {
                continue;
            }

            if (is_bool($v)) {
                $fields[$k] = $v ? 'true' : 'false';
            }

            if (is_int($v) || is_float($v)) {
                $fields[$k] = strval($v);
            }

            if (is_object($v) || is_array($v)) {
                $this->api_stringify($fields[$k]);
            }
        }
    }

    /**
     * Return a success response
     *
     * @param array $result Result data
     * @return mixed
     */
    public function api_success(array $result = []): mixed
    {
        return $this->api_result(true, $result);
    }

    /**
     * Return an error response and terminate
     *
     * @param string $msg Error message
     * @param array $result Additional result data
     */
    public function api_die(string $msg, array $result = []): never
    {
        $result['message'] = $msg;

        if (empty($result['reason'])) {
            $result['reason'] = 'api_die';
        }

        $this->api_result(false, $result);

        exit;
    }
}
