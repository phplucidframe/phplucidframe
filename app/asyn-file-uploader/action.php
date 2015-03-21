<?php
/**
 * The action.php (optional) handles form submission.
 * It should perform form validation, create, update, delete of data manipulation to database.
 * By default, a form is initiated for AJAX and action.php is automatically invoked if the action attribute is not given in the <form> tag.
 */
$success = false;

if(sizeof($_POST)){
	$post = _post($_POST);

	$validations = array(
		'photo' => array(
			'caption'  => _t('Image'),
			'value'    => $post['photo'],
			'rules'    => array('mandatory'),
		),
		'doc' => array(
			'caption'  => _t('Doc'),
			'value'    => $post['doc'],
			'rules'    => array('mandatory'),
		),
		'file' => array(
			'caption'  => _t('File'),
			'value'    => $post['file'],
			//'rules'    => array('mandatory'),
		),
		'sheet' => array(
			'caption'  => _t('Sheet'),
			'value'    => $post['sheet'],
			//'rules'    => array('mandatory'),
		),
	);

	if(Form::validate() && Validation::check($validations) == true){
		/**
		For "photo",
			$post['photo']             = Array of the file names saved in disk
			$post['photo-dimensions']  = (Optional) Array of dimensions used to resize the images uploaded
			$post['photo-dir']         = The directory where the file(s) are saved, encoded by base64_encode()
			$post['photo-fileName']    = The original file name that user chose
			$post['photo-uniqueId']    = The generated unique Id

		For "doc",
			$post['doc']               = Array of the file names saved in disk
			$post['doc-dir']           = The directory where the file(s) are saved, encoded by base64_encode()
			$post['doc-fileName']      = The original file name that user chose
			$post['doc-uniqueId']      = The generated unique Id

		For "file",
			$post['file']              = Array of the file names saved in disk
			$post['file-dir']          = The directory where the file(s) are saved, encoded by base64_encode()
			$post['file-fileName']     = The original file name that user chose
			$post['file-uniqueId']     = The generated unique Id

		For "sheet",
			$post['sheet']             = Array of the file names saved in disk
			$post['sheet-dir']         = The directory where the file(s) are saved, encoded by base64_encode()
			$post['sheet-fileName']    = The original file name that user chose
			$post['sheet-uniqueId']    = The generated unique Id
		*/

		### The following commented section works with the sample database ###

//		db_delete_multi('post_image', array('postId' => 1)); # postId == 1 is for example
//
//		for($i=0; $i<count($post['photo']); $i++){
//			$img = $post['photo'][$i];
//
//			### Here, you may want to move the uploaded image to the other directory ###
//			// $dir = base64_decode($post['photo-dir']);
//			// rename($dir.$img, FILE.'path/to/new/dir/'.$img);
//
//			# Save file names in db
//			db_insert('post_image', array(
//				'postId' => 1,
//				'pimgFileName' => $img,
//				'pimgWidth' => current(explode('x', $post['photo-dimensions'][$i])),
//			), $useSlug=false);
//		}

		$success = true;
		if($success){
			Form::set('success', true);
			Form::set('callback', 'postOutput('.json_encode($post).')');
		}
	}else{
		Form::set('error', Validation::$errors);
	}
}
Form::respond('frmAsynFileUpload'); # Ajax response
