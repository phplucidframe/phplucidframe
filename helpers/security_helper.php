<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for input sanitizing, data escaping, CSRF protection and XSS prevention
 *
 * @package		LC\Helpers\Security
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
 * @internal
 * Check the default security salt to be changed
 */
function security_prerequisite(){
	$defaultSalt = md5('lucidframe');
	$salt = file_get_contents(INC . 'security.salt');
	if(strcmp($salt, $defaultSalt) === 0){
		_cfg('sitewideWarnings', _t('Change your own security salt hash in the file "/inc/security.salt".'));
	}
}
/**
 * Strips HTML tags in the value to prevent from XSS attack. It should be called for GET values.
 * @param  mixed $get The value or The array of values being stripped.
 * @return mixed The cleaned value
 */
function _get($get){
	if(is_array($get)){
		foreach($get as $name=>$value){
			if(is_array($value)){
				$get[$name] = _get($value);
			}else{
				$value = strip_tags(trim($value));
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
 * Strips HTML tags in the value to prevent from XSS attack. It should be called for POST values.
 * @param  mixed $post The value or The array of values being stripped.
 * @return mixed the cleaned value
 */
function _post($post){
	if(is_array($post)){
		foreach($post as $name=>$value){
			if(is_array($value)){
				$post[$name] = _post($value);
			}else{
				$value = stripslashes($value);
				$value = htmlspecialchars($value, ENT_QUOTES);
				$value = strip_tags(trim($value));
				$post[$name] = $value;
			}
		}
	}else{
		$value = stripslashes($post);
		$value = htmlspecialchars($value, ENT_QUOTES);
		$value = strip_tags(trim($post));
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