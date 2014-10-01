<?php
$success = false;
if(sizeof($_POST)){
	$post = _post($_POST);
	extract($post);

	if(isset($action) && $action == 'delete' && isset($hidDeleteId) && $hidDeleteId){
		# DELETE category
		if( db_delete('category', array('catId' => $hidDeleteId)) ){
			$success = true;
		}
	}else{
		# NEW/EDIT
		$validations = array(
			'txtName' => array(
				'caption' 	=> _t('Name'). ' ('._langName($lc_defaultLang).')',
				'value' 	=> $txtName,
				'rules' 	=> array('mandatory'),
				'parameters'=> array($hidEditId)
			)
		);

		if(Form::validate() && Validation::check($validations) == true){
			if($hidEditId){
				$data = array(
					'catId'	  => $hidEditId,
					'catName' => $txtName
				);
				# Get translation strings for "catName"
				$data = array_merge($data, _postTranslationStrings($post, array('catName' => 'txtName')));

				if(db_update('category', $data, false)){
					$success = true;
				}
			}else{
				$data = array(
					'catName' => $txtName,
				);
				# Get translation strings for "pptName"
				$data = array_merge($data, _postTranslationStrings($post, array('catName' => 'txtName')));

				if(db_insert('category', $data)){
					$success = true;
				}
			}
		}else{
			Form::set('error', Validation::$errors);
		}
	}
	if($success){
		Form::set('success', true);
		Form::set('callback', 'LC.Page.Category.list()'); # Ajax callback
	}
}
Form::respond('frmCategory'); # Ajax response