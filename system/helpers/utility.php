<?php
/**
 * JMVC - Utility Functions
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

// http://wpeden.com/tipsntuts/how-to-get-logged-in-users-role-in-wordpress/
// if $id is not passed in, it will get the user role of the logged in user
function get_user_role($id=null)
{
	global $current_user;
	
	if(!$id) $id = $current_user->ID;
	
	if ( is_user_logged_in() ) 
	{
		$user = new WP_User( $id );
		
		if ( !empty( $user->roles ) && is_array( $user->roles ) ) 
		{
			foreach ( $user->roles as $role ) {
				return $role;
			}
		}
	}
}

// so that we can be more explicit when we use site_url() within codeigniter
function wp_site_url($url)
{
	return site_url($url);
}

/**
 * Check if current user is a page admin
 *
 * @return bool True if user is a page admin
 */
function is_page_admin()
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
function get_current_domain()
{
	$blog_url = home_url();
	preg_match('/https?:\/\/([^\.]+)/', $blog_url, $matches);

	if (empty($matches[1])) {
		return false;
	}

	return $matches[1];
}

// make a one-way token so no one can login by just typing in the url w/the facebook page id
function salty_word($word)
{
	return md5("sweet-and-salty-$word");
}

// get a post by slug
function id_from_slug( $slug, $post_type='page' ) 
{
	$attrs = array('name' => $slug);

	if( $post_type ) {
		$attrs['post_type'] = $post_type;
	}

   // $query = new WP_Query($attrs);

   // $query->the_post();
   // $id = get_the_ID();

   // wp_reset_postdata();
   // wp_reset_query();

	$posts = get_posts($attrs);

	if( $posts ) {
		return $posts[0]->ID;
	}

	return null;
}

// clear QUICK CACHE (wordpress plugin)
function util_clear_cache()
{
	if( !isset($GLOBALS['quick_cache']) ) {
		return false;
	}

	return $GLOBALS['quick_cache']->auto_clear_cache() + $GLOBALS['quick_cache']->auto_clear_home_page_cache();
}

// time NOW @ UTC 0
function utc_time($when='now')
{
	return utc_date($when)->getTimestamp();
}

function utc_date($when='now')
{
	$utc_tz = new DateTimeZone('Europe/London');
	$now_dt = new DateTime($when, $utc_tz); // GMT +0

	return $now_dt;
}

// shorten the url
function short_url($url)
{
	return file_get_contents("http://sht.tl/api.php?action=shorten&response=plain&longUrl=" . urlencode($url));
}

/**
 * Sign in a user programmatically
 *
 * @param WP_User $user The user object to authenticate
 * @throws Exception If user is not valid
 */
function auth_user($user)
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
 *
 * @return void
 */
function jmvc_clear_wp_cookies()
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

/*
// in order for auth_user above to work, we force wordpress to not
// care about the 'secure' flag when looking @ cookies
add_filter('auth_redirect_scheme', function($secure)
{
	return 'auth';
});
*/

?>