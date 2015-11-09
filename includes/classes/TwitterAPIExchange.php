<?php

/**
 * Twitter-API-PHP : Simple PHP wrapper for the v1.1 API
 *
 * PHP version 5.3.10
 *
 * @category Awesomeness
 * @package  Twitter-API-PHP
 * @author   James Mallison <me@j7mbo.co.uk>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://github.com/j7mbo/twitter-api-php
 */
class TwitterAPIExchange
{
	const METHOD_TIMELINE_GET = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

	const HTTP_GET = 'GET';
	const HTTP_POST = 'POST';

	private $oauth_access_token;
	private $oauth_access_token_secret;
	private $consumer_key;
	private $consumer_secret;
	private $postFields;
	private $getField;
	protected $oauth;
	public $url;


	/**
	 * Create the API access object. Requires an array of settings::
	 * oauth access token, oauth access token secret, consumer key, consumer secret
	 * These are all available by creating your own application on dev.twitter.com
	 * Requires the cURL library
	 *
	 * @param array $keys
	 */
	public function __construct(array $keys)
	{
		if (!isset($keys['oauth_access_token'])
			|| !isset($keys['oauth_access_token_secret'])
			|| !isset($keys['consumer_key'])
			|| !isset($keys['consumer_secret']))
		{
			throw new Exception('Make sure you are passing in the correct parameters');
		}

		$this->oauth_access_token = $keys['oauth_access_token'];
		$this->oauth_access_token_secret = $keys['oauth_access_token_secret'];
		$this->consumer_key = $keys['consumer_key'];
		$this->consumer_secret = $keys['consumer_secret'];
	}

	/**
	 * Set postFields array, example: array('screen_name' => 'J7mbo')
	 *
	 * @param array $array Array of parameters to send to API
	 *
	 * @return TwitterAPIExchange Instance of self for method chaining
	 */
	public function setPostfields(array $array)
	{
		if (!is_null($this->getGetField()))
		{
			throw new Exception('You can only choose get OR post fields.');
		}

		if (isset($array['status']) && substr($array['status'], 0, 1) === '@')
		{
			$array['status'] = sprintf("\0%s", $array['status']);
		}

		$this->postFields = $array;

		return $this;
	}

	/**
	 * Set getField string, example: '?screen_name=J7mbo'
	 *
	 * @param string $string Get key and value pairs as string
	 *
	 * @return \TwitterAPIExchange Instance of self for method chaining
	 */
	public function setGetfield($string)
	{
		if (!is_null($this->getPostFields()))
		{
			throw new Exception('You can only choose get OR post fields.');
		}

		$search = array('#', ',', '+', ':');
		$replace = array('%23', '%2C', '%2B', '%3A');
		$string = str_replace($search, $replace, $string);

		$this->getField = $string;

		return $this;
	}

	/**
	 * Get getField string (simple getter)
	 *
	 * @return string $this->getfields
	 */
	public function getGetField()
	{
		return $this->getField;
	}

	/**
	 * Get postFields array (simple getter)
	 *
	 * @return array $this->postFields
	 */
	public function getPostFields()
	{
		return $this->postFields;
	}

	/**
	 * Build the Oauth object using params set in construct and additionals
	 * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
	 *
	 * @param string $url The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
	 * @param string $requestMethod Either POST or GET
	 * @return \TwitterAPIExchange Instance of self for method chaining
	 */
	public function buildOauth($url, $requestMethod)
	{
		if (!in_array($requestMethod, array(self::HTTP_GET, self::HTTP_POST)))
		{
			throw new Exception('Request method must be either POST or GET');
		}

		$consumer_key = $this->consumer_key;
		$consumer_secret = $this->consumer_secret;
		$oauth_access_token = $this->oauth_access_token;
		$oauth_access_token_secret = $this->oauth_access_token_secret;

		$oauth = array(
			'oauth_consumer_key' => $consumer_key,
			'oauth_nonce' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => $oauth_access_token,
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		);

		$getfield = $this->getGetField();

		if (!is_null($getfield))
		{
			$getfields = str_replace('?', '', explode('&', $getfield));
			foreach ($getfields as $g)
			{
				$split = explode('=', $g);
				$oauth[$split[0]] = $split[1];
			}
		}

		$base_info = $this->_buildBaseString($url, $requestMethod, $oauth);
		$composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
		$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
		$oauth['oauth_signature'] = $oauth_signature;

		$this->url = $url;
		$this->oauth = $oauth;

		return $this;
	}

	/**
	 * Perform the actual data retrieval from the API
	 *
	 * @param boolean $return If true, returns data.
	 *
	 * @return string json If $return param is true, returns json data.
	 */
	public function performRequest($return = true)
	{
		if (!in_array('curl', get_loaded_extensions()))
		{
			return false;
		}

		if (!is_bool($return))
		{
			throw new Exception('performRequest parameter must be true or false');
		}

		$header = array($this->_buildAuthorizationHeader($this->oauth), 'Expect:');

		$getfield = $this->getGetField();
		$postfields = $this->getPostFields();

		$options = array(
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_HEADER => false,
			CURLOPT_URL => $this->url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false
		);

		if (!is_null($postfields))
		{
			$options[CURLOPT_POSTFIELDS] = $postfields;
		}
		else
		{
			if ($getfield !== '')
			{
				$options[CURLOPT_URL] .= $getfield;
			}
		}

		$feed = curl_init();
		curl_setopt_array($feed, $options);
		$json = curl_exec($feed);
		curl_close($feed);

		if ($return) { return $json; }
	}

	/**
	 * Private method to generate the base string used by cURL
	 *
	 * @param string $baseURI
	 * @param string $method
	 * @param array $params
	 *
	 * @return string Built base string
	 */
	private function _buildBaseString($baseURI, $method, $params)
	{
		$return = array();
		ksort($params);

		foreach($params as $key=>$value)
		{
			$return[] = "$key=" . $value;
		}

		return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
	}

	/**
	 * Private method to generate authorization header used by cURL
	 *
	 * @param array $oauth Array of oauth data generated by buildOauth()
	 *
	 * @return string $return Header used by cURL for request
	 */
	private function _buildAuthorizationHeader($oauth)
	{
		$return = 'Authorization: OAuth ';
		$values = array();

		foreach($oauth as $key => $value)
		{
			$values[] = "$key=\"" . rawurlencode($value) . "\"";
		}

		$return .= implode(', ', $values);
		return $return;
	}
}