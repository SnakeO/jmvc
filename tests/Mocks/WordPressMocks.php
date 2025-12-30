<?php
/**
 * WordPress Mock Functions for Testing
 *
 * Provides mock implementations of WordPress core functions
 * to enable unit testing without a WordPress installation.
 *
 * @package JMVC\Tests\Mocks
 */

namespace {
    /**
     * Mock data storage for testing
     */
    class WP_Mock_Data
    {
        public static $posts = [];
        public static $options = [];
        public static $users = [];
        public static $current_user_id = 0;
        public static $is_logged_in = false;
        public static $user_capabilities = [];
        public static $nonces = [];
        public static $actions = [];
        public static $filters = [];
        public static $post_meta = [];
        public static $json_sent = null;
        public static $died = false;
        public static $die_message = '';

        public static function reset()
        {
            self::$posts = [];
            self::$options = [];
            self::$users = [];
            self::$current_user_id = 0;
            self::$is_logged_in = false;
            self::$user_capabilities = [];
            self::$nonces = [];
            self::$actions = [];
            self::$filters = [];
            self::$post_meta = [];
            self::$json_sent = null;
            self::$died = false;
            self::$die_message = '';
        }

        public static function setLoggedIn($user_id, $capabilities = [])
        {
            self::$is_logged_in = true;
            self::$current_user_id = $user_id;
            self::$user_capabilities = $capabilities;
        }

        public static function setLoggedOut()
        {
            self::$is_logged_in = false;
            self::$current_user_id = 0;
            self::$user_capabilities = [];
        }

        public static function addPost($id, $data)
        {
            self::$posts[$id] = (object) array_merge([
                'ID' => $id,
                'post_title' => '',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_date' => date('Y-m-d H:i:s'),
                'post_author' => 1,
            ], $data);
        }

        public static function setPostMeta($post_id, $key, $value)
        {
            if (!isset(self::$post_meta[$post_id])) {
                self::$post_meta[$post_id] = [];
            }
            self::$post_meta[$post_id][$key] = $value;
        }
    }

    /**
     * Mock WP_Post class
     */
    if (!class_exists('WP_Post')) {
        class WP_Post
        {
            public $ID;
            public $post_title;
            public $post_content;
            public $post_status;
            public $post_type;
            public $post_date;
            public $post_author;

            public function __construct($post = null)
            {
                if (is_object($post)) {
                    foreach (get_object_vars($post) as $key => $value) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    /**
     * Mock WP_Error class
     */
    if (!class_exists('WP_Error')) {
        class WP_Error
        {
            protected $errors = [];
            protected $error_data = [];

            public function __construct($code = '', $message = '', $data = '')
            {
                if (!empty($code)) {
                    $this->errors[$code][] = $message;
                    if (!empty($data)) {
                        $this->error_data[$code] = $data;
                    }
                }
            }

            public function get_error_message($code = '')
            {
                if (empty($code)) {
                    $code = $this->get_error_code();
                }
                return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
            }

            public function get_error_code()
            {
                $codes = array_keys($this->errors);
                return !empty($codes) ? $codes[0] : '';
            }

            public function get_error_codes()
            {
                return array_keys($this->errors);
            }
        }
    }

    /**
     * Mock WP_User class
     */
    if (!class_exists('WP_User')) {
        class WP_User
        {
            public $ID;
            public $user_login;
            public $user_email;
            public $display_name;
            public $roles = [];
            public $caps = [];

            public function __construct($id = 0)
            {
                $this->ID = $id;
                if (isset(WP_Mock_Data::$users[$id])) {
                    foreach (WP_Mock_Data::$users[$id] as $key => $value) {
                        $this->$key = $value;
                    }
                }
            }

            public function has_cap($capability)
            {
                return in_array($capability, WP_Mock_Data::$user_capabilities, true);
            }
        }
    }

    // Authentication functions
    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in()
        {
            return WP_Mock_Data::$is_logged_in;
        }
    }

    if (!function_exists('current_user_can')) {
        function current_user_can($capability, $id = null)
        {
            return in_array($capability, WP_Mock_Data::$user_capabilities, true);
        }
    }

    if (!function_exists('get_current_user_id')) {
        function get_current_user_id()
        {
            return WP_Mock_Data::$current_user_id;
        }
    }

    if (!function_exists('wp_get_current_user')) {
        function wp_get_current_user()
        {
            return new WP_User(WP_Mock_Data::$current_user_id);
        }
    }

    if (!function_exists('is_super_admin')) {
        function is_super_admin($user_id = null)
        {
            return in_array('super_admin', WP_Mock_Data::$user_capabilities, true);
        }
    }

    // Nonce functions
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action)
        {
            $nonce = md5($action . 'salt' . time());
            WP_Mock_Data::$nonces[$nonce] = $action;
            return $nonce;
        }
    }

    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action)
        {
            if (isset(WP_Mock_Data::$nonces[$nonce]) && WP_Mock_Data::$nonces[$nonce] === $action) {
                return 1;
            }
            return false;
        }
    }

    // Post functions
    if (!function_exists('get_post')) {
        function get_post($id, $output = OBJECT)
        {
            if (!isset(WP_Mock_Data::$posts[$id])) {
                return null;
            }
            $post = WP_Mock_Data::$posts[$id];
            if ($output === ARRAY_A) {
                return (array) $post;
            }
            return $post;
        }
    }

    if (!function_exists('get_posts')) {
        function get_posts($args = [])
        {
            $posts = WP_Mock_Data::$posts;

            if (isset($args['post_type'])) {
                $posts = array_filter($posts, function ($post) use ($args) {
                    return $post->post_type === $args['post_type'];
                });
            }

            if (isset($args['posts_per_page']) && $args['posts_per_page'] > 0) {
                $posts = array_slice($posts, 0, $args['posts_per_page']);
            }

            return array_values($posts);
        }
    }

    if (!function_exists('wp_insert_post')) {
        function wp_insert_post($data, $wp_error = false)
        {
            static $next_id = 1000;

            if (empty($data['post_type'])) {
                $data['post_type'] = 'post';
            }

            if (isset($data['ID']) && $data['ID'] > 0) {
                $id = $data['ID'];
            } else {
                $id = $next_id++;
            }

            $data['ID'] = $id;
            WP_Mock_Data::addPost($id, $data);

            return $id;
        }
    }

    if (!function_exists('wp_update_post')) {
        function wp_update_post($data, $wp_error = false)
        {
            if (empty($data['ID'])) {
                return 0;
            }

            $id = $data['ID'];
            if (!isset(WP_Mock_Data::$posts[$id])) {
                return 0;
            }

            foreach ($data as $key => $value) {
                WP_Mock_Data::$posts[$id]->$key = $value;
            }

            return $id;
        }
    }

    if (!function_exists('wp_delete_post')) {
        function wp_delete_post($id, $force_delete = false)
        {
            if (!isset(WP_Mock_Data::$posts[$id])) {
                return null;
            }

            $post = WP_Mock_Data::$posts[$id];
            unset(WP_Mock_Data::$posts[$id]);

            return $post;
        }
    }

    // Post meta functions
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key = '', $single = false)
        {
            if (!isset(WP_Mock_Data::$post_meta[$post_id])) {
                return $single ? '' : [];
            }

            if (empty($key)) {
                return WP_Mock_Data::$post_meta[$post_id];
            }

            if (!isset(WP_Mock_Data::$post_meta[$post_id][$key])) {
                return $single ? '' : [];
            }

            $value = WP_Mock_Data::$post_meta[$post_id][$key];
            return $single ? $value : [$value];
        }
    }

    if (!function_exists('update_post_meta')) {
        function update_post_meta($post_id, $key, $value, $prev_value = '')
        {
            WP_Mock_Data::setPostMeta($post_id, $key, $value);
            return true;
        }
    }

    if (!function_exists('delete_post_meta')) {
        function delete_post_meta($post_id, $key, $value = '')
        {
            if (isset(WP_Mock_Data::$post_meta[$post_id][$key])) {
                unset(WP_Mock_Data::$post_meta[$post_id][$key]);
                return true;
            }
            return false;
        }
    }

    // Sanitization functions
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str)
        {
            // Remove tags, octets, entities, and extra whitespace
            $str = strip_tags($str);
            $str = preg_replace('/[\x00-\x1F\x7F]/', '', $str); // Remove control chars
            return trim($str);
        }
    }

    if (!function_exists('sanitize_file_name')) {
        function sanitize_file_name($filename)
        {
            // Remove path traversal and special characters
            $filename = str_replace(['../', '..\\', '..'], '', $filename);
            $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
            return $filename;
        }
    }

    if (!function_exists('sanitize_key')) {
        function sanitize_key($key)
        {
            // WordPress sanitize_key keeps underscores
            return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
        }
    }

    if (!function_exists('sanitize_email')) {
        function sanitize_email($email)
        {
            // Strip tags first, then validate email chars
            $email = strip_tags($email);
            // Only keep valid email characters
            return preg_replace('/[^a-zA-Z0-9@._\-+]/', '', $email);
        }
    }

    if (!function_exists('sanitize_textarea_field')) {
        function sanitize_textarea_field($str)
        {
            return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('sanitize_hex_color')) {
        function sanitize_hex_color($color)
        {
            if (preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                return $color;
            }
            return '';
        }
    }

    if (!function_exists('absint')) {
        function absint($value)
        {
            return abs((int) $value);
        }
    }

    // Escaping functions
    if (!function_exists('esc_html')) {
        function esc_html($text)
        {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('esc_attr')) {
        function esc_attr($text)
        {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('esc_url')) {
        function esc_url($url)
        {
            // Block javascript: and data: URLs
            if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
                return '';
            }
            return filter_var($url, FILTER_SANITIZE_URL);
        }
    }

    if (!function_exists('esc_url_raw')) {
        function esc_url_raw($url)
        {
            return filter_var($url, FILTER_SANITIZE_URL);
        }
    }

    if (!function_exists('wp_kses_post')) {
        function wp_kses_post($content)
        {
            return strip_tags($content, '<p><br><strong><em><a><ul><ol><li>');
        }
    }

    // JSON functions
    if (!function_exists('wp_json_encode')) {
        function wp_json_encode($data)
        {
            return json_encode($data);
        }
    }

    if (!function_exists('wp_send_json')) {
        function wp_send_json($response)
        {
            WP_Mock_Data::$json_sent = $response;
        }
    }

    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data = null, $status = null)
        {
            WP_Mock_Data::$json_sent = ['success' => false, 'data' => $data];
        }
    }

    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null)
        {
            WP_Mock_Data::$json_sent = ['success' => true, 'data' => $data];
        }
    }

    // URL functions
    if (!function_exists('home_url')) {
        function home_url($path = '')
        {
            return 'https://example.com' . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('admin_url')) {
        function admin_url($path = '')
        {
            return 'https://example.com/wp-admin' . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('site_url')) {
        function site_url($path = '')
        {
            return 'https://example.com' . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('rest_url')) {
        function rest_url($path = '')
        {
            return 'https://example.com/wp-json' . ($path ? '/' . ltrim($path, '/') : '');
        }
    }

    if (!function_exists('get_permalink')) {
        function get_permalink($post_id = null)
        {
            return 'https://example.com/?p=' . ($post_id ?: 0);
        }
    }

    if (!function_exists('get_template_directory')) {
        function get_template_directory()
        {
            return '/tmp/wordpress/wp-content/themes/test-theme';
        }
    }

    if (!function_exists('get_template_directory_uri')) {
        function get_template_directory_uri()
        {
            return 'https://example.com/wp-content/themes/test-theme';
        }
    }

    // REST API functions
    if (!function_exists('rest_ensure_response')) {
        function rest_ensure_response($response)
        {
            return $response;
        }
    }

    if (!function_exists('register_rest_route')) {
        function register_rest_route($namespace, $route, $args)
        {
            return true;
        }
    }

    // Hook functions
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
        {
            WP_Mock_Data::$actions[$hook][] = [
                'callback' => $callback,
                'priority' => $priority,
                'accepted_args' => $accepted_args,
            ];
            return true;
        }
    }

    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
        {
            WP_Mock_Data::$filters[$hook][] = [
                'callback' => $callback,
                'priority' => $priority,
                'accepted_args' => $accepted_args,
            ];
            return true;
        }
    }

    if (!function_exists('do_action')) {
        function do_action($hook, ...$args)
        {
            if (isset(WP_Mock_Data::$actions[$hook])) {
                foreach (WP_Mock_Data::$actions[$hook] as $action) {
                    call_user_func_array($action['callback'], $args);
                }
            }
        }
    }

    if (!function_exists('apply_filters')) {
        function apply_filters($hook, $value, ...$args)
        {
            if (isset(WP_Mock_Data::$filters[$hook])) {
                foreach (WP_Mock_Data::$filters[$hook] as $filter) {
                    $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
                }
            }
            return $value;
        }
    }

    if (!function_exists('register_shutdown_function')) {
        // Already exists in PHP
    }

    // Misc functions
    if (!function_exists('wp_mail')) {
        function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
        {
            return true;
        }
    }

    if (!function_exists('wp_die')) {
        function wp_die($message = '', $title = '', $args = [])
        {
            WP_Mock_Data::$died = true;
            WP_Mock_Data::$die_message = $message;
            throw new \Exception('wp_die: ' . $message);
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing)
        {
            return $thing instanceof WP_Error;
        }
    }

    if (!function_exists('wp_trim_words')) {
        function wp_trim_words($text, $num_words = 55, $more = '...')
        {
            $words = explode(' ', $text);
            if (count($words) > $num_words) {
                $words = array_slice($words, 0, $num_words);
                return implode(' ', $words) . $more;
            }
            return $text;
        }
    }

    if (!function_exists('current_time')) {
        function current_time($type, $gmt = 0)
        {
            if ($type === 'mysql') {
                return date('Y-m-d H:i:s');
            }
            return time();
        }
    }

    if (!function_exists('register_post_type')) {
        function register_post_type($post_type, $args = [])
        {
            return true;
        }
    }

    if (!function_exists('error_log')) {
        // Already exists in PHP
    }
}
