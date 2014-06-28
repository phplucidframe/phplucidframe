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
 
class Form{
	
	private static $id;
	private static $error 	= array();
	private static $success = false;
	private static $message = '';
	private static $redirect = '';
	private static $callback = '';
	
	public static function init(){
		self::$id 		= '';
		self::$error 	= array();
		self::$success 	= false;
		self::$message 	= '';
		self::$redirect = '';
		self::$callback = '';
	}
	
	public static function set($key, $value=''){
		self::$$key = $value;
	}
	
	/* Form token generation */
	public static function token(){
		$token = _encrypt(time());
		setSession('formToken', $token);
		echo '<input type="hidden" name="lc_formToken" value="'.$token.'" />';
	}
	
	/* Form token validation */
	public static function validate(){
		if(!isset($_POST['lc_formToken'])) return false;
		$token 			= _decrypt(getSession('formToken'));		
		$postedToken 	= _decrypt(_post($_POST['lc_formToken']));
		if($token == $postedToken){
			return true;
		}else{
			Validation::addError('', _t('Error occured during form submission. Please refresh the page to try again.'));
			return false;			
		}				
	}
	
	/* Respond AJAX Form */
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
        	Form.submitHandler({
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
}