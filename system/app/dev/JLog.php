<?php

class JLog
{
	public static $should_slack = true;
	public static $flash_log = array();

	public static function init()
	{
		register_shutdown_function(array('JLog', 'slackOutLog'));
	}

	public static function slackOutLog()
	{
		if( static::$should_slack && count(Jlog::$flash_log) ) {
			DevAlert::slack('JLog', implode("\n", Jlog::$flash_log));
		}
	}

	public static function log($which, $msg, $deets='')
	{
		$kvstore = JBag::get('kvstore');
		$log = $kvstore->get("Jlog/$which");

		$logline = date('Y-m-d H:i:s') . " [$which] - $msg " . print_r($deets, true);
		
		// store to log
		$log .= $logline . "\n";
		$kvstore->set("Jlog/$which", $log);
		static::$flash_log[] = $logline;
	}

	public static function info($msg, $deets='')
	{
		JLog::log('info', $msg, $deets);
	}

	public static function warn($msg, $deets='')
	{
		JLog::log('warn', $msg, $deets);
	}

	public static function error($msg, $deets='')
	{
		JLog::log('error', $msg, $deets);
	}
}