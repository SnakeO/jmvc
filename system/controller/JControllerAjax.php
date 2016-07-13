<?php

/*
	Here's some .htaccess rules:
	RewriteRule ^controller/(pub|admin|resource)/(.*) wp-admin/admin-ajax.php?action=$1_controller&path=$2 [L,QSA]
	RewriteRule ^hmvc_controller/(pub|admin|resource)/(.*?)/(.*) wp-admin/admin-ajax.php?action=$1_controller&path=$3&module=$2 [L,QSA]
 
	NGINX:
	rewrite ^/controller/(pub|admin|resource)/(.*) /wp-admin/admin-ajax.php?action=$1_controller&path=$2 last; break;
	rewrite ^/hmvc_controller/(pub|admin|resource)/(.*?)/(.*) /wp-admin/admin-ajax.php?action=$1_controller&path=$3&module=$2 last; break;
 */

class JControllerAjax {

	public static function init()
	{
		return new self();
	}

	/**
	 * Class constructor. Set up some filters and actions.
	 *
	 * @return null
	 */
	function __construct() 
	{
		// controllers for the admin side of wordpress
		add_action('wp_ajax_admin_controller', array($this, 'admin_controller'));

		// controllers for the front-facing side of wordpress
		add_action('wp_ajax_pub_controller', array($this, 'pub_controller'));
		add_action('wp_ajax_nopriv_pub_controller', array($this, 'pub_controller'));

		// controllers for ajax resources
		add_action('wp_ajax_resource_controller', array($this, 'resource_controller'));
		add_action('wp_ajax_nopriv_resource_controller', array($this, 'resource_controller'));

		// query vars that this class needs
		add_filter( 'query_vars', array($this, 'query_vars') );
	}

	function admin_controller()
	{
		$this->ajax_controller('admin');
	}

	function pub_controller()
	{
		$this->ajax_controller('pub');
	}

	function resource_controller()
	{
		$this->ajax_controller('resource');
	}

	/**
	 * Loads in the requested controller and calls the requested function
	 * @param  string $env the environment to find the controller in (pub, admin, resource)
	 * @return void      Calls the controller then exit()
	 */
	function ajax_controller($env)
	{
		$module = @$_GET['module'] ? $_GET['module'] : null;
		$path = @$_GET['path'] or die('no path');
		$parts = explode('/', $path);

		$controller = array_shift($parts);

		if( !$funk = array_shift($parts) ) {
			$funk = 'index';
		}

		$params = $parts;

		if( $params[0] == null ) {
			array_shift($params);
		}

		$obj = JController::load($controller, $env, $module);

		if( !$obj ) 
		{
			$details = array(
				'controller'	=> $controller,
				'env' 			=> $env,
				'module'		=> $module,
				'funk'			=> $funk
			);

			DevAlert::slack("no controller: $controller", $details);

			die("no controller: $controller");
		}

		if( !method_exists($obj, $funk) )
		{
			$details = array(
				'controller'	=> $controller,
				'env' 			=> $env,
				'module'		=> $module,
				'funk'			=> $funk
			);

			DevAlert::slack("no controller: $controller", $details);

			die("bad function in controller $controller: $funk");
		}

		// call controller method
		call_user_func_array( array($obj, $funk), $params);

		exit;
	}

	/**
	 * Add on to the array of allowed GET query vars
	 * @param  array $query_vars The current array of query vars
	 * @return array             The new array of query vars, with ours appended to it
	 */
	function query_vars( $query_vars )
	{	
		$query_vars[] = 'action';
		$query_vars[] = 'path';
		$query_vars[] = 'module';	// for HMVC
		return $query_vars;
	}
}

/**
 * Global function used to generate URLs to access our controllers
 * @param  string $url    The URL in the format of ControllerClass/ControllerFunction/ControllerMethod/param/param/param
 * @param  string $env    The environment.. pub, admin, resource. Defaults to 'admin'
 * @param  string $module The HVMC module name (optional)
 * @return string         The generated URL
 */
function controller_url($url, $env='admin', $module=null)
{
	if( $env != 'admin' && $env != 'public' && $env != 'pub' && $env != 'resource' ) {
		die('bad controller_url environment: ' . $env);
	}

	$url = explode('/', $url);

	$controller = array_shift($url) or die("no controller: $url");
	$function = array_shift($url) or die("no function: $url");

	$params = count($url) > 0 ? '/' . implode('/', $url) : '';

//	$module_qs = $module ? "&module=$module" : '';

	// use pretty controllers
	if( !$module ) {
		return site_url("/controller/$env/$controller/$function$params");
	}

	return site_url("hmvc_controller/$env/$module/$controller/$function$params");

//	return site_url("/wp-admin/admin-ajax.php?action=" . $env . "_controller&path=$controller/$function$params$module_qs");
}

?>