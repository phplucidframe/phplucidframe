<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for input sanitizing, data escaping, CSRF protection and XSS prevention
 *
 * @package     LC\Helpers\Security
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * @internal
 * Check the default security secret to be changed
 */
function security_prerequisite(){
	$defaultSecret = md5('lucidframe');
	$secret = trim(_cfg('securitySecret'));
	if(function_exists('mcrypt_encrypt') && strcmp($secret, $defaultSecret) === 0){
		$msg = 'Change your own security hash in the file "/inc/.secret".';
		$msg .= 'Get your own hash string at <a href="http://phplucidframe.sithukyaw.com/hash-generator" target="_blank">phplucidframe.sithukyaw.com/hash-generator</a>.';
		_cfg('sitewideWarnings', function_exists('_t') ? _t($msg) : $msg);
	}
}
/**
 * Sanitize input values from GET
 * @param  mixed $get The value or The array of values being sanitized.
 * @return mixed The cleaned value
 */
function _get($get){
	if(is_array($get)){
		foreach($get as $name=>$value){
			if(is_array($value)){
				$get[$name] = _get($value);
			}else{
				$value = _sanitize($value);
				$value = urldecode($value);
				$get[$name] = $value;
			}
		}
		return $get;
	}else{
		$value = strip_tags(trim($get));
		return urldecode($value);
	}
}
/**
 * Sanitize input values from POST
 * @param  mixed $post The value or The array of values being sanitized.
 * @return mixed the cleaned value
 */
function _post($post){
	if(is_array($post)){
		foreach($post as $name=>$value){
			if(is_array($value)){
				$post[$name] = _post($value);
			}else{
				$value = stripslashes($value);
				$value = _sanitize($value);
				$post[$name] = $value;
			}
		}
	}else{
		$value = stripslashes($post);
		$value = _sanitize($value);
		return $value;
	}
	return $post;
}
/**
 * Strips javascript tags in the value to prevent from XSS attack
 * @param mixed $value The value or The array of values being stripped.
 * @return mixed the cleaned value
 */
function _xss($value){
	if(is_object($value)) return $value;
	$pattern = '@<script[^>]*?>.*?</[^>]*?script[^>]*?>@si'; # strip out javacript
	if(is_array($value)){
		foreach($value as $key => $val){
			if(is_array($val)){
				$value[$key] = _xss($val);
			}else{
				$value[$key] = preg_replace( $pattern, '', trim(stripslashes($val)));
			}
		}
	}else{
		$value = preg_replace( $pattern, '', trim(stripslashes($value)));
	}
	return $value;
}
/**
 * Sanitize strings
 * @param  mixed $input Value to filter
 * @return mixed The filtered value
 */
function _sanitize($input){
	return htmlspecialchars(trim($input), ENT_NOQUOTES);
}
