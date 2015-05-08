<?php
$success = false;
if (sizeof($_POST)) {
	$post 	= _post($_POST);
	$post['txtBody'] = _xss($_POST['txtBody']);	# if it is populated by Rich Text Editor
	extract($post);

	$validations['txtTitle'] = array(
		'caption' 	=> _t('Title'),
		'value' 	=> $txtTitle,
		'rules' 	=> array('mandatory'),
	);

	$validations['cboCategory'] = array(
		'caption' 	=> _t('Category'),
		'value' 	=> $cboCategory,
		'rules' 	=> array('mandatory'),
	);

	$validations['txtBody'] = array(
		'caption' 	=> _t('Body'),
		'value' 	=> $txtBody,
		'rules' 	=> array('mandatory')
	);

	if (Form::validate() && Validation::check($validations) == true) {
		if ($hidEditId) { # edit
			$data = array(
				'postId' 	=> $hidEditId,
				'postTitle_'.$hidLang => $txtTitle,
				'postBody_'.$hidLang  => $txtBody,
				'catId'		=> $cboCategory,
			);

			if ($hidLang == $lc_defaultLang) { # default langugage
				$useSlug = true;
				$data['postTitle'] 	= $txtTitle;
				$data['postBody'] 	= $txtBody;
			} else {
				$useSlug = false;
			}

			if (isset($txtSlug) && $txtSlug) { # if user entered slug manually
				$postSlug = _slug($txtSlug, $table='post', array('postId !=' => $hidEditId));
				$data['slug'] = $postSlug;
			}

			if (db_update('post', $data, $useSlug)) {
				$success = true;
			}
		} else { # new
			$data = array(
				'postTitle' => $txtTitle,
				'postBody' 	=> $txtBody,
				'postTitle_'.$hidLang => $txtTitle,
				'postBody_'.$hidLang  => $txtBody,
				'catId'		=> $cboCategory,
				'uid'		=> $_auth->uid
			);

			if (isset($txtSlug) && $txtSlug) { # if user entered slug manually
				$postSlug = _slug($txtSlug, $table='post', $condition=NULL);
				$data['slug'] = $postSlug;
			}
			if (db_insert('post', $data)) {
				$success = true;
			}
		}
		if ($success) {
			Form::set('success', true);
			Form::set('redirect', _url('admin/post/list'));
		}
	} else {
		Form::set('error', Validation::$errors);
	}
}
Form::respond('frmPost'); # Ajax response
