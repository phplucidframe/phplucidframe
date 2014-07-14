<?php
/**
 * PHP 5
 *
 * LucidFrame : Simple & Flexible PHP Development
 * Copyright (c), LucidFrame.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @package     LC.helpers 
 * @author		Sithu K. <cithukyaw@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
/**
 * Set a message or value in Session using a name
 *
 * @param $name string The session variable name to store the value
 *		It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param mixed 	$value The value to be stored. 
 * @param boolean 	$serialize The value is to be serialized or not
 *
 * @return void
 */
function session_set($name, $value='', $serialize=false){
	setSession($name, $value, $serialize);
}
/**
 * Alias of session_set()
 * Set a message or value in Session using a name
 */
function setSession($name, $value='', $serialize=false){
	if(strpos($name, '.') !== false){
		$names = explode('.', $name);
		$name = array_shift($names);
		if($serialize){
			$code = '$_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"] = serialize($value);';
		}else{
			$code = '$_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"] = $value;';
		}
		eval($code);
	}else{
		$_SESSION[S_PREFIX . $name] = ($serialize) ? serialize($value) : $value;
	}
}
/**
 * Get a message or value of the given name from Session
 * 
 * @param string $name 	The session variable name to retrieve its value
 						It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param boolean $unserialize The value is to be unserialized or not
 *
 * @return mixed The value from SESSION
 */
function session_get($name, $unserialize=false){
	return getSession($name, $unserialize);
}
/**
 * Alias of session_get()
 * Get a message or value of the given name from Session
 */
function getSession($name, $unserialize=false){
	if(strpos($name, '.') !== false){
		$names = explode('.', $name);
		$name = array_shift($names);
		$code  = '$value = isset($_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"])';
		$code .= ' ? $_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"]';
		$code .= ' : "";';
		eval($code);
		return ($unserialize) ? unserialize($value) : $value;
	}else{
		if(isset($_SESSION[S_PREFIX . $name])) return ($unserialize) ? unserialize($_SESSION[S_PREFIX . $name]) : $_SESSION[S_PREFIX . $name];
		else return '';
	}
}
/**
 * Delete a message or value of the given name from Session
 * 
 * @param string $name The session variable name to delete its value
 * @return void
 */
function session_delete($name){
	deleteSession($name);
}
/**
 * Alias of session_delete()
 * Delete a message or value of the given name from Session
 */
function deleteSession($name){
	if(strpos($name, '.') !== false){
		$names = explode('.', $name);
		$name = array_shift($names);
		$code  = 'if( isset($_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"]) )';
		$code .= ' unset($_SESSION[S_PREFIX . $name]["' . implode('"]["', $names) . '"]);';
		eval($code);
	}else{
		if(isset($_SESSION[S_PREFIX . $name])) unset($_SESSION[S_PREFIX . $name]);
	}	
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
		setFlash($msg, $name, $class);
	}
}

if(!function_exists('setFlash')){
/**
 * Alias of flash_set()
 * Set the flash message in session
 * This function is overwritable from the custom helpers/session_helper.php 
 */
	function setFlash($msg, $name='', $class='success'){
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
 * @param $name  string The optional session name to retrieve the message from
 * @param $class string The HTML class name; default is success
 *
 * @return string The HTML message
 */
	function flash_get($name='', $class='success'){
		return getFlash($name, $class);
	}
}

if(!function_exists('getFlash')){
/**
 * Alias of flash_get()
 * Get the flash message from session and then delete it
 * This function is overwritable from the custom helpers/session_helper.php
 */
	function getFlash($name='', $class='success'){
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