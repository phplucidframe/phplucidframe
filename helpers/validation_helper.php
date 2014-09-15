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
 
$_validation_messages = array(
	'default'			=> _t("'%s' needs to be revised."),
	'mandatory' 		=> _t("'%s' is required."),
	'mandatoryOne'		=> _t("'%s' must be entered/selected at least one."),
	'mandatoryAll'		=> _t("'%s' is required. All must be entered/selected."),
	'notAllowZero' 		=> _t("'%s' should not be zero."),
	'alphaNumeric' 		=> _t("'%s' should contain only letters and numbers."),
	'alphaNumericSpace' => _t("'%s' should contain only letters, numbers and spaces."),
	'alphaNumericDash' 	=> _t("'%s' should contain only letters, numbers and dashes."),
	'numeric' 			=> _t("'%s' should be a number."),
	'numericSpace' 		=> _t("'%s' should contain only numbers and spaces."),
	'numericDash' 		=> _t("'%s' should contain only numbers and dashes. It should not start or end with a dash."),
	'username' 			=> _t("'%s' should contain only letters, numbers, periods, underscores and dashes."),
	'naturalNumber' 	=> _t("'%s' should be a positive integer. It is not allowed zero."),
	'wholeNumber' 		=> _t("'%s' should be a positive integer."),
	'integer' 			=> _t("'%s' should be a positive or negative integer."),
	'rationalNumber' 	=> _t("'%s' should be an integer or decimal."),
	'positiveRationalNumber' => _t("'%s' should be a positive integer or decimal."),
	'email'				=> _t("'%s' should be a valid format, e.g., username@example.com"),
	'domain'			=> _t("'%s' should be a valid domain name with letters, numbers and dash only."),
	'url'				=> _t("'%s' should be a valid website address, e.g., http://www.example.com"),
	'min'				=> _t("'%s' should be greater than or equal to %d."),
	'max'				=> _t("'%s' should be less than or equal to %d."),
	'minLength'			=> _t("'%s' should have at least %d letters."),
	'maxLength'			=> _t("'%s' should not exceed %d letters."),
	'between'			=> _t("'%s' should be between %d and %d."),
	'fileMaxSize'		=> _t("'%s' cannot exceed the maximum allowed upload size %dMB."),
	'fileMaxWidth'		=> _t("'%s' cannot exceed the maximum allowed width %dpx."),
	'fileMaxHeight'		=> _t("'%s' cannot exceed the maximum allowed height %dpx."),
	'fileMaxDimension'	=> _t("'%s' cannot exceed the maximum allowed dimension %dx%dpx."),
	'fileExactDimension'=> _t("'%s' should have the dimension %dx%dpx."),
	'fileExtension'		=> _t("'%s' must be one of the file types: %s."),
	'custom' 			=> _t("'%s' should be a valid format.")
);
/**
 * Checks that a string contains something other than whitespace
 * Returns true if string contains something other than whitespace
 * @param string|array $value Value to check
 * @return boolean Success
 */
function validate_mandatory($value){
	if(is_array($value) && empty($value['name'])) return false; # file upload
	if(is_array($value) && count($value) == 0) return false; 	# other grouped inputs
	if (empty($value) && $value != '0') {
		return false;
	}
	return (is_array($value)) ? true : preg_match('/[^\s]+/', $value);
}
/**
 * Check one of the fields is required
 * @param array $value The array of values to check
 * @return boolean
 */
function validate_mandatoryOne($value){
	if(is_array($value)){
		$value = array_unique($value);
		$empty = true;
		foreach($value as $v){
			if(preg_match('/[^\s]+/', $v)) $empty = false; # if one of the value is not empty
		}
		return !$empty;
	}else{
		return preg_match('/[^\s]+/', $value);
	}
}
/**
 * Check all of the fields are not empty
 * @param array $value The array of values to check
 * @return boolean
 */
function validate_mandatoryAll($value){
	if(is_array($value)){
		$value = array_unique($value);
		foreach($value as $v){
			if(preg_match('/[\s]+/', $v)) return false; # if one of the value is empty
		}
		return true;
	}else{
		return preg_match('/[^\s]+/', $value);
	}
}
/**
 * Check a string or number is zero or not
 * @param $value	(string) value to check
 * @return boolean 	true for non-zero, false for zero
 */
function validate_notAllowZero($value){
	$value = trim($value);
	return ($value == '0' || $value == 0) ? false : true;
}
/**
 * Checks that a string contains only integer or letters
 * @param $value	(mixed) Value to check
 * @return boolean 	true if string contains only integer or letters
 */
function validate_alphaNumeric($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9]+$/', $value);
}
/**
 * Checks that a string contains only integer, letters or spaces
 * @param $value	(mixed) Value to check
 * @return boolean 	true if string contains only integer, letters or spaces
 */
function validate_alphaNumericSpace($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9 ]+$/', $value);
}
/**
 * Checks that a string contains only integer, letters or dashes
 * @param $value	(mixed) Value to check
 * @return boolean 	true if string contains only integer, letters or dashes
 */
function validate_alphaNumericDash($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9\-]+$/', $value);
}
/**
 * Checks if a value is numeric.
 * @param 	$value (string) Value to check
 * @return 	boolean Success
 */
function validate_numeric($value) {
	return is_numeric($value);
}
/**
 * Checks if the value contains numbers and dashes
 * @param 	$value (string) Value to check
 * @return 	boolean Success
 */
function validate_numericDash($value){
	if(is_numeric($value) && strlen($value) == 1) return true;
	if(empty($value)) return true;
	return preg_match('/^([0-9])+([0-9\-])*([0-9])+$/', $value);
}
/**
 * Checks if the value contains numbers and spaces
 * @param 	$value (string) Value to check
 * @return 	boolean Success
 */
function validate_numericSpace($value){
	if(is_numeric($value) && strlen($value) == 1) return true;
	if(empty($value)) return true;
	return preg_match('/^[0-9 ]+$/', $value);
}
/**
 * Checks if the value does not contain special characters
 * @param 	$value (string) Value to check
 * @return 	boolean Success
 */
function validate_username($value){
	if(empty($value)) return true;
	return preg_match('/^([A-Za-z])+([A-Za-z0-9_\-\.])*([A-Za-z0-9])+$/', $value);
}
/**
 * Checks if a value is a positive integer starting from 1, 2, 3, and so on. No decimal
 * @param 	$value (string) Value to check
 * @return 	boolean TRUE on success or FALSE on failure
 * @see 	http://en.wikipedia.org/wiki/Natural_number
 *			http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_naturalNumber($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[1-9][0-9]*$/', $value);
}
/**
 * Checks if a value is a positive integer starting from 0, 1, 2, 3, and so on. No decimal.
 * @param 	$value (string) Value to check
 * @return 	boolean TRUE on success or FALSE on failure
 * @see 	http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_wholeNumber($value){
	$value = trim($value);
	if($value == '') return true;	
	return preg_match('/^(?:0|[1-9][0-9]*)$/', $value);
}
/**
 * Checks if a value is a positive or negative integer.
 * @param 	$value (string) Value to check
 * @return 	boolean TRUE on success or FALSE on failure
 * @see 	http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm 
 */
function validate_integer($value){
	$value = trim($value);
	if($value == '') return true;	
	return preg_match('/^[-]?(?:0|[1-9][0-9]*)$/', $value);
}
/**
 * Checks if a value is an integer AND decimal.
 * @param 	$value (string) Value to check
 * @return 	boolean TRUE on success or FALSE on failure
 * @see 	http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm 
 */
function validate_rationalNumber($value){
	$value = trim($value);
	if($value == '') return true;	
	return preg_match('/^[-]?[0-9]*[\.]?[0-9]+$/', $value);
}
/**
 * Checks if a value is a positive integer AND decimal
 * @param 	$value (string) Value to check
 * @return 	boolean TRUE on success or FALSE on failure
 * @see 	http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm 
 */
function validate_positiveRationalNumber($value){
	$value = trim($value);
	if($value == '') return true;	
	return preg_match('/^[0-9]*[\.]?[0-9]+$/', $value);
}
/**
 * Validates for an email address.
 * @param $value	(string) value to check
 * @return boolean Success
 */
function validate_email($value){
	$value = trim($value);
	if($value == '') return true;	
	return preg_match('/^[A-Za-z0-9]([A-Za-z0-9]|_|\.|\-)*@([a-z0-9]|\.|\-)+\.[a-z]{2,4}$/', $value);
}
/**
 * Checks if the value is a valid domain (alpha-numeric and dash only)
 * @param 	$value (string) Value to check
 * @return 	boolean Success
 */
function validate_domain($value){
	if(empty($value)) return true;
	return preg_match('/^([a-z])+([a-z0-9\-])*([a-z0-9])+$/i', $value);
}
/**
 * Validates for a valid absolute web address
 * @param $value	(string) value to check
 * @return boolean Success
 */
function validate_url($value){
	if(empty($value)) return true;
	
	# General regular expression for URL
	$regExp = '/^((http|https|ftp):\/\/)?([a-z0-9\-_]+\.){2,4}([[:alnum:]]){2,4}([[:alnum:]\/+=%&_\.~?\-]*)$/';
	
	# Get host name from URL
	preg_match("/^((http|https|ftp):\/\/)?([^\/]+)/i", $value, $matches);
	$host = $matches[3];	
	# Checking host name
	if(!strstr($host, "@")){
		if(preg_match($regExp, $value)){
		# Ok with general regular expression
			# Analyze host segment of URL
			$hostParts = explode(".", $host); 
			
			# Get suffix from host eg. com, net, org, sg or info, etc...
			$suffix = (strstr($hostParts[count($hostParts)-1], '?')) ? reset(explode('?', $hostParts[count($hostParts)-1])) : $hostParts[count($hostParts)-1];
			
			# IF last segment is valid && URL not contains 4w
			if(preg_match("/^[a-z]{2,4}$/", $suffix) && ! strstr($value, "wwww")) return true;
		}else{	
		# IF not OK with general regular expression
			# Regular Expression for URL
			$urlExp = "/^(([a-z0-9]|_|\-)+\.)+[a-z]{2,4}$/";
			
			# IF valid URL && URL not contains 4 w
			if(preg_match($urlExp, $value) && ! strstr($value, "wwww")) return true;
		} # End of Check if URL
	} # End of Check Host Name
	
	return false;
}
/**
 * Checks that a string length is greater than the specific length.
 * @param $value	(string) value to check for length
 * @param $min 		(int) minimum length in range (inclusive)
 * @return boolean Success
 */
function validate_minLength($value, $min) {
	$length = mb_strlen($value);
	return ($length >= $min);
}
/**
 * Checks that a string length is  less than the specific length.
 * @param $value	(string) value to check for length
 * @param $max		(int) maximum length in range (inclusive)
 * @return boolean Success
 */
function validate_maxLength($value, $max) {
	$length = mb_strlen($value);
	return ($length <= $max);
}
/**
 * Checks that a number is greater than the specific number.
 * @param $value	(int/float) value to check for length
 * @param $min 		(int/float) minimum value in range (inclusive)
 * @return boolean Success
 */
function validate_min($value, $min) {
	return ($value >= $min);
}
/**
 * Checks that a number is less than the specific number.
 * @param $value	(int/float) value to check for length
 * @param $max		(int/float) maximum value in range (inclusive)
 * @return boolean Success
 */
function validate_max($value, $max) {
	return ($value <= $max);
}
/**
 * Checks that a number is within a specified range.
 * Returns true is the number matches value min, max, or between min and max
 * @param $value	(string) value to check
 * @param $min 		(integer) minimum value in range (inclusive)
 * @param $max 		(integer) Maximum value in range (inclusive)
 * @return boolean Success
 */
function validate_between($value, $min, $max) {
	return ($value >= $min && $value <= $max);
}
/**
 * Used when a custom regular expression is needed.
 * @param $value	(mixed) value to check with the regular expression.
 * @param $regex 	(string) a valid regular expression
 * @return boolean Success
 */
function validate_custom($value, $regex){
	if (empty($value) && $value != '0') {
		return true;
	}
	return preg_match($regex, $value);
}
/**
 * Validation of image file upload for allowed file extensions
 * @param $value		(array) $_FILES array
 * @param $extensions	(array) Array of file extensions such as array('jpg', 'jpeg', 'png', 'gif')
 * @return boolean 		true on success; false on failure
 */
function validate_fileExtension($value, $extensions = array('jpg', 'jpeg', 'png', 'gif')){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	if(empty($value['name'])) return true;
	$ext = explode('.', $value['name']);
	$ext = strtolower(end($ext));
	return (in_array($ext, $extensions)) ? true : false;
}
/**
 * Validation of maximum file upload size
 * @param $value		(array) $_FILES array
 * @param $maxSize		(array) max file size in MB
 * @return boolean 		true on success; false on failure
 */
function validate_fileMaxSize($value, $maxSize = NULL) {
	if(!is_array($value)) return true;
	if(is_null($maxSize)) return true;
	$fileSize 	= $value['size'];
	$maxSize 	= $maxSize * 1024 * 1024; # in bytes
	return ($fileSize <= $maxSize);
}
/**
 * Validation of image file upload for max width and max height
 * @param $value		(array) $_FILES array
 * @param $maxWidth		(int) Maximum image width in pixels
 * @param $maxHeight	(int) Maximum image height in pixels 
 * @return boolean 		true on success; false on failure
 */
function validate_fileMaxDimension($value, $maxWidth, $maxHeight){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	if($width <= $maxWidth && $height <= $maxHeight) return true;
	return false;
}
/**
 * Validation of image file upload for exact width and height
 * @param $value		(array) $_FILES array
 * @param $maxWidth		(int) Image width in pixels
 * @param $maxHeight	(int) Image height in pixels 
 * @return boolean 		true on success; false on failure
 */
function validate_fileExactDimension($value, $maxWidth, $maxHeight){ 
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	if($width == $maxWidth && $height == $maxHeight) return true;
	return false;
}
/**
 * Validation of image file upload for max width only
 * @param $value		(array) $_FILES array
 * @param $maxWidth		(int) Maximum image width in pixels
 * @return boolean 		true on success; false on failure
 */
function validate_fileMaxWidth($value, $maxWidth){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	return ($width <= $maxWidth);
}
/**
 * Validation of image file upload for max height only
 * @param $value		(array) $_FILES array
 * @param $maxHeight	(int) Maximum image height in pixels
 * @return boolean 		true on success; false on failure
 */
function validate_fileMaxHeight($value, $maxHeight){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	return ($height <= $maxHeight);
}
/**
 * Validation of an IP address.
 * @param $value	(string) The string to test.
 * @param $type 	(string) The IP Protocol version to validate against; ipv4 or ipv6
 * @return boolean 	TRUE on success; FALSE on failure
 */
function validate_ip($value, $type = 'both') {
	$type = strtolower($value);
	$flags = 0;
	if ($type === 'v4' || $type === 'ipv4') {
		$flags = FILTER_FLAG_IPV4;
	}
	if ($type === 'v6' || $type === 'ipv6') {
		$flags = FILTER_FLAG_IPV6;
	}
	return (boolean)filter_var($value, FILTER_VALIDATE_IP, array('flags' => $flags));
}

class Validation{

	public 	static $messages;
	public 	static $errors = array();
	public 	static $batchRules = array('mandatoryOne', 'mandatoryAll');
	
	public static function check($validations, $type='multi'){
		Form::init();
				
		$type = strtolower($type);
		if( !in_array($type, array('single', 'multi')) ) $type = 'multi';
		self::$errors = array();
		foreach($validations as $id => $v){
			if(	is_array($v['rules']) ){
				foreach($v['rules'] as $rule){
					$success = true;
					$caption = ( !isset($v['caption']) ) ? $id : $v['caption'];	
					
					if(is_array($v['value']) && in_array($rule, self::$batchRules)){					
						# Batch validation rules may be applied for array of values						
						$values = $v['value'];
						$func = 'validate_'.$rule;						
						if( function_exists($func) ){
							$success = call_user_func_array($func, array($values));
							if(!$success) self::setError($id, $rule, $v);
							continue; # go to the next rule
						}
						# if array of values, the validation function (apart from the batch validation functions) will be applied to each value						
					}else{
						if(!is_array($v['value']) || (is_array($v['value']) && array_key_exists('tmp_name',$v['value']))){ 
							$values = array($v['value']);
						}else{
							$values = $v['value'];
						}
						
					}

					foreach($values as $value){	
						# Custom validation function
						if(strstr($rule, 'validate_')){
							$args = array($value);
							if(isset($v['parameters']) && is_array($v['parameters'])){
								if(isset($v['parameters'][$rule])) $params = $v['parameters'][$rule];
								else $params = $v['parameters'];
								$args = array_merge($args, $params);
							}
							$success = call_user_func_array($rule, $args);
							if(!$success) self::setError($id, $rule, $v);
						}else{
						# Pre-defined validation functions
							$func = 'validate_'.$rule;							
							if(function_exists($func)){
								switch($rule){
									case 'min': 
										# Required property: min
										if(!isset($v['min'])) continue;
										$success = call_user_func_array($func, array($value, $v['min']));
										if(!$success) self::setError($id, $rule, $v, $v['min']);
										break;
																											
									case 'max': 
										# Required property: max
										if(!isset($v['max'])) continue;
										$success = call_user_func_array($func, array($value, $v['max']));
										if(!$success) self::setError($id, $rule, $v, $v['max']);
										break;
										
									case 'minLength': 
										# Required property: min
										if(!isset($v['min'])) continue;
										$success = call_user_func_array($func, array($value, $v['min']));
										if(!$success) self::setError($id, $rule, $v, $v['min']);
										break;
																											
									case 'maxLength': 
										# Required property: max
										if(!isset($v['max'])) continue;
										$success = call_user_func_array($func, array($value, $v['max']));
										if(!$success) self::setError($id, $rule, $v, $v['max']);
										break;									
																										
									case 'between': 
										# Required property: min|max									
										if(!isset($v['min']) || !isset($v['max'])) continue;									
										$success = call_user_func_array($func, array($value, $v['min'], $v['max']));
										if(!$success) self::setError($id, $rule, $v, $v['min'], $v['max']);
										break;
										
									case 'ip': 
										# Required property: protocol
										$v['protocol'] = (!isset($v['protocol']) || ( isset($v['protocol']) && !in_array($v['protocol'], array('ipv4','ipv6')) )) ? 'ipv4' : $v['protocol'];
										$success = call_user_func_array($func, array($value, $v['protocol']));
										if(!$success) self::setError($id, $rule, $v);
										break;
										
									case 'custom': 
										# Required property: pattern
										if(!isset($v['pattern'])) continue;
										$success = call_user_func_array($func, array($value, $v['pattern']));
										if(!$success) self::setError($id, $rule, $v);
										break;
										
									case 'fileMaxSize':
										# Required property: maxSize
										if(!isset($v['maxSize'])) continue;
										$success = call_user_func_array($func, array($value, $v['maxSize']));
										if(!$success) self::setError($id, $rule, $v, $v['maxSize']);
										break;
										
									case 'fileMaxWidth':
										# Required property: maxWidth
										if(!isset($v['maxWidth'])) continue;
										$success = call_user_func_array($func, array($value, $v['maxWidth']));
										if(!$success) self::setError($id, $rule, $v, $v['maxWidth']);
										break;
										
									case 'fileMaxHeight':
										# Required property: maxHeight
										if(!isset($v['maxHeight'])) continue;
										$success = call_user_func_array($func, array($value, $v['maxHeight']));
										if(!$success) self::setError($id, $rule, $v, $v['maxHeight']);
										break;
										
									case 'fileMaxDimension':
										# Required property: maxWidth, maxHeight
										if(!isset($v['maxWidth']) || !isset($v['maxHeight'])) continue;
										$success = call_user_func_array($func, array($value, $v['maxWidth'], $v['maxHeight']));
										if(!$success) self::setError($id, $rule, $v, $v['maxWidth'], $v['maxHeight']);
										break;
										
									case 'fileExactDimension':
										# Required property: width, height
										if(!isset($v['width']) || !isset($v['height'])) continue;
										$success = call_user_func_array($func, array($value, $v['width'], $v['height']));
										if(!$success) self::setError($id, $rule, $v, $v['width'], $v['height']);
										break;										
										
									case 'fileExtension':
										# Required property: extensions
										if(!isset($v['extensions'])) continue;
										$success = call_user_func_array($func, array($value, $v['extensions']));
										if(!$success) self::setError($id, $rule, $v, '"'.implode(', ', $v['extensions']).'"');
										break;																																														
										
									default:
										$success = call_user_func_array($func, array($value));
										if(!$success) self::setError($id, $rule, $v);
								}
								if(!$success){
									if($type == 'single') break 3;
									continue 3;								
								}
							} # if(function_exists($func))
						} # if(strstr($rule, 'validate_'))
					} # foreach($values as $value)
				} # foreach($v['rules'] as $rule)
			} # if(	is_array($v['rules']) )
		} # foreach($validations as $id => $v)
		return (count(self::$errors)) ? false : true;
	}
	
	private static function setError($id, $rule, $element){
		$caption = $element['caption'];
		$msg 	 = ( isset(self::$messages[$rule]) ) ? self::$messages[$rule] : self::$messages['default'];
		$msg 	 = ( isset($element['messages'][$rule]) ) ? $element['messages'][$rule] : $msg;
		$args = func_get_args();
		if(count($args) > 3){
			$args = array_slice($args, 3);
			$cmd  = 'self::$errors[] = array("msg" => sprintf( $msg, $caption';
			$cmd .= ', '.implode(', ', $args);
			$cmd .= ' ), "htmlID" => $id);';
			//echo $cmd;
			eval($cmd);
		}else{ 
			self::$errors[] = array(
				'msg' => sprintf( $msg, $caption ), 
				'htmlID' => $id
			);
		}
	}
	/**
	 * Add an external error messsage
	 * @param $id	(string) HTML ID
	 * @param $msg	(string) Error message to show
	 */
	public static function addError($id, $msg){
		self::$errors[] = array(
			'msg' => sprintf( $msg ), 
			'htmlID' => $id
		);		
	}
}

Validation::$messages = $_validation_messages;