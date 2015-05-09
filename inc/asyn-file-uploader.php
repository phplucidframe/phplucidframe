<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * This file performs the file upload process and file delete process of AsynFileUploader
 *
 * @package     LC
 * @since       PHPLucidFrame v 1.3.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

chdir('../');
require_once('bootstrap.php');

### FILE DELETE HANDLER ###
if (count($_POST) && isset($_POST['action']) && $_POST['action'] === 'delete' && count($_FILES) === 0) {
	$post = _post($_POST);
	$files = array();
	// unlink the physical files
	if (is_array($post['files'])) {
		$dir = base64_decode($post['dir']);
		foreach ($post['files'] as $fname) {
			$file = $dir . $fname;
			if (is_file($file) && file_exists($file)) {
				unlink($file);
			}
			$files[] = $file;
		}
		// invoke custom delete hook (if any)
		if ($post['onDelete'] && function_exists($post['onDelete'])) {
			call_user_func($post['onDelete'], $post['ids']);
		}
	}

	$return = array(
		'name'    => $post['name'],
		'success' => true,
		'error'   => '',
		'ids'     => $post['ids'],
		'files'   => $files
	);
	echo json_encode($return);
	exit;
}

### FILE UPLOAD HANDLER ###
$get = _get($_GET);

$name         = $get['name'];
$buttonId     = $get['id'];
$label        = $get['label'];
$uploadDir    = base64_decode($get['dir']);
$webDir       = str_replace('\\', '/', str_replace(ROOT, WEB_ROOT, $uploadDir));
$maxSize      = $get['maxSize'];
$fileTypes    = ($get['exts']) ? explode(',', $get['exts']) : '';
$phpCallback  = (isset($get['phpCallback'])) ? $get['phpCallback'] : '';
$buttons      = (isset($get['buttons'])) ? explode(',', $get['buttons']) : array();
$dimensions   = (isset($get['dimensions'])) ? explode(',', $get['dimensions']) : '';

$data = array(
	'success'          => false,
	'id'               => $buttonId,
	'name'             => $name,
	'label'            => $label,
	'disabledButtons'  => $buttons,
	'displayFileName'  => '',
	'displayFileLink'  => '',
	'uniqueId'         => '',
	'extension'        => '',
	'filesUploaded'    => array(),
	'dimensions'       => array(),
	'savedIds'        => array(),
	'error'            => ''
);

if (count($_FILES)) {
	$post = _post($_POST);

	$validations = array(
		$name => array(
			'caption'  => $label,
			'value'    => $_FILES['file'],
			'rules'    => array('fileMaxSize'),
			'maxSize'  => $maxSize,
			'messages' => array(
				'fileMaxSize' => _t('File size exceeds the maximum allowed upload size %dMB', $maxSize)
			)
		)
	);

	if ($fileTypes) {
		$validations[$name]['rules'][] = 'fileExtension';
		$validations[$name]['extensions'] = $fileTypes;
		$validations[$name]['messages']['fileExtension'] = _t('File must be one of the file types: %s.', _fstr($fileTypes));
	}

	if (Validation::check($validations, 'single') === true) {
		$file = new File($name);
		$uniqueId = $file->get('uniqueId');
		$file->set('uploadPath', $uploadDir);
		if ($dimensions) {
			$file->set('dimensions', $dimensions);
			$file->set('resize', FILE_RESIZE_BOTH);
		}

		$fileData = $file->upload($_FILES['file']);

		if ($fileData) {
			$data['success']         = true;
			$data['displayFileName'] = $fileData['fileName'];
			$data['displayFileLink'] = ($dimensions) ? $webDir . $fileData['uploads'][$dimensions[0]] : $webDir . $fileData['uploads'][0];
			$data['uniqueId']        = $uniqueId;
			$data['extension']       = $fileData['extension'];
			$data['filesUploaded']   = array_values($fileData['uploads']);
			$data['dimensions']      = array_keys($fileData['uploads']);
			if ($data['dimensions'][0] === 0) {
				$data['dimensions'] = array();
			}

			# if onUpload hook is specified, execute the hook
			if ($phpCallback && function_exists($phpCallback)) {
				$data['savedIds'] = call_user_func($phpCallback, $fileData, $post);
			}

			# delete the existing files if any
			# before `$_POST[$name]` is replaced with new one in javascript below
			$existingFiles = $post[$name];
			if (is_array($existingFiles) && count($existingFiles)) {
				foreach ($existingFiles as $oldFile) {
					$oldFile = $uploadDir . $oldFile;
					if (is_file($oldFile) && file_exists($oldFile)) {
						unlink($oldFile);
					}
				}
			}
		} else {
			$error = $file->getError();
			Validation::addError($name, $error['message']);
		}
	}

	if (count(Validation::$errors)) {
		# if there is any validation error and if there was any uploaded file
		$data['error'] = array(
			'id' => Validation::$errors[0]['htmlID'],
			'plain' => Validation::$errors[0]['msg'],
			'html' => _msg(Validation::$errors, 'error', 'html')
		);
		$existingFiles = $post[$name];
		if (is_array($existingFiles) && count($existingFiles) && $existingFiles[0]) {
			$data['filesUploaded'] = $existingFiles;
		}
	}

	if ( !(is_array($data['savedIds']) && count($data['savedIds']) > 0) &&
	   isset($post[$name.'-id']) && is_array($post[$name.'-id']) ) {
		# if there was any saved data in db
		$data['savedIds'] = $post[$name.'-id'];
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>AsyncFileUploader</title>
	<script language="javascript">
		var parent = window.top;
		var $ = window.top.jQuery;
		var interval = null;
		var data = <?php echo json_encode($data); ?>;

		function startAutoUpload() {
			if (interval) clearTimeout(interval);
			interval = setTimeout( function() {
				$(data.id).hide();
				$('#asynfileuploader-progress-' + data.name).show();
				$('#asynfileuploader-delete-' + data.name).hide();
				$('#asynfileuploader-error-' + data.name).html('');
				$('#asynfileuploader-error-' + data.name).hide();
				if ($('#asynfileuploader-name-' + data.name).size()) {
					$('#asynfileuploader-name-' + data.name).hide();
				}
				if (data.disabledButtons.length) {
					for (var i=0; i<data.disabledButtons.length; i++) {
						var $button = $('#'+data.disabledButtons[i]);
						if ($button.size()) {
							$button.attr('disabled', 'disabled');
						}
					}
				}
				// post the existing files if any
				$('#asynfileuploader-value-' + data.name).html($('#asynfileuploader-value-' + data.name).html());
				// submit the upload form
				document.fileupload.submit();
			}, 1000 );
		}
	</script>
</head>
<body style="margin:0">
	<form name="fileupload" id="file-upload" method="post" class="no-ajax" enctype="multipart/form-data">
		<input type="file" name="file" id="file" size="30" onChange="startAutoUpload()" style="height:100px" />
		<?php # the existing file uploaded ?>
		<div id="asynfileuploader-value-<?php echo $name; ?>">
			<input type="hidden" name="<?php echo $name; ?>[]" />
		</div>
	</form>
</body>
</html>
<script type="text/javascript">
	// Progress off
	$('#'+data.id).css('display', 'inline-block');
	$('#asynfileuploader-progress-' + data.name).hide();
	if (data.success) {
		if ($('#asynfileuploader-name-' + data.name)) {
			$('#asynfileuploader-name-' + data.name).html('<a href="' + data.displayFileLink + '" target="_blank">' + data.displayFileName + '</a>');
			$('#asynfileuploader-name-' + data.name).css('display', 'inline-block');
		}
		// POSTed values
		$('#asynfileuploader-fileName-' + data.name).val(data.displayFileName);
		$('#asynfileuploader-uniqueId-' + data.name).val(data.uniqueId);
		// The file uploaded or The array of files uploaded
		var inputs = '';
		if (data.filesUploaded.length === 0) {
			// no file uploaded
			inputs += '<input type="hidden" name="' + data.name + '" value="" />';
		} else {
			// multiple files uploaded in the case of image by dimensions
			for (var i=0; i<data.filesUploaded.length; i++) {
				var fname = data.filesUploaded[i];
				inputs += '<input type="hidden" name="' + data.name + '[]" value="' + fname + '" />';
			}
			for (var i=0; i<data.dimensions.length; i++) {
				var dimension = data.dimensions[i];
				inputs += '<input type="hidden" name="' + data.name + '-dimensions[]" value="' + dimension + '" />';
			}
			// if there are IDs saved in database related to the uploaded files
			for (var i=0; i<data.savedIds.length; i++) {
				var id = data.savedIds[i];
				inputs += '<input type="hidden" name="' + data.name + '-id[]" value="' + id + '" />';
			}
		}
		$('#asynfileuploader-value-' + data.name).html(inputs);
		// run hook
		window.parent.LC.AsynFileUploader.onUpload({
			name:       data.name,
			id:         data.id,
			fileName:   data.displayFileName,
			extension:  data.extension,
			url:        data.displayFileLink,
			caption:    data.label,
			uploads:    data.filesUploaded
		});
	} else {
		if (data.error) {
			// show errors
			parent.LC.AsynFileUploader.hook.onError(data.name, data.error);
		}
		if (data.filesUploaded.length) {
			// if there is any file uploaded previously
			$('#asynfileuploader-delete-' + data.name).css('display', 'inline-block');
			if ($('#asynfileuploader-name-' + data.name).size()) {
				$('#asynfileuploader-name-' + data.name).css('display', 'inline-block');
			}
		}
	}
	// re-enable buttons
	if (data.disabledButtons.length) {
		for (var i=0; i<data.disabledButtons.length; i++) {
			var $button = $('#'+data.disabledButtons[i]);
			if ($button.size()) {
				$button.attr('disabled', 'disabled');
			}
		}
	}
</script>
