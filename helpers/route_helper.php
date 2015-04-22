<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for system routing
 *
 * @package     LC\Helpers\Routing
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/** @type array Array of the custom route configuration */
$lc_routes = array();
/** @type string The clean route without query string or without file name */
$lc_cleanRoute = '';

/**
 * @internal
 * Initialize URL routing
 */
function route_init(){
	if (!isset($_SERVER['HTTP_REFERER'])) {
		$_SERVER['HTTP_REFERER'] = '';
	}
	if (!isset($_SERVER['SERVER_PROTOCOL']) || ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')) {
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
	}

	if (isset($_SERVER['HTTP_HOST'])) {
		// As HTTP_HOST is user input, ensure it only contains characters allowed
		// in hostnames. See RFC 952 (and RFC 2181).
		// $_SERVER['HTTP_HOST'] is lowercased here per specifications.
		$_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
		if (!_validHost($_SERVER['HTTP_HOST'])) {
		  // HTTP_HOST is invalid, e.g. if containing slashes it may be an attack.
		  header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		  exit;
		}
	}
	else {
		// Some pre-HTTP/1.1 clients will not send a Host header. Ensure the key is
		// defined for E_ALL compliance.
		$_SERVER['HTTP_HOST'] = '';
	}
	// When clean URLs are enabled, emulate ?route=foo/bar using REQUEST_URI. It is
	// not possible to append the query string using mod_rewrite without the B
	// flag (this was added in Apache 2.2.8), because mod_rewrite unescapes the
	// path before passing it on to PHP. This is a problem when the path contains
	// e.g. "&" or "%" that have special meanings in URLs and must be encoded.
	$_GET[ROUTE] = route_request();
	_cfg('cleanRoute', $_GET[ROUTE]);
}
/**
 * @internal
 *
 * Returns the requested URL path of the page being viewed.
 * Examples:
 * - http://example.com/foo/bar returns "foo/bar".
 *
 * @return string The requested URL path.
 */
function route_request() {
	global $lc_baseURL;
	global $lc_languages;
	global $lc_lang;
	global $lc_cleanURL;

	if(_getLangInURI() === false){
		$lc_lang = $lang = _cfg('defaultLang');
	}

	if (isset($_GET[ROUTE]) && is_string($_GET[ROUTE])) {
		// This is a request with a ?route=foo/bar query string.
		$path = $_GET[ROUTE];
		if(isset($_GET['lang']) && $_GET['lang']){
			$lang = strip_tags(urldecode($_GET['lang']));
			$lang = rtrim($lang, '/');
			if( array_key_exists($lang, $lc_languages) ){
				$lc_lang = $lang;
			}
		}
	}
	elseif (isset($_SERVER['REQUEST_URI'])) {
		// This request is either a clean URL, or 'index.php', or nonsense.
		// Extract the path from REQUEST_URI.
		$request_path = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));
		$request_path = str_replace($lc_baseURL, '', ltrim($request_path, '/'));
		$request_path = ltrim($request_path, '/');

		$lang = _getLangInURI();
		if($lang){
			$lc_lang = $lang;
			$path = trim($request_path, '/');
			if(strpos($path, $lc_lang) === 0){
				$path = substr($path, strlen($lang));
			}
		}else{
			$path = trim($request_path);
		}

		// If the path equals the script filename, either because 'index.php' was
		// explicitly provided in the URL, or because the server added it to
		// $_SERVER['REQUEST_URI'] even when it wasn't provided in the URL (some
		// versions of Microsoft IIS do this), the front page should be served.
		if ($path == basename($_SERVER['PHP_SELF'])) {
		  $path = '';
		}
	}
	else {
		// This is the front page.
		$path = '';
	}

	// Under certain conditions Apache's RewriteRule directive prepends the value
	// assigned to $_GET[ROUTE] with a slash. Moreover we can always have a trailing
	// slash in place, hence we need to normalize $_GET[ROUTE].
	$path = trim($path, '/');

	$protocol = current(explode('/', $_SERVER['SERVER_PROTOCOL']));
	$base = strtolower($protocol) . '://' . $_SERVER['HTTP_HOST'];
	if($lc_baseURL) $base .= '/' . $lc_baseURL;

	/**
	 * @ignore Path to the web root
	 */
	define('WEB_ROOT', $base.'/');
	/**
	 * @ignore
	 */
	define('WEB_APP_ROOT', WEB_ROOT . APP_DIR . '/');
	/**
	 * @ignore Path to the home page
	 */
	define('HOME', WEB_ROOT);

	session_set('lang', $lc_lang);

	return $path;
}
/**
 * Search the physical directory according to the routing path
 *
 * @return mixed The path if found; otherwise return false
 */
function route_search(){
	$q 		= route_path();
	$seg 	= explode('/', $q);
	$count 	= sizeof($seg);
	$sites 	= _cfg('sites');

	if($seg[0] == LC_NAMESPACE && is_array($sites) && array_key_exists(LC_NAMESPACE, $sites)){
		$seg[0] = $sites[LC_NAMESPACE];
	}

	$path = implode('/', $seg);
	if(is_file($path) && file_exists($path)){
		if(count($seg) > 1){
			_cfg('cleanRoute', implode('/', array_slice($seg, 0, count($seg)-1)));
		}else{
			_cfg('cleanRoute', '');
		}
		return $path;
	}

	$append	= array('/index.php', '.php');
	for($i=$count; $i>0; $i--){
		# try to look for
		# ~/path/to/the-given-name/index.php
		# ~/path/to/the-given-name.php
		foreach($append as $a){
			$cleanRoute = implode('/', array_slice($seg, 0, $i));
			$path = $cleanRoute . $a;
			if(is_file($path) && file_exists($path)){
				_cfg('cleanRoute', rtrim($cleanRoute, '/'));
				return $path;
			}
		}
	}
	return false;
}
/**
 * Get the routing path
 *
 * Alias `_r()`
 * @return string
 */
function route_path(){
	$path = '';
	if(isset($_GET[ROUTE])){
		$path = urldecode($_GET[ROUTE]);
	}
	return $path;
}
/**
 * @internal
 * Define the custom routing path
 */
function route_map($path, $to=''){
	global $lc_routes;
	$lc_routes[$path] = $to;

}
/**
 * Return the absolute URL path appended the query string if necessary
 *
 * Alias `_url()`
 *
 * @param string $path Routing path such as "foo/bar"; NULL for the current path
 * @param array $queryStr Query string as
 * 	array(
 * 		$value1, // no key here
 * 		'key1' => $value2,
 * 		'key3' => $value3 or array($value3, $value4)
 * 	)
 * @param string $lang Languague code to be prepended to $path such as "en/foo/bar". It will be useful for site language switch redirect
 *
 * @return string
 *
 */
function route_url($path=NULL, $queryStr=array(), $lang=''){
	global $lc_cleanURL;
	global $lc_translationEnabled;
	global $lc_sites;

	if($lang === false) $forceExcludeLangInURL = true;
	else $forceExcludeLangInURL = false;

	if( stripos($path, 'http') === 0 ){
		return $path;
	}else{
		if(strtolower($path) == 'home'){
			if(isset($GLOBALS['lc_homeRouting']) && $GLOBALS['lc_homeRouting']) $path = $GLOBALS['lc_homeRouting'];
		}

		if($path && is_string($path)){
			$path = rtrim($path, '/');
		}else{
			$r = (_isRewriteRule()) ? REQUEST_URI : route_path();
			$path = route_updateQueryStr($r, $queryStr);
		}
	}

	$q = '';
	if($queryStr && is_array($queryStr) && count($queryStr)){
		foreach($queryStr as $key => $value){
			if(is_array($value)){
				$v = array_map('urlencode', $value);
				$value = implode('/', $v);
			}else{
				$value = urlencode($value);
			}
			if(is_numeric($key)){
				if($lc_cleanURL) $q .= '/' . $value;
				else $q .= '&'.$value;
			}else{
				if($lc_cleanURL) $q .= '/-' . $key . '/' . $value;
				else $q .= '&' . $key . '=' . $value;
			}
		}
	}

	if(is_array($lc_sites) && array_key_exists(LC_NAMESPACE, $lc_sites)){
		$regex = '/\b^('.$lc_sites[LC_NAMESPACE].'){1}\b/i';
		$path = preg_replace($regex, LC_NAMESPACE, $path);
	}

	# If URI contains the language code, force to include it in the URI
	$l = _getLangInURI();
	if(empty($lang) && $l){
		$lang = $l;
	}

	$url = WEB_ROOT;
	if($lang && $lc_translationEnabled && !$forceExcludeLangInURL){
		if($lc_cleanURL) $url .= $lang.'/';
		else $q .= '&lang=' . $lang;
	}

	if(strtolower($path) == 'home') $path = '';

	if($lc_cleanURL){
		$url .= $path . $q;
	}else{
		$url .= $path . '?' . ltrim($q, '&');
		$url = trim($url, '?');
	}
	$url = preg_replace('/(\s){1,}/', '+', $url); # replace the space with "+"
	return $url;
}
/**
 * Update the route path with the given query string
 *
 * @param string $path The route path which may contain the query string
 * @param array $queryStr Query string as
 * 	array(
 * 		$value1, // no key here
 * 		'key1' => $value2,
 * 		'key3' => $value3 or array($value3, $value4)
 * 	)
 * @return string The updated route path
 */
function route_updateQueryStr($path, &$queryStr=array()){
	global $lc_cleanURL;
	if(count($queryStr)){
		if($lc_cleanURL){ # For clean URLs like /path/query/str/-key/value
			foreach($queryStr as $key => $value){
				$route = _arg($key, $path);
				if($route){
					if(is_string($key)){
						$regex = '/(\-'.$key.'\/)';
						if(is_array($route)) $regex .= '('.implode('\/', $route).'+)';
						else $regex .= '('.$route.'+)';
						$regex .= '/i';
					}
					elseif(is_numeric($key)){
						$regex = '/\b('.$route.'){1}\b/i';
					}
					else{
						continue;
					}
				}else{ # if the key could not be retrieved from URI, skip it
					continue;
				}
				if(preg_match($regex, $path)){ # find the key in URI
					if(is_array($value)){
						$v = array_map('urlencode', $value);
						$value = implode('/', $v);
					}else{
						$value = urlencode($value);
					}
					if(is_numeric($key)) $path = preg_replace($regex, $value, $path); # no key
					else $path = preg_replace($regex, '-'.$key.'/'.$value, $path);
					unset($queryStr[$key]); # removed the replaced query string from the array
				}
			}
		}else{ # For unclean URLs like /path/query/str?key=value
			parse_str($_SERVER['QUERY_STRING'], $serverQueryStr);
			$queryStr = array_merge($serverQueryStr, $queryStr);
		}
	}
	return $path;
}

route_init();
