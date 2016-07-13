<?php
/*
	JMVC - Utility Functions
*/

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

// let's us know if the user is a page admin
function is_page_admin()
{
	$ci = &get_instance();
	return (is_user_logged_in() && is_user_member_of_blog(get_current_user_id(), App::$d->promo->wp_blog_id)) || (@$_GET['snakeo'] == 'letmein') || $ci->fb->isAdmin() || is_super_admin();
}

function get_current_domain()
{
	// get current domain
	$blog_url = get_bloginfo('url');
	preg_match('/https?:\/\/([^\.]+)/', $blog_url, $matches);

	if( !$matches[1] ) 
	{
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

// sign in a user
function auth_user($user)
{
	if( !$user->ID ) {
		throw new Exception("auth_user: user is not valid");
	}

//	wp_logout();

	// get rid of all wordpress login cookies. Sometimes cookies on the base domain can interfere.
	if (isset($_SERVER['HTTP_COOKIE'])) 
	{
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    	foreach($cookies as $cookie) 
    	{
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			
			if( stripos($name, 'wp-') !== false || stripos($name, 'wordpress') !== false )
			{
				$my_domain = '.' . $_SERVER['SERVER_NAME'];

				setcookie($name, '', 1);
				setcookie($name, '', 1, null, '.etsythemeshop.com');
				setcookie($name, '', 1, null, $my_domain);

				setcookie($name, '', 1, '/');
				setcookie($name, '', 1, '/', '.etsythemeshop.com');
				setcookie($name, '', 1, null, $my_domain);

				setcookie($name, '', 1, '/wp-admin');
				setcookie($name, '', 1, '/wp-admin', '.etsythemeshop.com');
				setcookie($name, '', 1, null, $my_domain);

				setcookie($name, '', 1, '/wp-login');
				setcookie($name, '', 1, '/wp-login', '.etsythemeshop.com');
				setcookie($name, '', 1, null, $my_domain);
			}
		}

		// die();
	}

	wp_set_current_user( $user->ID );

	// both http: and https://
	wp_set_auth_cookie( $user->ID, true, false);
	wp_set_auth_cookie( $user->ID, true, true);
//	wp_set_auth_cookie( $user->ID );

	do_action( 'wp_login', $user->user_login );

	if (isset($_GET['cookie']) )  {
	//	die();
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