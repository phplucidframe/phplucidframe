<?php
/**
 * The action.php (optional) handles form submission.
 * It should perform form validation, create, update, delete of data manipulation to database.
 * By default, a form is initiated for AJAX and action.php is automatically invoked if the action attribute is not given in the <form> tag.
 */
$success = false;

if(sizeof($_POST)){
	$post = _post($_POST);
	extract($post);

	/**
	Form validation prerequites here, for example
	***
	$validations = array(
		'txtTitle' => array(
			'caption' 	=> _t('Title'),
			'value' 	=> $txtTitle,
			'rules' 	=> array('mandatory'),
		),
		'txtBody' => array(
			'caption' 	=> _t('Body'),
			'value' 	=> $txtBody,
			'rules' 	=> array('mandatory'),
		),
	);
	*/

	$validations = array(
	);

	if(Validation::check($validations) == true){
		/**
		Database operations here
		*/

		if($success){
			Form::set('success', true);
			Form::set('redirect', _url('home'));
		}
	}else{
		Form::set('error', Validation::$errors);
	}
}
Form::respond('formID'); # Ajax response