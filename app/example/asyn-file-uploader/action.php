<?php
/**
 * The action.php (optional) handles form submission.
 * It should perform form validation, create, update, delete of data manipulation to database.
 * By default, a form is initiated for AJAX and action.php is automatically invoked if the action attribute is not given in the <form> tag.
 */
$success = false;

if (sizeof($_POST)) {
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

	if (Form::validate($validations)) {
		/**
		For "photo",
			$post['photo']             = The uploaded file name saved in disk
			$post['photo-id']          = The ID in database related to the previously uploaded file
			$post['photo-dimensions']  = (Optional) Array of dimensions used to resize the images uploaded
			$post['photo-dir']         = The directory where the file(s) are saved, encoded by base64_encode()
			$post['photo-fileName']    = The same value of $post['photo']

		For "doc",
			$post['doc']               = The uploaded file name saved in disk
			$post['doc-id']            = The ID in database related to the previously uploaded file
			$post['doc-dir']           = The directory where the file(s) are saved, encoded by base64_encode()
			$post['doc-fileName']      = The same value of $post['doc']

		For "file",
			$post['file']              = The uploaded file name saved in disk
			$post['file-id']           = The ID in database related to the previously uploaded file
			$post['file-dir']          = The directory where the file(s) are saved, encoded by base64_encode()
			$post['file-fileName']     = The same value of $post['file']

		For "sheet",
			$post['sheet']             = The uploaded file name saved in disk
			$post['sheet-id']          = The ID in database related to the previously uploaded file
			$post['sheet-dir']         = The directory where the file(s) are saved, encoded by base64_encode()
			$post['sheet-fileName']    = The same value of $post['sheet']
		*/

		### The following commented section works with the sample database ###

		/*
		if (isset($post['photo-postId']) && $post['photo-postId']) {
			# Save file name in db
			# This is only needed when `onUpload` callback is not provided in view.php
			db_insert('post_image', array(
				'pimgFileName' => $post['photo'],
				'postId' => $post['photo-postId']
			), $useSlug=false);
		}

		# Save file name in db
		# This is only needed when `onUpload` callback is not provided in view.php
		db_insert('document', array(
			'docFileName' => $post['doc'],
		), $useSlug=false);
		*/

		$success = true;
		if ($success) {
			Form::set('success', true);
			Form::set('callback', 'postOutput('.json_encode($post).')');
		}
	} else {
		Form::set('error', Validation::$errors);
	}
}
Form::respond('frmAsynFileUpload'); # Ajax response
