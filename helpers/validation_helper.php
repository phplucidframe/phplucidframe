<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for input validation
 *
 * @package		LC\Helpers\Validation
 * @since		PHPLucidFrame v 1.0.0
 * @copyright	Copyright (c), PHPLucidFrame.
 * @author 		Sithu K. <sithukyaw.com>
 * @link 		http://phplucidframe.sithukyaw.com
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

$lc_validationMessages = array(
	'default'				=> "'%s' needs to be revised.",
	'mandatory'				=> "'%s' is required.",
	'mandatoryOne'			=> "'%s' must be entered/selected at least one.",
	'mandatoryAll'			=> "'%s' is required. All must be entered/selected.",
	'notAllowZero'			=> "'%s' should not be zero.",
	'alphaNumeric'			=> "'%s' should contain only letters and numbers.",
	'alphaNumericSpace'		=> "'%s' should contain only letters, numbers and spaces.",
	'alphaNumericDash'		=> "'%s' should contain only letters, numbers and dashes.",
	'numeric'				=> "'%s' should be a number.",
	'numericSpace'			=> "'%s' should contain only numbers and spaces.",
	'numericDash'			=> "'%s' should contain only numbers and dashes. It should not start or end with a dash.",
	'username'				=> "'%s' should contain only letters, numbers, periods, underscores and dashes.",
	'naturalNumber'			=> "'%s' should be a positive integer. It is not allowed zero.",
	'wholeNumber'			=> "'%s' should be a positive integer.",
	'integer'				=> "'%s' should be a positive or negative integer.",
	'rationalNumber'		=> "'%s' should be an integer or decimal.",
	'positiveRationalNumber'=> "'%s' should be a positive integer or decimal.",
	'email'					=> "'%s' should be a valid format, e.g., username@example.com",
	'domain'				=> "'%s' should be a valid domain name with letters, numbers and dash only.",
	'url'					=> "'%s' should be a valid website address, e.g., http://www.example.com",
	'min'					=> "'%s' should be greater than or equal to %d.",
	'max'					=> "'%s' should be less than or equal to %d.",
	'minLength'				=> "'%s' should have at least %d letters.",
	'maxLength'				=> "'%s' should not exceed %d letters.",
	'between'				=> "'%s' should be between %d and %d.",
	'fileMaxSize'			=> "'%s' cannot exceed the maximum allowed upload size %dMB.",
	'fileMaxWidth'			=> "'%s' cannot exceed the maximum allowed width %dpx.",
	'fileMaxHeight'			=> "'%s' cannot exceed the maximum allowed height %dpx.",
	'fileMaxDimension'		=> "'%s' cannot exceed the maximum allowed dimension %dx%dpx.",
	'fileExactDimension'	=> "'%s' should have the dimension %dx%dpx.",
	'fileExtension'			=> "'%s' must be one of the file types: %s.",
	'date'					=> "'%s' should be a real date or valid for the date format '%s'.",
	'time'					=> "'%s' should be a real time or valid for 12-hr/24-hr format.",
	'custom'				=> "'%s' should be a valid format."
);
/**
 * @internal
 * Initialize the validation messages
 */
function __validation_init(){
	global $lc_validationMessages;
	$i18nEnabled = function_exists('_t');
	foreach($lc_validationMessages as $key => $msg){
		$lc_validationMessages[$key] = ($i18nEnabled) ? _t($msg) : $msg;
	}
	Validation::set('messages', $lc_validationMessages);
}
/**
 * Checks that a string contains something other than whitespace
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value contains something other than whitespace, FALSE otherwise
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
 * @return boolean TRUE if one of the fields are not empty, FALSE otherwise
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
 * @param array $value The array of values being checked
 * @return boolean TRUE if all of the fields are not empty, FALSE otherwise
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
 * @param string $value The value being checked
 * @return boolean TRUE for non-zero, FALSE otherwise
 */
function validate_notAllowZero($value){
	$value = trim($value);
	return ($value == '0' || $value == 0) ? false : true;
}
/**
 * Checks that a string contains only integer or letters
 * @param mixed $value The value being checked
 * @return boolean 	TRUE if the value contains only integer or letters, FALSE otherwise
 */
function validate_alphaNumeric($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9]+$/', $value);
}
/**
 * Checks that a string contains only integer, letters or spaces
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value contains only integer, letters or spaces, FALSE otherwise
 */
function validate_alphaNumericSpace($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9 ]+$/', $value);
}
/**
 * Checks that a string contains only integer, letters or dashes
 * @param mixed $value The value being checked
 * @return boolean 	TRUE if the value contains only integer, letters or dashes, FALSE otherwise
 */
function validate_alphaNumericDash($value){
	if(empty($value)) return true;
	return preg_match('/^[A-Za-z0-9\-]+$/', $value);
}
/**
 * Checks if a value is numeric.
 * @param mixed $value The value being checked
 * @return boolean TRUE if var is a number or a numeric string, FALSE otherwise.
 */
function validate_numeric($value) {
	return is_numeric($value);
}
/**
 * Checks if the value contains numbers and dashes
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value contains numbers and dashes only, FALSE otherwise
 */
function validate_numericDash($value){
	if(is_numeric($value) && strlen($value) == 1) return true;
	if(empty($value)) return true;
	return preg_match('/^([0-9])+([0-9\-])*([0-9])+$/', $value);
}
/**
 * Checks if the value contains numbers and spaces
 * @param string $value The value being checked
 * @return boolean TRUE if the value contains numbers and spaces only, FALSE otherwise
 */
function validate_numericSpace($value){
	if(is_numeric($value) && strlen($value) == 1) return true;
	if(empty($value)) return true;
	return preg_match('/^[0-9 ]+$/', $value);
}
/**
 * Checks if the value does not contain special characters
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value does not contain special characters, FALSE otherwise
 */
function validate_username($value){
	if(empty($value)) return true;
	return preg_match('/^([A-Za-z])+([A-Za-z0-9_\-\.])*([A-Za-z0-9])+$/', $value);
}
/**
 * Checks if a value is a positive integer starting from 1, 2, 3, and so on. No decimal
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is natural number, FALSE otherwise
 * @see http://en.wikipedia.org/wiki/Natural_number
 *   http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_naturalNumber($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[1-9][0-9]*$/', $value);
}
/**
 * Checks if a value is a positive integer starting from 0, 1, 2, 3, and so on. No decimal.
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is whole number, FALSE otherwise
 * @see http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_wholeNumber($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^(?:0|[1-9][0-9]*)$/', $value);
}
/**
 * Checks if a value is a positive or negative integer.
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is integer, FALSE otherwise
 * @see http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_integer($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[-]?(?:0|[1-9][0-9]*)$/', $value);
}
/**
 * Checks if a value is an integer AND decimal.
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is rational number, FALSE otherwise
 * @see http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_rationalNumber($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[-]?[0-9]*[\.]?[0-9]+$/', $value);
}
/**
 * Checks if a value is a positive integer AND decimal
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is positive rational number, FALSE otherwise
 * @see http://math.about.com/od/mathhelpandtutorials/a/Understanding-Classification-Of-Numbers.htm
 */
function validate_positiveRationalNumber($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[0-9]*[\.]?[0-9]+$/', $value);
}
/**
 * Validates for an email address.
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is a valid email address, FALSE otherwise
 */
function validate_email($value){
	$value = trim($value);
	if($value == '') return true;
	return preg_match('/^[A-Za-z0-9]([A-Za-z0-9]|_|\.|\-)*@([a-z0-9]|\.|\-)+\.[a-z]{2,4}$/', $value);
}
/**
 * Checks if the value is a valid domain (alpha-numeric and dash only)
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value has letters, numbers and dashes only, FALSE otherwise
 */
function validate_domain($value){
	if(empty($value)) return true;
	return preg_match('/^([a-z])+([a-z0-9\-])*([a-z0-9])+$/i', $value);
}
/**
 * Validates for a valid absolute web address
 * @param mixed $value The value being checked
 * @return boolean TRUE if the value is a valid absolute web address, FALSE otherwise
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
 * @param mixed $value The value being checked
 * @param int $min The minimum length to meet (inclusive)
 * @return boolean if the character length of the value meets the specified minimum length, FALSE otherwise
 */
function validate_minLength($value, $min) {
	$length = mb_strlen($value);
	return ($length >= $min);
}
/**
 * Checks that a string length is  less than the specific length.
 * @param mixed $value The value being checked
 * @param int $max The maximum length to meet (inclusive)
 * @return boolean if the character length of the value meets the specified maximum length, FALSE otherwise
 */
function validate_maxLength($value, $max) {
	$length = mb_strlen($value);
	return ($length <= $max);
}
/**
 * Checks that a number is greater than the specific number.
 * @param int/float $value The value being checked
 * @param int/float $min The minimum value to meet (inclusive)
 * @return boolean if the value is equal to or greater than the specific minimum number, FALSE otherwise
 */
function validate_min($value, $min) {
	return ($value >= $min);
}
/**
 * Checks that a number is less than the specific number.
 * @param int/float $value The value being checked
 * @param int/float $max The maximum value to meet (inclusive)
 * @return boolean if the value is equal to or less than the specific maximum number, FALSE otherwise
 */
function validate_max($value, $max) {
	return ($value <= $max);
}
/**
 * Checks that a number is within a specified range.
 * @param int/float $value The value being checked
 * @param int/float $min The minimum value in range (inclusive)
 * @param int/float $max The maximum value in range (inclusive)
 * @return boolean TRUE if the number is within the specified range, FALSE otherwise
 */
function validate_between($value, $min, $max) {
	return ($value >= $min && $value <= $max);
}
/**
 * Used when a custom regular expression is needed.
 * Searches the value for a match to the regular expression given in pattern.
 * @param  mixed $value The value being checked
 * @param  string $pattern The pattern to search for, as a string
 * @return mixed `1` if the pattern matches given value, `0` if it does not, or `FALSE` if an error occurred.
 * @see http://php.net/manual/en/function.preg-match.php
 */
function validate_custom($value, $pattern){
	if (empty($value) && $value != '0') {
		return true;
	}
	return preg_match($pattern, $value);
}
/**
 * Validation of image file upload for allowed file extensions
 * @param array $value The $_FILES array
 * @param array $extensions The Array of file extensions such as `array('jpg', 'jpeg', 'png', 'gif')`
 * @return boolean TRUE if the uploaded file extension is allowed according to the given extensions, FALSE otherwise
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
 * @param array $value The $_FILES array
 * @param int $maxSize The maximum file size in MB
 * @return boolean TRUE if the uploaded file does not exceed the given file size, FALSE otherwise
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
 * @param array $value The $_FILES array
 * @param int $maxWidth	The maximum image width in pixels
 * @param int $maxHeight The maximum image height in pixels
 * @return boolean
 *  TRUE if the image uploaded dimension does not exceed the given max width and height;
 *  FALSE otherwise
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
 * @param array $value The $_FILES array
 * @param int $width The image width in pixels
 * @param int $height The mage height in pixels
 * @return boolean
 *  TRUE if the image uploaded dimension same as the given max width and height;
 *  FALSE otherwise
 */
function validate_fileExactDimension($value, $width, $height){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($w, $h) = getimagesize($value['tmp_name']);
	if($w == $width && $h == $height) return true;
	return false;
}
/**
 * Validation of image file upload for max width only
 * @param array $value The $_FILES array
 * @param int $maxWidth	The maximum image width in pixels
 * @return boolean 
 *  TRUE if the uploaded image does not exceed the maximum width allowed;
 *  FALSE otherwise
 */
function validate_fileMaxWidth($value, $maxWidth){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	return ($width <= $maxWidth);
}
/**
 * Validation of image file upload for max height only
 * @param array $value The $_FILES array
 * @param int $maxHeight The maximum image height in pixels
 * @return boolean
 *  TRUE if the uploaded image does not exceed the maximum height allowed;
 *  FALSE otherwise
 */
function validate_fileMaxHeight($value, $maxHeight){
	if(!is_array($value)) return true;
	if(!file_exists($value['tmp_name'])) return true;
	list($width, $height) = getimagesize($value['tmp_name']);
	return ($height <= $maxHeight);
}
/**
 * Validation of an IP address.
 * @param string $value	The value being checked
 * @param string $type The IP protocol version to validate against IPv4 or IPv6
 * @return boolean TRUE on success; FALSE on failure
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
/**
 * Validation of a date which checks if the string passed is a valid date.
 * **Allowed formats**
 *
 * - `d-m-y` 31-12-2014 separators can be a period, dash, forward slash, but not allow space
 * - `m-d-y` 12-31-2014 separators can be a period, dash, forward slash, but not allow space
 * - `y-m-d` 2014-12-31 separators can be a period, dash, forward slash, but not allow space
 * 
 * @param string $value The date string being checked
 * @param string $format The date format to be validated against. Default is y-m-d for 2014-12-31
 * 
 * @return bool TRUE on success; FALSE on failure
 */
function validate_date($value, $format = 'y-m-d'){
	if(empty($value)) return true;
	$value = trim($value);
	$format = strtolower($format);
	$separators = array('/', '-', '.');
	$sepGroup = '([-\/.])';
	$cleanFormat = preg_replace('/'.$sepGroup.'/', '', $format); // remove the separators from the format
	$pattern = '';
	
	if(in_array($cleanFormat, array('dmy', 'mdy'))){
		$pattern = '/^([\d]{1,2})'.$sepGroup.'([\d]{1,2})'.$sepGroup.'([\d]{4})$/'; // dmy or mdy
	}
	else{
		$pattern = '/^([\d]{4})'.$sepGroup.'([\d]{1,2})'.$sepGroup.'([\d]{1,2})$/'; // ymd
	}
	if($pattern && preg_match_all($pattern, $value, $matches)){
		if($matches[2][0] != $matches[4][0]) return false; // inconsisitent separators
		elseif(!in_array($matches[2][0], $separators)) return false; // invalid separator
		$sep	= $matches[2][0]; // the separator using
		$dt 	= explode($sep, $value);
		$format = str_split($cleanFormat);
		$ft 	= array_flip($format);
		$y = $dt[$ft['y']];
		$m = $dt[$ft['m']];
		$d = $dt[$ft['d']];
		return checkdate($m, $d, $y);
	}
	return false;
}
/**
 * Validation of a time which checks if the string passed is a valid time in 24-hr or 12-hr format
 * **Allowed inputs**
 * 
 * - 23:59 or 01:00 or 1:00
 * - 23:59:59 or 01:00:00 or 1:00:00
 * - 11:59am or 01:00pm or 1:00pm
 * - 11:59 am or 01:00 pm or 1:00 PM or 1:00PM
 * - 11:59:59am 01:00:00pm or 1:00:00pm
 * - 11:59:59 AM 01:00:00 PM or 1:00:00PM
 * 
 * @param string $value The time string being checked
 * 
 * @return bool TRUE on success; FALSE on failure
 */
function validate_time($value){
	if(empty($value)) return true;
	$value = trim($value);
	$regex = array(
		'24' => '/^([0-23]{2})(:)([0-59]{2})(:[0-59]{2})?$/', // 24-hr format
		'12' => '/^(0?[0-11]{0,2})(:)([0-59]{2})(:[0-59]{2})?\s*(am|pm)$/i' // 12-hr format
	);
	foreach($regex as $pattern){
		if(preg_match($pattern, $value)) return true;
	}
	return false;
}

/**
 * This class is part of the PHPLucidFrame library.
 * Form validation helper
 */
class Validation{

	/** @var array The array of the error messages upon validation */
	public 	static $errors = array();
	/** @var array The array of default error messages */
	private	static $messages;
	/** @var array The array of the rules for group of inputs validation */
	private	static $batchRules = array('mandatoryOne', 'mandatoryAll');

	/**
	 * @internal
	 */
	public static function set($key, $value=''){
		self::$$key = $value;
	}
	/**
	 * Check all inputs according to the validation rules provided
	 *
	 * @param array $validations The array of the validation rules
	 * @param string $type The return form of the error message:
	 *  "multi" to return all error messages occurred;
	 *  "single" to return the first error message occurred
	 *
	 * @return void
	 */
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
										if(!$success) self::setError($id, $rule, $v, implode(', ', $v['extensions']));
										break;

									case 'date':
										# Optional property: dateFormat
										if(!isset($v['dateFormat']) || (isset($v['dateFormat']) && empty($v['dateFormat']))) $v['dateFormat'] = 'y-m-d';
										$success = call_user_func_array($func, array($value, $v['dateFormat']));
										if(!$success) self::setError($id, $rule, $v, $v['dateFormat']);
										break;

									case 'time':
										$success = call_user_func_array($func, array($value));
										if(!$success) self::setError($id, $rule, $v);
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
	/**
	 * @internal
	 */
	private static function setError($id, $rule, $element){
		$caption = $element['caption'];
		$msg 	 = ( isset(self::$messages[$rule]) ) ? self::$messages[$rule] : self::$messages['default'];
		$msg 	 = ( isset($element['messages'][$rule]) ) ? $element['messages'][$rule] : $msg;
		$args = func_get_args();
		if(count($args) > 3){
			$args = array_slice($args, 3);
			array_unshift($args, $caption);
			self::$errors[] = array(
				"msg" => vsprintf($msg, $args),
				"htmlID" => $id
			);
		}else{
			self::$errors[] = array(
				'msg' => sprintf( $msg, $caption ),
				'htmlID' => $id
			);
		}
	}
	/**
	 * Add an external error messsage
	 *
	 * @param string $id HTML ID
	 * @param string $msg The error message to show
	 *
	 * @return void
	 */
	public static function addError($id, $msg){
		self::$errors[] = array(
			'msg' => sprintf( $msg ),
			'htmlID' => $id
		);
	}
}

__validation_init();