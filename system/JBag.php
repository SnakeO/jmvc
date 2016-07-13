<?php  

/* global get/set */

class JBag 
{
	public static $module;

	static $things = array();

	public static function set($k, $v)
	{
		self::$things[$k] = $v;
	}

	public static function get($k) 
	{
		return @self::$things[$k];
	}
}