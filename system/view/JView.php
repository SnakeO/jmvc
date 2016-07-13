<?php  

class JView 
{
	public static $module;

	function show($view, $data=array(), $force_module=false)
	{
		echo self::get($view, $data, $force_module);
	}

	// returns as a variable
	function get($view, $data=array(), $force_module=false)
	{
		if( !is_array($data) ) {
			throw new Exception("Data for view $view is not an array.");
		}

		$module = $force_module ? $force_module : self::$module;
		$view_pathinfo = pathinfo($view);

		// check HVMC
		if( $module ) 
		{
			$viewdir 	= dirname(__FILE__) . "/../../modules/$module/views";
			$fullview 	= "$viewdir/$view.php";
			$view_url 	= JMVC_URL . "modules/$module/views/" . ($view_pathinfo['dirname'] ? "$view_pathinfo[dirname]/" : '');
		}

		// if the module's view doesn't exist, try in the global scope
		if( !$module || !file_exists($fullview) )
		{
			$viewdir 	= dirname(__FILE__) . '/../../views';
			$fullview 	= "$viewdir/$view.php";
			$view_url 	= JMVC_URL . "views/" . ($view_pathinfo['dirname'] ? "$view_pathinfo[dirname]/" : '');
		}
		
		if( !file_exists($fullview) ) {
			throw new Exception("View not found: $fullview");
		}

		// useful global variables for the view to use
		$view_url;
		
		// catchers mitt
		ob_start();
		extract($data);
		include($fullview);
		
		return ob_get_clean();
	}
}