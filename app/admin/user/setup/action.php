<?php
$success = false;
if(sizeof($_POST)){
	$post = _post($_POST);
	extract($post);

	$validations = array(
		'txtFullName' => array(
			'caption' 	=> _t('Full Name'),
			'value' 	=> $txtFullName,
			'rules' 	=> array('mandatory'),
		),
		'txtUsername' => array(
			'caption' 	=> _t('Username'),
			'value' 	=> $txtUsername,
			'rules' 	=> array('mandatory', 'username', 'validate_checkDuplicateUsername'),
			'parameters'=> array($hidEditId),
			'messages'	=> array(
				'validate_checkDuplicateUsername' => _t('Username already exists. Please try another one.')
			)
		),
		'txtEmail' => array(
			'caption' 	=> _t('Email'),
			'value' 	=> $txtEmail,
			'rules' 	=> array('mandatory', 'email'),
		)
	);

	if ( ! $hidEditId ) {
		$validations['txtPwd'] = array(
			'caption'	=> _t('Password'),
			'value' 	=> $txtPwd,
			'rules' 	=> array('mandatory', 'minLength', 'maxLength'),
			'min'		=> 8,
			'max'		=> 20,
		);
		$validations['txtConfirmPwd'] = array(
			'caption'	=> _t('Confirm Password'),
			'value' 	=> $txtConfirmPwd,
			'rules' 	=> array('mandatory', 'validate_confirmPassword'),
			'parameters'=> array($txtPwd),
			'messages'	=> array(
				'validate_confirmPassword' => _t('"%s" does not match.')
			)
		);
	}

	if(Validation::check($validations) == true){
		if($hidEditId){
			$data = array(
				'uid'		=> $hidEditId,
				'fullName' 	=> $txtFullName,
				'username' 	=> $txtUsername,
				'email' 	=> $txtEmail,
				'role'		=> $cboRole,
			);
			if( !empty($txtPwd) ){
				$data['password'] = $txtPwd;
			}
			if(db_update('user', $data)){
				$success = true;
			}
		}else{
			$auth = $_auth;
			$data = array(
				'fullName' 	=> $txtFullName,
				'username' 	=> $txtUsername,
				'email' 	=> $txtEmail,
				'password'	=> _encrypt($txtPwd),
				'role'		=> $cboRole,
			);
			if(db_insert('user', $data)){
				$success = true;
			}
		}

		if($success){
			Form::set('success', true);
			Form::set('redirect', _url('admin/user/list'));
		}
	}else{
		Form::set('error', Validation::$errors);
	}
}
Form::respond('frmUser');
