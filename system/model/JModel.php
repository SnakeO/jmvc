<?php  

class JModel 
{
	public static $singletons;

	public static function load($modelpath, $module=null, $get_singleton=false)
	{
		if( $get_singleton && isset(self::$singletons["$modelpath.$module"]) ) {
			return self::$singletons["$modelpath.$module"];
		}

		$modelname = basename($modelpath);

		// from main or module?
		if( !$module ) 
		{
			$path = JMVC . "models/$modelpath.php";

			if( !file_exists($path) ) {
				throw new Exception("Couldn't find global model $modelpath");
			}

			$class = $modelname;
			require_once($path);
		}
		else 
		{
			$path = JMVC . "modules/$module/models/$modelpath.php";
				
			if( !file_exists($path) ) {
				throw new Exception("Couldn't find model $modelpath in $module");
			}

			// modules are namespaced -- build a namespaced class like \giveaway\resource\Giveaway or \giveaway\admin\WPGiveawaySetup
			$class = "$module\\$modelname";
			require_once($path);
		}

		if( $get_singleton ) 
		{
			self::$singletons["$modelpath.$module"] = new $class();
			return self::$singletons["$modelpath.$module"];
		}
	}

	public static function exists($modelpath, $module=null)
	{
		if( isset(self::$singletons["$modelpath.$module"]) ) {
			return true;
		}

		$modelname = basename($modelpath);

		// from main or module?
		if( !$module ) 
		{
			$path = JMVC . "models/$modelpath.php";

			if( !file_exists($path) ) {
				return false;
			}
		}
		else 
		{
			$path = JMVC . "modules/$module/models/$modelpath.php";
				
			if( !file_exists($path) ) {
				return false;
			}
		}

		return $path;
	}
}