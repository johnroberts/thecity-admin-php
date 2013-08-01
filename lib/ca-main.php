<?php
/** 
* Project:    thecity-admin-php
* File:       ca-main.php
*
* @author John Roberts <john@john-roberts.net> 
* @link https://github.com/johnroberts/thecity-admin-php
* @version 1.1
*/
require_once dirname(__FILE__) . '/ca-config.php'; 

define("CITYAPIBASEURL", "https://api.onthecity.org"); // Base URL for the City Admin API

class CityApi 
{
	protected $key = APIKEY;                    // The City API key to use 
	protected $token = USERTOKEN;               // The City API user token
	protected $last_headers = array();          // HTTP headers from last request
	protected $last_response_start_line = NULL; // Last HTTP Response Start Line, e.g.: "HTTP/1.1 200 OK"
	protected $last_status_code = NULL;         // Last HTTP status code, e.g.: 200
	public  $debug = false;                     // Controls whether debug_message() outputs anything
	public  $json = true;                       // Should results be JSON or PHP associative array?
	public  $ssl_verify_peer = true;            // Should curl verify it's SSL peer? Leave true in production, you can set to false to in some test environments, like XAMPP on Windows

	protected function curl_check_basic_functions() 
	{ 
	  if( !function_exists("curl_init") && 
		  !function_exists("curl_setopt") && 
		  !function_exists("curl_exec") && 
		  !function_exists("curl_close") ) return false; 
	  else return true; 
	} 

	// Parses HTTP response headers into an array
	protected function http_parse_headers($header)
	{
		$retval = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
		foreach( $fields as $field ) {
			if($field =='') break; // fix for continuing past end of headers into body
			if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
				if( isset($retVal[$match[1]]) ) {
					$retval[$match[1]] = array($retval[$match[1]], $match[2]);
				} else {
					$retval[$match[1]] = trim($match[2]);
				}
			}
		}
		return $retval;
	}
	
	/**
	* Gets the HMAC for an API endpoint request signing
	*
	* @param string $endpoint The City Admin API endpoint
	* @param integer $time The time of the request
	* @param string $body The JSON body (if any) that is part of the request
	*
	* @return string The HMAC signature signed using the API key and user token
	*/
	protected function get_hmac($endpoint, $time, $body = NULL)
	{
		// Prepare the string to sign, Unix time + verb + host + path + query params + body 
		$string_to_sign = $time . $endpoint; // example endpoint: "GEThttps://api.onthecity.org/users"
		if(!is_null($body))
			$string_to_sign .= $body;
		$this->debug_message (__FUNCTION__, 'string_to_sign: ' . $string_to_sign);

		// Construct the HMAC signature
		$unencoded_hmac = hash_hmac('sha256', $string_to_sign, $this->key, true);
		$unescaped_hmac = base64_encode($unencoded_hmac);
		$trimmed_unescaped_hmac = trim($unescaped_hmac);
		$hmac_signature = urlencode($trimmed_unescaped_hmac);

		$this->debug_message (__FUNCTION__, 'hmac_signature: ' . $hmac_signature);

		return $hmac_signature;
	}


	/**
	* If the [optional] params are set then those will be use for the API Key and Token.  
	* Otherwise the APIKEY and USERTOKEN global constants will be used.
	*
	* @param string $apikey (Optional) The City API key to use. Can also be set via the global constant APIKEY.
	* @param string $usertoken (Optional) The City API user token.  Can also be set via the global constant USERTOKEN.
	*/
  public function __construct($apikey = NULL, $usertoken = NULL) {
  	if(!is_null($apikey)) { $self->key = $apikey; }
  	if(!is_null($usertoken)) { $self->token = $usertoken; }
  }

	
	// test function to try to figure out what's going on with City signature problems
	public function get_hmac2($string_to_sign)
	{
		
		// Construct the HMAC signature
		$unencoded_hmac = hash_hmac('sha256', $string_to_sign, $this->key, true);
		$unescaped_hmac = base64_encode($unencoded_hmac);
		$trimmed_unescaped_hmac = trim($unescaped_hmac);
		$hmac_signature = urlencode($trimmed_unescaped_hmac);

		$this->debug_message (__FUNCTION__, 'string_to_sign: ' . $string_to_sign);
		$this->debug_message (__FUNCTION__, 'unencoded_hmac: ' . $unencoded_hmac);
		$this->debug_message (__FUNCTION__, 'unescaped_hmac: ' . $unescaped_hmac);
		$this->debug_message (__FUNCTION__, 'trimmed_unescaped_hmac: ' . $trimmed_unescaped_hmac);
		$this->debug_message (__FUNCTION__, 'hmac_signature: ' . $hmac_signature);
		
		return $hmac_signature;
	}
	
	/**
	 * Exception handler
	 * Logs to error log and returns an error code in the format of The City Admin API 
	 * web service error codes
	 *
	 * @param string $fname The name of the method or function where the exception hapened
	 * @param string $msg The error message
	 *
	 * @return string A formatted error message
	 */
	protected function handle_exception($fname, $msg)
	{
		$msg = 'Exception: ' . $fname .': ' . $msg;
		$results = array('error_code' => 500, 'error_message' => $msg);
		error_log($msg);
		$this->last_status_code = 500;
		return $this->json ? json_encode($results) : $results;
	}

	/**
	 * Debug messages
	 * If it exists, calls ca_debug_message (defined in ca-config.php) to allow configuration of debug message behavior.
	 * Otherwise outputs using error_log().
	 * Does nothing unless CityApi::debug is true.
	 *
     * @param string $fname The name of the method or function where the exception hapened
	 * @param string $msg The error message
	 */
	protected function debug_message($fname, $msg = "")
	{
		if($this->debug) {
			$msg1 = 'DEBUG: ' . $fname . ': ' . $msg;
			if(function_exists('ca_debug_message')) 
				ca_debug_message($msg1);
			else
				error_log($msg1);
		}
	}

	/**
	 * Adds arguments as querystring to $url:
	 *   Appends '?'
	 *   Appends name/value pair arguments found in args
	 *
	 * @param $url The URL to append to (expected to have no trailing slash)
	 * @param $args A valid associative array
	 *
	 * @return string The completed url with querystring
	 *
	 * Produces querystring parameters in sorted order.
	 * Didn't use http_build_query since it urlencodes, at the wrong time for City signature processing.
	 */
	private function add_querystring ($url, $args = NULL)
	{
		if (!is_null($args))
			ksort($args);
		$first_arg = true;
		foreach($args as $key => $value) {
			if($first_arg) { 
				//$url .= '?' . $key . '=' . urlencode($value); 
				$url .= '?' . $key . '=' . $value; 
				$first_arg = false;
			} 
			else {	
				//$url .= '&' . $key . '=' . urlencode($value);  
				$url .= '&' . $key . '=' . $value;  
			}
		}
		
		$this->debug_message (__FUNCTION__, 'returning url: ' . $url);
		return $url;
	}

	// Call The City Admin API web service endpoint
	//
	// Parse and capture HTTP status code and headers
	// Decode from JSON to PHP object format if $json is false
	// 
	// $verb - GET, PUT, POST, DELETE
	// $url  - endpoint URL, like https://api.onthecity.org/groups/1234
	// $body - JSON encoded body
	//
	// Returns
	// Results from The City Admin API call
	//
	public function call_city ($verb, $url, $body = NULL)
	{
		try {
			$results = null;
			$curl_results = null;
			$endpoint = $verb . $url;
			$this->debug_message (__FUNCTION__, 'endpoint: ' . $endpoint);
			if( !$this->curl_check_basic_functions() ) {
				throw new Exception('cURL basic functions unavailable');
			}
			$time = time();
			$hmac_signature = $this->get_hmac($endpoint, $time, $body);
			
			$ch = curl_init(); 
			
			// The way The City request signing works, if we urlencode the url 
			// before now, we get signature errors from The City.
			// On their end the url decode happens before the signature test.
			// We are only worrying about space for now. urlencode encodes '=', 
			// which for some reason is not handled well on The City side. On that side
			// the parameters are not mapped correctly into key/value with the encoded '=', 
			// resulting in a signature error.
			$url = str_replace(' ', '+', $url);
			//$parts = parse_url($url);
			//echo '<pre>'; var_dump($parts); echo '</pre>';
			//if($parts['query'])
			//	//$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $parts['query'];
			//	$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . urlencode($parts['query']);
			//else
			//	$url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
			//$this->debug_message (__FUNCTION__, 'remade url: ' . $url);
			curl_setopt($ch, CURLOPT_URL, $url); 
			
			curl_setopt($ch, CURLOPT_HEADER, 1); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			if($verb == 'POST') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
				if(!is_null($body))
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body); 
			}

			// test body with PUT verb- see GROUPS UPDATE in The City spec
			if($verb == 'PUT') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");  
				if(!is_null($body))
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body); 
			}

			if($verb == 'DELETE') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");  
			}

			if(is_null($body)) {
				//$this->debug_message (__FUNCTION__, '$body is null');
				curl_setopt($ch, CURLOPT_HTTPHEADER, 
					array('X-City-Sig: ' . $hmac_signature, 
						'X-City-User-Token: ' . $this->token,
						'X-City-Time: ' . $time,
						'Accept: application/vnd.thecity.admin.v1+json',
						'Content-Type: application/json', // 2012-09-19 test
						'Content-Length: 0', // 2012-09-19 test
						));
			}
			else
				curl_setopt($ch, CURLOPT_HTTPHEADER, 
					array('X-City-Sig: ' . $hmac_signature, 
						'X-City-User-Token: ' . $this->token,
						'X-City-Time: ' . $time,
						'Accept: application/vnd.thecity.admin.v1+json',
						'Content-Type: application/json',
						'Content-Length: ' . strlen($body),
						));

			// This CURLOPT_SSL_VERIFIER option is only for testing, needed to work on XAMPP. 
			//$this->ssl_verify_peer = false; 
			//$this->debug_message (__FUNCTION__, '<span style="color:red;font-weight:bold;">Remove the above ssl_verify_peer setting before release!!</span>');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verify_peer); 

			$curl_results = curl_exec($ch); 
			$this->last_status_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$this->debug_message (__FUNCTION__, 'HTTP Status: ' . $this->last_status_code);
			
			curl_close($ch); 
			if(!$curl_results)
				throw new Exception('curl_exec failed.');
			
			//$this->debug_message (__FUNCTION__, '<pre><p class="ca-debug">curl_results:<br>' . $curl_results . '</p></pre>'); 

			$this->last_headers = $this->http_parse_headers($curl_results);
			$this->last_response_start_line = strtok($curl_results, "\r");

			$results = strstr( $curl_results, "\r\n\r\n");
			if(!$this->json)
				$results = json_decode($results, true); // map into associative arrays

			return $results;
		}
		catch (Exception $e) {
			// pass exception up to caller with info for handle_exception()
			throw new Exception('Exception in ' . __METHOD__ . ': ' . $e->getMessage() . ' City endpoint: ' . $endpoint);
		}
	}
	
	public function set_key($apikey) 
	{
		$this->key = $apikey;
	}
	public function set_token($apitoken)
	{
		$this->token = $apitoken;
	}
	public function get_last_headers()
	{
		return $this->last_headers;
	}
	public function get_last_response_start_line()
	{
		return $this->last_response_start_line;
	}
	public function get_last_status_code()
	{
		return $this->last_status_code;
	}
	
	public function get_ratelimit_limit_by_account()
	{
		return $this->last_headers['X-City-Ratelimit-Limit-By-Account'];
	}
	public function get_ratelimit_limit_by_ip()
	{
		return $this->last_headers['X-City-Ratelimit-Limit-By-Ip'];
	}
	public function get_ratelimit_remaining_by_account()
	{
		return $this->last_headers['X-City-Ratelimit-Remaining-By-Account'];
	}
	public function get_ratelimit_remaining_by_ip()
	{
		return $this->last_headers['X-City-Ratelimit-Remaining-By-Ip'];
	}

	// $args - optional args
	public function users_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/users';
			if(!is_null($args)) 
				$url = $this->add_querystring($url, $args);
			
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_show($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid;
			if(!is_null($args)) 
				$url .= '?' . http_build_query($args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_create($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/users';
			if(is_null($args))
				throw new Exception('Parameters needed.');
				
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_update($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/users/' . $userid;
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_destroy($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid)) 
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_count ($filter = NULL)
	{
		try {	
			$url = CITYAPIBASEURL . '/users/count';
			if(!is_null($filter))
				$url .= '?filter=' . $filter;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_memberize($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('Parameters needed.');
				
			$url = CITYAPIBASEURL . '/users/' . $userid . '/memberize';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_dememberize($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid)) // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/dememberize';
			//return $this->call_city('PUT', $url, '{"":""}'); // PUT needs a body?
			return $this->call_city('PUT', $url, '{"":null}'); // PUT needs a body?
			//return $this->call_city('PUT', $url, json_encode(null)); // PUT needs a body?
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_deactivate($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
				
			$url = CITYAPIBASEURL . '/users/' . $userid . '/deactivate';
			return $this->call_city('PUT', $url, is_null($args) ? NULL : json_encode($args)); // if $args is supplied, PUT them as JSON body
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	public function users_bulk_memberize ($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/users/bulk_memberize';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_bulk_deactivate ($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/users/bulk_deactivate';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_addresses_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid)) 
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/addresses';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_addresses_show($userid = NULL, $addressid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid addressid: $addressid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($addressid)) // $addressid is required
				throw new Exception('addressid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/addresses/' . $addressid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_addresses_create ($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/addresses';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_addresses_update ($userid = NULL, $addressid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid addressid: $addressid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($addressid)) // $addressid is required
				throw new Exception('addressid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/addresses/' . $addressid;
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_addresses_destroy ($userid = NULL, $addressid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid addressid: $addressid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($addressid)) // $addressid is required
				throw new Exception('addressid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/addresses/' . $addressid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_admin_privileges_index($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid)) 
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/admin_privileges';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_admin_privileges_create ($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/admin_privileges';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_barcodes_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/barcodes';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	// $barcode can be barcode or barcodeid
	public function users_barcodes_show($userid = NULL, $barcode = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid barcode: $barcode");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($barcode)) // $barcode is required
				throw new Exception('barcode parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/barcodes/' . $barcode;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_barcodes_create($userid = NULL, $barcode = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid barcode: $barcode");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid needed.');
			if(is_null($barcode))  // $barcode is required
				throw new Exception('barcode needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/barcodes?barcode=' . $barcode;
			return $this->call_city('POST', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	// barcodeid can be the barcode id or the barcode
	public function users_barcodes_destroy($userid = NULL, $barcodeid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid barcode: $barcode");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($barcodeid)) // $barcodeid is required
				throw new Exception('barcodeid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/barcodes/' . $barcodeid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_checkins_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/checkins';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_checkins_show($userid = NULL, $checkinid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid checkin: $checkinid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($checkinid)) // $checkinid is required
				throw new Exception('checkinid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/checkins/' . $checkinid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_checkins_last($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/checkins/last';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_event_attendances_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/event_attendances';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_event_attendances_show($userid = NULL, $attendanceid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid attendanceid: $attendanceid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($attendanceid)) // $attendanceid is required
				throw new Exception('attendanceid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/event_attendances/' . $attendanceid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_event_attendances_recent($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/event_attendances/recent';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_event_attendances_dates($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/event_attendances/dates';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_invitations_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/invitations';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_invitations_create($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args))  // $groupid is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/invitations';

			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_family_index($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/family';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	/**
	 *
	 *
	 * $args must contain family_role and either family_id or family_external_id
	 *
	 */
	public function users_family_create($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/family';
			$url = $this->add_querystring($url, $args);
			return $this->call_city('POST', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	/**
	 *
	 *
	 * $args must contain the new family_role
	 *
	 */
	public function users_family_update($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/family';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_family_destroy($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/family';
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_notes_index($userid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/notes';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_notes_show($userid = NULL, $noteid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid noteid: $noteid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($noteid))  // $noteid is required
				throw new Exception('noteid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/notes/' . $noteid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	/**
	 *
	 * $args must contain body and visible_to
	 *
	 */	public function users_notes_create($userid = NULL, $args = NULL)
	 {
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/notes';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_processes_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/processes';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_processes_show($userid = NULL, $processid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid processid: $processid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($processid))  // $processid is required
				throw new Exception('processid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/processes/' . $processid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_processes_answers_index($userid = NULL, $processid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid  processid: $processid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($processid))  // $processid is required
				throw new Exception('processid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/processes/' . $processid . '/answers';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_processes_notes_index($userid = NULL, $processid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid  processid: $processid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($processid))  // $processid is required
				throw new Exception('processid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/processes/' . $processid . '/notes';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_roles_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_roles_show($userid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid  roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	/**
	 * $args must contain group_id and role title
	 */
	public function users_roles_create($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('POST', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_roles_destroy($userid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid  roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_roles_activate($userid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid)) // $roleid is required
				throw new Exception('roleid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid . '/activate';
			return $this->call_city('PUT', $url, '{"":""}');
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_roles_deactivate($userid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid)) // $roleid is required
				throw new Exception('roleid parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid . '/deactivate';
			return $this->call_city('PUT', $url, '{"":""}');
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_roles_promote ($userid, $roleid, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid)) // $roleid is required
				throw new Exception('roleid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid . '/promote';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function users_roles_demote ($userid, $roleid, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid roleid: $roleid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($roleid)) // $roleid is required
				throw new Exception('roleid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/users/' . $userid . '/roles/' . $roleid . '/promote';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function users_skills_index($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/skills';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	/**
	 * $args must contain either name or skill_id
	 */
	public function users_skills_create($userid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($args) && is_null(args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/skills';
				
			return $this->call_city('POST', $url, is_null($args) ? NULL : json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	// skill can be the skill name or the skill id
	public function users_skills_destroy($userid = NULL, $skill = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "userid: $userid  skill: $skill");
			
			if(is_null($userid))  // $userid is required
				throw new Exception('userid parameter needed.');
			if(is_null($skill))  // $skill is required
				throw new Exception('skill parameter needed.');
			
			$url = CITYAPIBASEURL . '/users/' . $userid . '/skills/' . rawurlencode($skill);
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/groups';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_show($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			
			$url = CITYAPIBASEURL . '/groups/' . $groupid;
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_create($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			if(is_null($args))  // $args is required
				throw new Exception('Parameters needed.');
			
			$url = CITYAPIBASEURL . '/groups';
				
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	//
	// example args
	// $args = array('name' => 'My Group', 'description' => 'Awesome serving here!', 'auto_approve_invites' => 'true');
	//
	public function groups_update($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid;
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_count()
	{
		try {
			return $this->call_city('GET', CITYAPIBASEURL . '/groups/count');
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_schedule_archive($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/schedule_archive';
			return $this->call_city('PUT', $url, json_encode(NULL));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_schedule_deletion($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/schedule_deletion';
			return $this->call_city('PUT', $url, json_encode(NULL));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_undo_scheduled_archive($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/undo_scheduled_archive';
			return $this->call_city('PUT', $url, json_encode(NULL));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_undo_scheduled_deletion($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/undo_scheduled_deletion';
			return $this->call_city('PUT', $url, json_encode(NULL));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_unarchive($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/unarchive';
			return $this->call_city('PUT', $url, json_encode(NULL));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	public function groups_addresses_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/addresses';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_addresses_show($groupid = NULL, $addressid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  addressid: $addressid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($addressid))  // $addressid is required
				throw new Exception('addressid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/addresses/' . $addressid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_addresses_create($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/addresses';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_addresses_update($groupid = NULL, $addressid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  addressid: $addressid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($addressid))  // $addressid is required
				throw new Exception('addressid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/addresses/' . $addressid;
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	public function groups_addresses_destroy($groupid = NULL, $addressid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  addressid: $addressid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($addressid))  // $addressid is required
				throw new Exception('addressid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/addresses/' . $addressid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_checkins_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/checkins';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_checkins_show($groupid = NULL, $checkinid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  checkinid: $checkinid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($checkinid))  // $checkinid is required
				throw new Exception('checkinid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/checkins/' . $checkinid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_event_attendances_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/event_attendances';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_event_attendances_show($groupid = NULL, $attendanceid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  attendanceid: $attendanceid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($attendanceid))  // $attendanceid is required
				throw new Exception('attendanceid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/event_attendances/' . $attendanceid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_exports_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/exports';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_exports_show($groupid = NULL, $exportsid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  exportsid: $exportsid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($exportsid))  // $exportsid is required
				throw new Exception('exportsid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/exports/' . $exportsid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	// TODO: get clarification on querystring or JSON body for user_type parameter
	public function groups_exports_create($groupid = NULL, $user_type = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  user_type: $user_type");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($user_type))  // $user_type is required
				throw new Exception('user_type parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/exports';
			return $this->call_city('POST', $url, json_encode(array('user_type' => $user_type)));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_health_assessments_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/health_assessments';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_invitations_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/invitations';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	// TODO: get clarification on querystring or JSON body for args
	public function groups_invitations_create($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/invitations';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	//
	// example args
	// args is optional
	// $args = array('include_inactive' => 'false', 'page' => '1', 'title' => 'Managers');
	//
	public function groups_roles_index($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_show($groupid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_create($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles';
			//if(!is_null($args))
			//	$url = $this->add_querystring($url, $args);
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_roles_destroy($groupid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_activate($groupid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid . '/activate';
			return $this->call_city('PUT', $url, '{"":""}');
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_deactivate($groupid = NULL, $roleid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid . '/deactivate';
			return $this->call_city('PUT', $url, '{"":""}');
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_promote($groupid = NULL, $roleid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid ");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid . '/promote';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function groups_roles_demote($groupid = NULL, $roleid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  roleid: $roleid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($roleid))  // $roleid is required
				throw new Exception('roleid parameter needed.');
			if(is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/' . $roleid . '/demote';
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_roles_deactivate_all($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/roles/deactivate_all';
			return $this->call_city('POST', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_tags_index($groupid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
				
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/tags';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	// 
	// either name or tag_id is required
	// example args: array('name' => 'College Age')
	// The name must be an existing group tag in your City
	//
	public function groups_tags_create($groupid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($args))  // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/tags';
			return $this->call_city('POST', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function groups_tags_destroy($groupid = NULL, $tag = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "groupid: $groupid  tag: $tag");
			
			if(is_null($groupid))  // $groupid is required
				throw new Exception('groupid parameter needed.');
			if(is_null($tag))  // $tag is required
				throw new Exception('tag parameter needed.');
			
			$url = CITYAPIBASEURL . '/groups/' . $groupid . '/tags/' . $tag;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function addresses_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/addresses';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function addresses_show($addressid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "addressid: $addressid");
			
			if(is_null($addressid))  // $addressid is required
				throw new Exception('addressid parameter needed.');
				
			$url = CITYAPIBASEURL . '/addresses/' . $addressid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function addresses_groups($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/addresses/groups';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function barcodes_show($barcode = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "barcode: $barcode");
			
			if(is_null($barcode))  // $barcode is required
				throw new Exception('barcode parameter needed.');
				
			$url = CITYAPIBASEURL . '/barcodes/' . $barcode;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function campuses_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/campuses';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function campuses_show($campusid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "campusid: $campusid");
			
			if(is_null($campusid))  // $campusid is required
				throw new Exception('campusid parameter needed.');
				
			$url = CITYAPIBASEURL . '/campuses/' . $campus;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function checkins_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/checkins';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function checkins_show($checkinid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "checkinid: $checkinid");
			
			if(is_null($checkinid))  // $checkinid is required
				throw new Exception('checkinid parameter needed.');
				
			$url = CITYAPIBASEURL . '/checkins/' . $checkinid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function checkins_by_barcode($barcode = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "barcode: $barcode");
			
			if(is_null($barcode))  // $barcode is required
				throw new Exception('barcode parameter needed.');
				
			$url = CITYAPIBASEURL . '/checkins/by_barcode/' . $barcode;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function checkins_by_callboard_number($callboard_number = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "callboard_number: $callboard_number");
			
			if(is_null($callboard_number))  // $callboard_number is required
				throw new Exception('callboard_number parameter needed.');
				
			$url = CITYAPIBASEURL . '/checkins/by_callboard_number/' . $callboard_number;
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function checkins_by_pager_number($pager_number = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "pager_number: $pager_number");
			
			if(is_null($pager_number))  // $pager_number is required
				throw new Exception('pager_number parameter needed.');
				
			$url = CITYAPIBASEURL . '/checkins/by_pager_number/' . $pager_number;
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function checkins_by_parent_receipt_barcode($parent_receipt_barcode = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "parent_receipt_barcode: $parent_receipt_barcode");
			
			if(is_null($parent_receipt_barcode))  // $parent_receipt_barcode is required
				throw new Exception('parent_receipt_barcode parameter needed.');
				
			$url = CITYAPIBASEURL . '/checkins/by_parent_receipt_barcode/' . $parent_receipt_barcode;
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function churches_index()
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/churches';
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function custom_fields_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/custom_fields';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_show($custom_fieldid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "custom_fieldid: $custom_fieldid");
			
			if(is_null($custom_fieldid))  // $custom_fieldid is required
				throw new Exception('custom_fieldid parameter needed.');
				
			$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_create($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);

			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/custom_fields';

			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_update($custom_fieldid = NULL, $args = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "custom_fieldid: $custom_fieldid");

			if (is_null($custom_fieldid)) // $custom_fieldid is required
				throw new Exception('custom_fieldid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid ;

			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_destroy($custom_fieldid = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "custom_fieldid: $custom_fieldid");

			if (is_null($custom_fieldid)) // $custom_fieldid is required
				throw new Exception('custom_fieldid parameter needed.');

			$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid ;

			return $this->call_city('DELETE', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_users_index($custom_fieldid = NULL, $args = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "custom_fieldid: $custom_fieldid");

			if(is_null($custom_fieldid))  // $custom_fieldid is required
				throw new Exception('custom_fieldid parameter needed.');

			$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid . '/users';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);

			//$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid . '/users';
			//$this->debug_message (__FUNCTION__, "url: $url");
			//if(!is_null($args))
			//	$url .= '?' . http_build_query($args);
			//$this->debug_message (__FUNCTION__, "url: $url");

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function custom_fields_users_count($custom_fieldid = NULL, $args = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "custom_fieldid: $custom_fieldid");

			if(is_null($custom_fieldid))  // $custom_fieldid is required
				throw new Exception('custom_fieldid parameter needed.');

			$url = CITYAPIBASEURL . '/custom_fields/' . $custom_fieldid . '/users/count';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	// familyid can be family id or family external id
	public function families_show($familyid = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "familyid: $familyid");

			if(is_null($familyid))  // $familyid is required
				throw new Exception('familyid parameter needed.');

			$url = CITYAPIBASEURL . '/families/' . $familyid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function families_create ($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);

			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/families';

			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function donations_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);

			$url = CITYAPIBASEURL . '/donations';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function donations_show($donationid = NULL) {
		try {	
			$this->debug_message (__FUNCTION__, "donationid: $donationid");
			
			if(is_null($donationid))  // $donationid is required
				throw new Exception('donation id parameter needed.');

			$url = CITYAPIBASEURL . '/donations/' . $donationid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function donations_exports_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);

			$url = CITYAPIBASEURL . '/donations/exports';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function donations_exports_show($exportid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "exportid: $exportid");
			
			if(is_null($exportid))  // $exportid is required
				throw new Exception('exportid id parameter needed.');

			$url = CITYAPIBASEURL . '/donations/exports/' . $exportid;
			return $this->call_city('GET', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function donations_exports_create($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);

			$url = CITYAPIBASEURL . '/donations/exports';
            
            return $this->call_city('POST', $url, is_null($args) ? $args : json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function donations_exports_destroy($exportid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "exportid: $exportid");

			if (is_null($exportid)) // $exportid is required
				throw new Exception('exportid parameter needed.');

			$url = CITYAPIBASEURL . '/donations/exports/' . $exportid;

			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}	

	public function families_update ($familyid = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($familyid)) // $familyid is required
				throw new Exception('familyid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/families/' . $familyid;
			
			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function families_destroy ($familyid = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($familyid)) // $familyid is required
				throw new Exception('familyid parameter needed.');
			
			$url = CITYAPIBASEURL . '/families/' . $familyid;
			
			return $this->call_city('DELETE', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function funds_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/funds';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function funds_show($fund_id = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "fund_id: $fund_id");
			
			if(is_null($fund_id))  // $fund_id is fund_id
				throw new Exception('fund_id parameter needed.');
				
			$url = CITYAPIBASEURL . '/funds/' . $fund_id;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function funds_create($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);

			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');

			$url = CITYAPIBASEURL . '/funds';

			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function funds_update($fund_id = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "$fund_id: fund_id");
			
			if (is_null($fund_id)) // $fund_id is required
				throw new Exception('fund_id parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
	
			$url = CITYAPIBASEURL . '/funds/' . $fund_id;
			
			return $this->call_city('PUT', $url, json_encode($args));
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function funds_destroy($fund_id = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "fund_id: $fund_id");

			if (is_null($fund_id)) // $fund_id is required
				throw new Exception('fund_id parameter needed.');
				
			$url = CITYAPIBASEURL . '/funds/' . $fund_id;

            return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}	
	
	public function invitations_index ($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/invitations';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function invitations_bulk_invite ($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
	
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/invitations';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	public function metrics_index ($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/metrics';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_show ($metricid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid");
			
			if(is_null($metricid))  // $metricid is required
				throw new Exception('metricid parameter needed.');
				
			$url = CITYAPIBASEURL . '/metrics/' . $metricid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_create ($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_update ($metricid = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics/' . $metricid;
			
			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_destroy ($metricid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics';
			
			return $this->call_city('DELETE', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	
	public function metrics_measurements_index ($metricid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
	
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_show ($metricid = NULL, $measurementsid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid measurementsid: $measurementsid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			if (is_null($measurementsid)) // $measurementsid is required
				throw new Exception('measurementsid parameter needed.');
	
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements/' . $measurementsid;
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_create ($metricid = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__, "metricid: $metricid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_update ($metricid = NULL, $measurementid = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__, "metricid: $metricid measurementid: $measurementid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			if (is_null($measurementid)) // $measurementid is required
				throw new Exception('measurementid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements/' . $measurementid;
			
			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_destroy ($metricid = NULL, $measurementid = NULL)
	{
		try {
			$this->debug_message (__FUNCTION__, "metricid: $metricid measurementid: $measurementid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
			if (is_null($measurementid)) // $measurementid is required
				throw new Exception('measurementid parameter needed.');
			
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements/' . $measurementid;
			
			return $this->call_city('DELETE', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_last ($metricid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
	
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements/last';
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function metrics_measurements_values ($metricid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "metricid: $metricid");
			
			if (is_null($metricid)) // $metricid is required
				throw new Exception('metricid parameter needed.');
	
			$url = CITYAPIBASEURL . '/metrics/' . $metricid . '/measurements/values';
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function pledges_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/pledges';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function pledges_show($pledge = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "pledge: $pledge");
			
			if(is_null($pledge))  // $pledge is required
				throw new Exception('pledge parameter needed.');
				
			$url = CITYAPIBASEURL . '/pledges/' . $pledge;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function roles_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/roles';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function skills_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/skills';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_show ($skillid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "skillid: $skillid");
			
			if(is_null($skillid))  // $skillid is required
				throw new Exception('skillid parameter needed.');
				
			$url = CITYAPIBASEURL . '/skills/' . $skillid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_create ($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/skills';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_destroy ($skillid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "skillid: $skillid");
			
			if (is_null($skillid)) // $skillid is required
				throw new Exception('skillid parameter needed.');
			
			$url = CITYAPIBASEURL . '/skills/' . $skillid;
			
			return $this->call_city('DELETE', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_users_index ($skillid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "skillid: $skillid");
			
			if(is_null($skillid))  // $skillid is required
				throw new Exception('skillid parameter needed.');
				
			$url = CITYAPIBASEURL . '/skills/' . $skillid . '/users';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_users_user_ids ($skillid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "skillid: $skillid");
			
			if(is_null($skillid))  // $skillid is required
				throw new Exception('skillid parameter needed.');
				
			$url = CITYAPIBASEURL . '/skills/' . $skillid . '/user_ids';

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function skills_users_count ($skillid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "skillid: $skillid");
			
			if(is_null($skillid))  // $skillid is required
				throw new Exception('skillid parameter needed.');
				
			$url = CITYAPIBASEURL . '/skills/' . $skillid . '/users/count';

			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function tags_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/tags';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function tags_show ($tagid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "tagid: $tagid");
			
			if(is_null($tagid))  // $tagid is required
				throw new Exception('tagid parameter needed.');
				
			$url = CITYAPIBASEURL . '/tags/' . $tagid;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function tags_create ($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/tags';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function tags_update ($tagid = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__, "tagid: $tagid");
			
			if (is_null($tagid)) // $tagid is required
				throw new Exception('tagid parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/tags/' . $tagid;
			
			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function tags_destroy ($tagid = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "tagid: $tagid");
			
			if (is_null($tagid)) // $tagid is required
				throw new Exception('tagid parameter needed.');
			
			$url = CITYAPIBASEURL . '/tags/' . $tagid;
			
			return $this->call_city('DELETE', $url);
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function tags_groups_index ($tagid = NULL, $args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "tagid: $tagid");
			
			if(is_null($tagid))  // $tagid is required
				throw new Exception('tagid parameter needed.');
				
			$url = CITYAPIBASEURL . '/tags/' . $tagid . '/groups';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
	
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function terminology_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/terminology';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function terminology_show ($native_label = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "native_label: $native_label");
			
			if(is_null($native_label))  // $native_label is required
				throw new Exception('native_label parameter needed.');
				
			$url = CITYAPIBASEURL . '/terminology/' . $native_label;
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function terminology_update ($native_label = NULL, $args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__, "native_label: $native_label");
			
			if (is_null($native_label)) // $native_label is required
				throw new Exception('native_label parameter needed.');
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/terminology/' . $native_label;
			
			return $this->call_city('PUT', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

	public function web_hooks_index($args = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__);
			
			$url = CITYAPIBASEURL . '/web_hooks';
			if(!is_null($args))
				$url = $this->add_querystring($url, $args);
				
			return $this->call_city('GET', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function web_hooks_create($args = NULL)
	{
		try {
			$this->debug_message(__FUNCTION__);
			
			if (is_null($args)) // $args is required
				throw new Exception('args parameter needed.');
			
			$url = CITYAPIBASEURL . '/web_hooks';
			
			return $this->call_city('POST', $url, json_encode($args));
		}
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}
	public function web_hooks_destroy($web_hook_id = NULL)
	{
		try {	
			$this->debug_message (__FUNCTION__, "web_hook_id: $web_hook_id");
			
			if(is_null($web_hook_id))  // $web_hook_id is required
				throw new Exception('web_hook_id parameter needed.');
				
			$url = CITYAPIBASEURL . '/web_hooks/' . $web_hook_id;
			return $this->call_city('DELETE', $url);
		} 
		catch (Exception $e) {
			return $this->handle_exception(__METHOD__, $e->getMessage());
		}
	}

}

