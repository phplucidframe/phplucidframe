<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for session handling and flash messaging
 *
 * @package		LC\Helpers\Session
 * @since		PHPLucidFrame v 1.0.0
 * @copyright	Copyright (c), PHPLucidFrame.
 * @author 		Sithu K. <sithukyaw.com>
 * @link 		http://phplucidframe.sithukyaw.com
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * Set a message or value in Session using a name
 *
 * @param $name string The session variable name to store the value
 *  It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param mixed $value The value to be stored.
 * @param boolean $serialize The value is to be serialized or not
 *
 * @return void
 */
function session_set($name, $value='', $serialize=false){
	__dotNotationToArray($name, 'session', $value, $serialize);
}
/**
 * Get a message or value of the given name from Session
 *
 * @param string $name 	The session variable name to retrieve its value
 *   It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param boolean $unserialize The value is to be unserialized or not
 *
 * @return mixed The value from SESSION
 */
function session_get($name, $unserialize=false){
	$value = __dotNotationToArray($name, 'session');
	return ($unserialize && is_string($value)) ? unserialize($value) : $value;
}
/**
 * Delete a message or value of the given name from Session
 *
 * @param string $name The session variable name to delete its value
 * @return void
 */
function session_delete($name){
	$name = S_PREFIX . $name;
	if(isset($_SESSION[$name])){
		unset($_SESSION[$name]);
		return true;
	}
	$keys = explode('.', $name);
	$firstKey = array_shift($keys);
	if(count($keys)){
		if(!isset($_SESSION[$firstKey])) return false;
		$array = &$_SESSION[$firstKey];
		$parent = &$_SESSION[$firstKey];
		$found = true;
		foreach($keys as $k) {
			if(isset($array[$k])){
				$parent = &$array;
				$array = &$array[$k];
			}else{
				return false;
			}
		}
		$array = NULL;
		unset($array);
		unset($parent[$k]);
	}
	return true;
}

if(!function_exists('flash_set')){
/**
 * Set the flash message in session
 * This function is overwritable from the custom helpers/session_helper.php
 *
 * @param string $msg The message to be shown
 * @param string $name The optional session name to store the message
 * @param string $class The HTML class name; default is success
 *
 * @return void
 */
	function flash_set($msg, $name='', $class='success'){
		$msg = '<span class="'.$class.'">'.$msg.'</span>';
		$msg = '<div class="message '.$class.'" style="display:block;"><ul><li>'.$msg.'</li></ul></div>';

		if($name) $_SESSION[S_PREFIX.'flashMessage'][$name] = $msg;
		else $_SESSION[S_PREFIX.'flashMessage'] = $msg;
	}
}

if(!function_exists('flash_get')){
/**
 * Get the flash message from session and then delete it
 * This function is overwritable from the custom helpers/session_helper.php
 *
 * @param string $name The optional session name to retrieve the message from
 * @param string $class The HTML class name; default is success
 *
 * @return string The HTML message
 */
	function flash_get($name='', $class='success'){
		$message = '';
		if($name){
			if(isset($_SESSION[S_PREFIX.'flashMessage'][$name])){
				$message = $_SESSION[S_PREFIX.'flashMessage'][$name];
				unset($_SESSION[S_PREFIX.'flashMessage'][$name]);
			}
		}else{
			if(isset($_SESSION[S_PREFIX.'flashMessage'])){
				$message = $_SESSION[S_PREFIX.'flashMessage'];
				unset($_SESSION[S_PREFIX.'flashMessage']);
			}
		}
		return $message;
	}
}
/**
* Send a cookie
* Convenience method for setcookie()
* 
* @param string $name The name of the cookie. 'cookiename' is called as cookie_get('cookiename') or $_COOKIE['cookiename']
* @param mixed $value The value of the cookie. This value is stored on the clients computer
* @param int $expiry The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
*  In other words, you'll most likely set this with the time() function plus the number of seconds before you want it to expire.
*  If f set to 0, or omitted, the cookie will expire at the end of the session
* @param string $path The path on the server in which the cookie will be available on. The default path '/' will make it available to the entire domain.
* @param string $domain The domain that the cookie is available to. If it is not set, it depends on the configuration variable $lc_siteDomain.
* @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
* @param bool $httpOnly When TRUE the cookie will be made accessible only through the HTTP protocol.
*  This means that the cookie won't be accessible by scripting languages, such as JavaScript
* 
* @see http://php.net/manual/en/function.setcookie.php
* 
* @return void
*/
function cookie_set($name, $value, $expiry=0, $path='/', $domain='', $secure=false, $httpOnly=false){
	if(!$domain) $domain = _cfg('siteDomain');
	$name = preg_replace('/^('.S_PREFIX.')/', '', $name);
	$name = S_PREFIX . $name;
	if($expiry > 0) $expiry = time() + $expiry;
	setcookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
}
/**
* Get a cookie
* Convenience method to access $_COOKIE[cookiename] 
* @param string $name The name of the cookie to retrieve
* 
* @return mixed
*  The value of the cookie if found.
*  NULL if not found.
*  The entire $_COOKIE array if $name is not provided.
*/
function cookie_get($name=''){
	if(empty($name)) return $_COOKIE;
	$name = preg_replace('/^('.S_PREFIX.')/', '', $name);
	$name = S_PREFIX . $name;
	if(isset($_COOKIE[$name])) return $_COOKIE[$name];
	else return NULL;
}
/**
* Delete a cookie
* Convenience method to delete $_COOKIE['cookiename'] 
* @param string $name The name of the cookie to delete
* @param string $path The path on the server in which the cookie will be available on.
*  This would be the same value used for cookie_set().
* 
* @return bool TRUE for the successful delete; FALSE for no delete.
*/
function cookie_delete($name, $path='/'){
	if(empty($name)) return $_COOKIE;
	$name = preg_replace('/^('.S_PREFIX.')/', '', $name);
	$name = S_PREFIX . $name;
	if(isset($_COOKIE[$name])){
		unset($_COOKIE[$name]);
		setcookie($name, NULL, -1, $path);
		return true;
	}
	return (!isset($_COOKIE[$name])) ? true : false;
}