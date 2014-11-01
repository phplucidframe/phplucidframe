<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for AJAX form handling and form validation
 *
 * @package		LC\Helpers\Form
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
 * This class is part of the PHPLucidFrame library.
 * Helper for AJAX form handling and form validation
 */
class Form{
	/** @var string The HTML form ID */
	private static $id;
	/** @var array The error messages and their associated HTML ID */
	private static $error 	= array();
	/** @var boolean TRUE/FALSE for form valiation success */
	private static $success = false;
	/** @var string The form message */
	private static $message = '';
	/** @var string URL to be redirect upon form submission completed */
	private static $redirect = '';
	/** @var string The Javascript callback function to be invoked upon form submission completed */
	private static $callback = '';
	/**
	 * Constructor
	 */
	public static function init(){
		self::$id 		= '';
		self::$error 	= array();
		self::$success 	= false;
		self::$message 	= '';
		self::$redirect = '';
		self::$callback = '';
	}
	/**
	 * Setter for the class properties
	 * @param string $key The property name
	 * @param mixed $value The value to be set
	 * @return void
	 */
	public static function set($key, $value=''){
		self::$$key = $value;
	}
	/**
	 * Form token generation
	 * @return void
	 */
	public static function token(){
		$token = _encrypt(time());
		session_set(_cfg('formTokenName'), $token);
		echo '<input type="hidden" name="lc_formToken_'._cfg('formTokenName').'" value="'.$token.'" />';
	}
	/**
	 * Form token validation
	 * @return void
	 */
	public static function validate(){
		if(!isset($_POST['lc_formToken_'._cfg('formTokenName')])) return false;
		$token 			= _decrypt(session_get(_cfg('formTokenName')));
		$postedToken 	= _decrypt(_post($_POST['lc_formToken_'._cfg('formTokenName')]));
		$result 		= false;
		# check token first
		if($token == $postedToken){
			# check referer if it is requesting in the same site
			if($_SERVER['HTTP_REFERER'] && _cfg('siteDomain')){
				$parsedURL = parse_url($_SERVER['HTTP_REFERER']);
				if( strcasecmp(_cfg('siteDomain'), $parsedURL['host']) == 0 ){
					$result = true;
				}
			}
		}
		if($result == false){
			Validation::addError('', _t('Error occured during form submission. Please refresh the page to try again.'));
			return false;
		}
		return true;
	}
	/**
	 * AJAX form responder
	 * @param string $formId The HTML form ID
	 * @param array $errors The array of the errors (it is used only for generic form processing)
	 * @return void
	 */
	public static function respond($formId, $errors=NULL){
		self::$id = $formId;
		$errorStr = '';
		$ajaxResponse = true;
		if(is_array($errors)){
			self::$error = $errors;
			$ajaxResponse = false;
			# if no error message and no other message, no need to respond
			if(count(self::$error) == 0 && empty(self::$message)) return;
		}

		if(sizeof(self::$error)){
			$errorStr = json_encode(self::$error);
		}else{
			$errorStr = "''";
		}

		if( $ajaxResponse ){
		?>
			var response = {
				'formId' 	: '<?php echo self::$id; ?>',
				'success' 	: <?php echo (self::$success) ? 1 : 0; ?>,
				'error' 	: <?php echo $errorStr; ?>,
				'msg' 		: "<?php echo addslashes(self::$message); ?>",
				'redirect' 	: '<?php echo self::$redirect; ?>',
				'callback' 	: '<?php echo self::$callback; ?>'
			};
		<?php
		}else{
		?>
		<script type="text/javascript">
			LC.Form.submitHandler({
				'formId' 	: '<?php echo self::$id; ?>',
				'success' 	: <?php echo (self::$success) ? 1 : 0; ?>,
				'error' 	: <?php echo $errorStr; ?>,
				'msg' 		: "<?php echo addslashes(self::$message); ?>",
				'redirect' 	: '<?php echo self::$redirect; ?>',
				'callback' 	: '<?php echo self::$callback; ?>'
			});
		</script>
		<?php
		}
	}
	/**
	 * Permits you to set the value of an input or textarea.
	 * Allows you to safely use HTML and characters such as quotes within form elements without breaking out of the form
	 * 
	 * @param string $name The input element field name
	 * @param mixed $defaultValue The default value of the input element (optional)
	 * 
	 * @return mixed The value of the input element
	 */
	public static function value($name, $defaultValue=NULL){
		if(count($_POST)){
			if(!isset($_POST[$name])) return '';
			$value = _post($_POST[$name]);
			return _h($value);
		}else{
			return _h($defaultValue);
		}
	}
}