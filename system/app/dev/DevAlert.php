<?php

use GuzzleHttp\Client as Guzzle;

class DevAlert
{
	static $promises = array();

	/**
	 * Send an alert out to the site admins
	 *
	 * @param  string $topic
	 * @param  mixed $deets The details of error message to send
	 * @return void
	 */
	public static function mail($topic, $deets='')
	{
		$body = DevAlert::constructBody($topic, $deets);
		wp_mail(JConfig::$config['devalert']['mail']['email'], $topic, $body);
	}

	/**
	 * Make sure that all async DevAlert's have gone out
	 */
	public static function waitForPromises()
	{
		foreach(DevAlert::$promises as $promise) {
			$promise->wait();
		}
	}

	/**
	 * Send slack alert out to the site admins
	 *
	 * @param  string $topic
	 * @param  mixed $deets The details of error message to send
	 * @return void
	 */
	public static function slack($topic, $deets='')
	{
		// Instantiate with defaults, so all messages created
		// will be sent from 'Cyril' and to the #accounting channel
		// by default. Any names like @regan or #channel will also be linked.
		$settings = [
			'username' => JConfig::$config['devalert']['slack']['username'],
			'channel' => JConfig::$config['devalert']['slack']['channel'],
			'link_names' => true
		];

		$guzzle = new Guzzle;
		$endpoint = JConfig::$config['devalert']['slack']['endpoint'];

		$client = new Maknz\Slack\Client($endpoint, $settings, $guzzle);
		$body = DevAlert::constructBody($topic, $deets);

		// $client->send($body);
		
		// send async instead
		$message = $client->createMessage();
		$message->setText($body);

		$payload = $client->preparePayload($message);
		$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
		DevAlert::$promises[] = $guzzle->requestAsync('POST', $endpoint, ['body' => $encoded]);
	}

	/**
	 * Send an alert out to the site admins (facade for easy switching between mail and slack)
	 *
	 * @param  string $topic
	 * @param  mixed $deets The details of error message to send
	 * @return void
	 */
	public static function send($topic, $deets)
	{
		DevAlert::slack($topic, $deets);
	}

	public static function init()
	{
		register_shutdown_function(array('DevAlert', 'waitForPromises'));

		add_action('wp_ajax_devalert', function()
		{
			$kvstore = JBag::get('kvstore');
			$res = $kvstore->get(@$_GET['id']);
			echo $res ?: "devalert not found";

			die();
		});
	}

	private static function constructBody($topic, $deets='')
	{
		$uid = uniqid('devalert_', true);

		$deets_output = $deets;

		if( $deets instanceof Exception ) {
			$deets = (array)$deets;
		}

		// convenient to pass in an array with the keys as the headings and value as the content
		if( is_array($deets) ) {

			$deets_output = '';

			foreach($deets as $heading => $content) {

				if( !is_string($content) ) {
					$content = print_r($content, true);
				}

				$deets_output .= "\n\n=======$heading======\n$content";
			}
		}

		$msg = "<pre>";

		// topic + details
		$msg .= "$topic\n\n";
		$msg .= $deets_output;

		// add in global stuff that's helpful
		$msg .= "\n\n=========URL==========\n";
		$msg .= @$_SERVER['REQUEST_METHOD'] . ' http://' . @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . "\n"; // suppress errors for cli
		$msg .= "referrer: " . @$_SERVER['HTTP_REFERER'];
		$msg .= "\n\n=========HEADERS==========\n";
		$msg .= getallheaders();
		$msg .= "\n\n=========GET==========\n";
		$msg .= print_r($_GET, true);
		$msg .= "\n\n=========POST==========\n";
		$msg .= print_r($_POST, true);
		$msg .= "\n\n=======CALL STACK=======\n";
		$msg .= print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);
		$msg .= "\n\n==========SESSION==========\n";
		$msg .= "id: " . session_id() . "\n\n";
		$msg .= print_r(@$_SESSION, true);
		$msg .= "\n\n==========COOKIES==========\n";
		$msg .= "id: " . print_r($_COOKIE, true) . "\n\n";
		$msg .= "\n\n==========CURRENT BLOG ID==========\n";
		$msg .= get_current_blog_id() . "\n\n";
		$msg .= "\n\n==========CURRENT USER ID==========\n";
		$msg .= get_current_user_id() . "\n\n";
		$msg .= "\n\n==========BROWSER==========\n";
		$msg .= print_r(@get_browser(), true) . "\n\n";

		$msg .= "</pre>";

		// save encrypted msg to kvstore memory
		$kvstore = JBag::get('kvstore');
		$kvstore->set($uid, $msg);

		return "$topic\n\n" . admin_url( "admin-ajax.php?action=devalert&id=$uid" );
	}
}