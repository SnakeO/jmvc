<?php

class APIController
{
	protected $api_result_mode = 'json';

	// return an associative array with $fields extracted from it (or the full item if fields is empty array)
	protected function extractFields($assoc_array, $fields)
	{
		if(!$fields) {
			return $assoc_array;
		}

		foreach($assoc_array as $k=>$v) 
		{
			if( !in_array($k, $fields) ) {
				unset($assoc_array[$k]);
			}
		}

		return $assoc_array;
	}

	// clean up $_REQUEST parameters
	protected function cleanParams($params)
	{
		// string -> boolean
		foreach($params as $k=>$v) 
		{
			if($v === 'true' || $v === 'false') {
				$params[$k] = ($v == 'true');
			}
		}

		if( @$params['fields'] ) {
			$params['fields'] = explode('|', $params['fields']);
		}

		return $params;
	}

	function api_set_mode($mode)
	{
		$this->api_result_mode = $mode;
	}

	// simple JSON api
	function api_result($success, $result=array())
	{
		$result['success'] = $success;
		//api_stringify($result); // everything's a string
		
		if( $this->api_result_mode == 'json' )
		{
			$json = json_encode($result);

			// jsonp?
			$callback = @$_REQUEST['callback'];
			if( $callback != null ) 
			{
				$json = $callback . "($json)";
				header("Content-Type: text/javascript");
			}
			else {
				header("Content-Type: application/json");
			}
			echo $json;
		}
		else if( $this->api_result_mode == 'return' )
		{
			// use StdClass
			return json_decode(json_encode($result));
		}
		else echo "bad result mode: $this->api_result_mode";
	}

	// everything's a string
	function api_stringify(&$fields)
	{
		foreach($fields as $k => $v) 
		{
			if( is_string($v) ) {
				continue;
			}

			if( is_bool($v) ) {
				$fields[$k] = $v ? 'true' : 'false';
			}

			if( is_int($v) || is_float($v) ) {
				$fields[$k] = strval($v);
			}

			if( is_object($v) || is_array($v) ) {
				$this->api_stringify($fields[$k]);
			}
		}
	}

	function api_success($result=array())
	{
		return $this->api_result(true, $result);
	}

	function api_die($msg, $result=array())
	{
		$result['message'] = $msg;

		if( !$result['reason'] ) {
			$result['reason'] = 'api_die';
		}

		$this->api_result(false, $result);

		die();
	}
}