<?php  

class JLib 
{
	public static $libs;

	public function __construct($module=null)
	{
		$this->module = $module;
	}

	public static function load($librarypath, $module=null)
	{
		// alraedy loaded?
		if( isset(self::$libs[$librarypath]) ) {
			return self::$libs[$librarypath];
		}

		// allow for libraries to live in nested subdirectories
		$pathinfo = pathinfo($librarypath);
		$libraryname = $pathinfo['basename'];
		$librarydir = $pathinfo['dirname'];

		if( $librarydir == '.' ) {
			$librarydir = '';
		}

		// VoucherMailer -> VoucherMailer.php
		$library_filename = "$libraryname.php";

		// from main or module?
		if( !$module ) 
		{
			$path = JMVC . "libraries/$librarydir/$library_filename";

			if( !file_exists($path) ) 
			{
				// try lowercase
				$library_filename = strtolower($library_filename);
				$path = JMVC . "libraries/$librarydir/$library_filename";

				if( !file_exists($path) ) {	
					return false;
				}
			}

			require_once($path);
			$class = $librarydir ? "\\libraries\\$librarydir\\$libraryname" : "\\libraries\\$libraryname";
		}
		else 
		{
			$path = JMVC . "modules/$module/libraries/$librarydir/$library_filename";
				
			if( !file_exists($path) ) 
			{
				// try lowercase
				$library_filename = strtolower($library_filename);
				$path = JMVC . "modules/$module/libraries/$librarydir/$library_filename";

				if( !file_exists($path) ) {	
					return false;
				}
			}

			// modules are namespaced -- build a namespaced class like \giveaway\resource\Giveaway or \giveaway\admin\WPGiveawaySetup
			require_once($path);
			$class = $librarydir ?  "$module\\libraries\\$librarydir\\$libraryname" : "$module\\libraries\\$libraryname";
		}

		if( !class_exists($class) ) {
			print_r(debug_backtrace());
			throw new Exception("Class $class doesn't exist");
		}

		self::$libs[$librarypath] = new $class();
		return self::$libs[$librarypath];
	}
}