<?php
/**
 * All custom file helper functions specific to the site should be defined here.
 */

/**
 * This is just for example
 * The `onUpload` hook for an AsynFileUploader to do database operation
 * regarding to the uploaded files
 *
 * @param  array $file The array of the uploaded file information:
 *
 *     array(
 *       'name'             => 'Name of the input element',
 *       'fileName'         => 'The uploaded file name',
 *       'originalFileName' => 'The original file name user selected',
 *       'extension'        => 'The selected and uploaded file extension',
 *       'dir'              => 'The uploaded directory',
 *     )
 *
 * @param  array $post The POSTed information
 *
 *     array(
 *       '{name}'            => File name uploaded previously
 *       '{name}-id'         => The database value ID (if a file have previously been uploaded)
 *       '{name}-dimensions' => Optional array of the file dimensions in WxH (it will not be available if it is not an image file),
 *       '{name}-{fieldName} => Optional hidden values
 *     )
 *
 * @return integer
 */
function example_photoUpload($file, $post) {
	if (isset($post['photo-postId']) && $post['photo-postId']) {
		# Save new file names in db
		db_insert('post_image', array(
			'postId' => $post['photo-postId'],
			'pimgFileName' => $file['fileName']
		), $useSlug=false);
		return db_insertId();
	}
	return 0;
}

/**
 * This is just for example
 * The `onDelete` hook for an AsynFileUploader to do database operation
 * regarding to the deleted file
 *
 * @param array $id The ID to delete
 * @return boolean TRUE on success; otherwise FALSE
 */
function example_photoDelete($id) {
	if ($id) {
		return db_delete('post_image', array('pimgId' => $id));
	}
}

/**
 * This is just for example
 * The `onUpload` hook for an AsynFileUploader to do database operation
 * regarding to the uploaded files
 *
 * @param  array $file The array of the uploaded file information:
 *
 *     array(
 *       'name'             => 'Name of the input element',
 *       'fileName'         => 'The uploaded file name',
 *       'originalFileName' => 'The original file name user selected',
 *       'extension'        => 'The selected and uploaded file extension',
 *       'dir'              => 'The uploaded directory',
 *     )
 *
 * @param  array $post The POSTed information
 *
 *     array(
 *       '{name}'            => Array of the file names uploaded and saved in drive
 *       '{name}-id'         => The database value ID (if a file have previously been uploaded)
 *       '{name}-{fieldName} => Optional hidden values
 *     )
 *
 * @return integer
 */
function example_docUpload($file, $post) {
	# Save new file names in db
	db_insert('document', array(
		'docFileName' => $file['fileName']
	), $useSlug=false);
	return db_insertId();
}

/**
 * This is just for example
 * The `onDelete` hook for an AsynFileUploader to do database operation
 * regarding to the deleted files
 *
 * @param array $ids The IDs to delete
 * @return boolean TRUE on success; otherwise FALSE
 */
function example_docDelete($id) {
	if ($id) {
		return db_delete('document', array('docId' => $id));
	}
}

