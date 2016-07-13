<?php  

class JController {

	protected $module;	// set this for HMVC modules

	/**
	 * Constructor
	 */
	public function __construct()
	{
	
	}

	public static function load($controllername, $env='admin', $module=null)
	{
		if( $env != 'admin' && $env != 'pub' && $env != 'resource' ) {
			die('bad JControler::load environment: ' . $env);
		}

		// WPController -> wpcontroller.php
		$controller_filename = "$controllername.php";

		// from main or module?
		if( !$module ) 
		{
			$path = JMVC . "controllers/$env/$controller_filename";

			if( !file_exists($path) ) {
				return false;
			}

			require_once($path);
			$class = $controllername;
		}
		else 
		{
			$path = JMVC . "modules/$module/controllers/$env/$controller_filename";
				
			if( !file_exists($path) ) {
				return false;
			}

			// modules are namespaced -- build a namespaced class like \giveaway\resource\Giveaway or \giveaway\admin\WPGiveawaySetup
			require_once($path);
			$class = "$module\\$env\\$controllername";
		}

		return new $class();
	}
}
