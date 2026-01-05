<?php

declare(strict_types=1);

/**
 * JMVC - Utility Functions
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the role of a user
 *
 * @param int|null $id User ID (defaults to current user)
 * @return string|null The user's role or null if not found
 */
function get_user_role(?int $id = null): ?string
{
    global $current_user;

    if (!$id) {
        $id = $current_user->ID;
    }

    if (is_user_logged_in()) {
        $user = new WP_User($id);

        if (!empty($user->roles) && is_array($user->roles)) {
            foreach ($user->roles as $role) {
                return $role;
            }
        }
    }

    return null;
}

/**
 * Wrapper for site_url for use within CodeIgniter context
 *
 * @param string $url URL path
 * @return string Full site URL
 */
function wp_site_url(string $url): string
{
    return site_url($url);
}

/**
 * Check if current user is a page admin
 *
 * @return bool True if user is a page admin
 */
function is_page_admin(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (is_super_admin()) {
        return true;
    }

    if (is_user_member_of_blog(get_current_user_id(), App::$d->promo->wp_blog_id)) {
        return true;
    }

    $ci = &get_instance();
    if (isset($ci->fb) && $ci->fb->isAdmin()) {
        return true;
    }

    return false;
}

/**
 * Get the current domain/subdomain
 *
 * @return string|false Domain name or false on failure
 */
function get_current_domain(): string|false
{
    $blog_url = home_url();
    preg_match('/https?:\/\/([^\.]+)/', $blog_url, $matches);

    if (empty($matches[1])) {
        return false;
    }

    return $matches[1];
}

/**
 * Create a one-way salted hash of a word
 *
 * @param string $word Word to hash
 * @return string MD5 hash
 */
function salty_word(string $word): string
{
    return md5("sweet-and-salty-$word");
}

/**
 * Get a post ID from its slug
 *
 * @param string $slug Post slug
 * @param string $post_type Post type
 * @return int|null Post ID or null if not found
 */
function id_from_slug(string $slug, string $post_type = 'page'): ?int
{
    $attrs = array('name' => $slug);

    if ($post_type) {
        $attrs['post_type'] = $post_type;
    }

    $posts = get_posts($attrs);

    if ($posts) {
        return $posts[0]->ID;
    }

    return null;
}

/**
 * Clear Quick Cache (WordPress plugin)
 *
 * @return int|false Cache clear result or false if not available
 */
function util_clear_cache(): int|false
{
    if (!isset($GLOBALS['quick_cache'])) {
        return false;
    }

    return $GLOBALS['quick_cache']->auto_clear_cache() + $GLOBALS['quick_cache']->auto_clear_home_page_cache();
}

/**
 * Get current UTC timestamp
 *
 * @param string $when DateTime string
 * @return int Unix timestamp
 */
function utc_time(string $when = 'now'): int
{
    return utc_date($when)->getTimestamp();
}

/**
 * Get current UTC DateTime object
 *
 * @param string $when DateTime string
 * @return DateTime DateTime object in UTC
 */
function utc_date(string $when = 'now'): DateTime
{
    $utc_tz = new DateTimeZone('Europe/London');
    $now_dt = new DateTime($when, $utc_tz);

    return $now_dt;
}

/**
 * Shorten a URL using sht.tl service
 *
 * @param string $url URL to shorten
 * @return string|false Shortened URL or false on failure
 */
function short_url(string $url): string|false
{
    return file_get_contents("http://sht.tl/api.php?action=shorten&response=plain&longUrl=" . urlencode($url));
}

/**
 * Sign in a user programmatically
 *
 * @param WP_User $user The user object to authenticate
 * @throws Exception If user is not valid
 */
function auth_user(WP_User $user): void
{
    if (empty($user->ID)) {
        throw new Exception("auth_user: user is not valid");
    }

    // Clear existing WordPress cookies
    jmvc_clear_wp_cookies();

    wp_set_current_user($user->ID);

    // Set auth cookies for both HTTP and HTTPS
    wp_set_auth_cookie($user->ID, true, false);
    wp_set_auth_cookie($user->ID, true, true);

    do_action('wp_login', $user->user_login, $user);
}

/**
 * Clear WordPress authentication cookies
 */
function jmvc_clear_wp_cookies(): void
{
    if (!isset($_SERVER['HTTP_COOKIE'])) {
        return;
    }

    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    $my_domain = isset($_SERVER['SERVER_NAME']) ? '.' . sanitize_text_field($_SERVER['SERVER_NAME']) : '';
    $site_domain = wp_parse_url(home_url(), PHP_URL_HOST);
    $cookie_domain = $site_domain ? '.' . $site_domain : '';

    $paths = array('/', '/wp-admin', '/wp-login');

    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie, 2);
        $name = trim($parts[0]);

        if (stripos($name, 'wp-') !== false || stripos($name, 'wordpress') !== false) {
            foreach ($paths as $path) {
                setcookie($name, '', 1, $path);
                if ($cookie_domain) {
                    setcookie($name, '', 1, $path, $cookie_domain);
                }
                if ($my_domain && $my_domain !== $cookie_domain) {
                    setcookie($name, '', 1, $path, $my_domain);
                }
            }
        }
    }
}
