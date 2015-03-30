<?php
/**
 * All custom file helper functions specific to the site should be defined here.
 */

/**
 * This is just for example
 * The `onUpload` hook for an AsynFileUploader to do database operation
 * regarding to the uploaded files
 *
 * @param  array The   array of the uploaded file information:
 *
 *     array(
 *       'name'     => 'Name of the input element',
 *       'fileName' => 'The original file name',
 *       'extension'=> 'The selected and uploaded file extension',
 *       'dir'      => 'The uploaded directory',
 *       'uploads'  => array(
 *         'dimension (WxH) or index' => 'The uploaded file name like return from basename()'
 *       )
 *     )
 *
 * @param  array $post The POSTed information
 *
 *     array(
 *       '{name}'            => Array of the file names uploaded and saved in drive
 *       '{name}-dimensions' => Optional array of the file dimensions in WxH (it will not be available if it is not an image file),
 *       '{name}-id'         => Optional array of the database value IDs (if a file have previously been uploaded)
 *     )
 *
 * @return array The array of inserted IDs
 */
function example_photoUpload($file, $post){
	$ids = array();
	$sql = 'SELECT postId FROM '.db_prefix().'post_image WHERE pimgId = :id';
	if( $postId = db_fetch($sql, array(':id' => $post['photo-id'][0])) ){
		if( db_delete_multi('post_image', array('pimgId' => $post['photo-id'])) ){
			foreach($file['uploads'] as $dimension => $file){
				$width = current(explode('x', $dimension));
				# Save new file names in db
				db_insert('post_image', array(
					'postId' => $postId,
					'pimgFileName' => $file,
					'pimgWidth' => $width
				), $useSlug=false);
				$ids[] = db_insertId();
			}
		}
	}
	return $ids;
}

/**
 * This is just for example
 * The `onDelete` hook for an AsynFileUploader to do database operation
 * regarding to the deleted files
 *
 * @param array $ids The IDs to delete
 * @return boolean TRUE on success; otherwise FALSE
 */
function example_photoDelete($ids){
	return db_delete_multi('post_image', array('pimgId' => $ids));
}

/**
 * This is just for example
 * The `onUpload` hook for an AsynFileUploader to do database operation
 * regarding to the uploaded files
 *
 * @param  array The   array of the uploaded file information:
 *
 *     array(
 *       'name'     => 'Name of the input element',
 *       'fileName' => 'The original file name',
 *       'extension'=> 'The selected and uploaded file extension',
 *       'dir'      => 'The uploaded directory',
 *       'uploads'  => array(
 *         0 => 'The uploaded file name like return from basename()'
 *       )
 *     )
 *
 * @param  array $post The POSTed information
 *
 *     array(
 *       '{name}'            => Array of the file names uploaded and saved in drive
 *       '{name}-id'         => Optional array of the database value IDs (if a file have previously been uploaded)
 *     )
 *
 * @return array The array of inserted IDs
 */
function example_docUpload($file, $post){
	$ids = array();
	$docId = isset($post['doc-id']) ? $post['doc-id'] : 0;
	if( db_delete_multi('document', array('docId' => $docId)) ){
		foreach($file['uploads'] as $file){
			# Save new file names in db
			db_insert('document', array(
				'docFileName' => $file,
			), $useSlug=false);
			$ids[] = db_insertId();
		}
	}
	return $ids;
}

/**
 * This is just for example
 * The `onDelete` hook for an AsynFileUploader to do database operation
 * regarding to the deleted files
 *
 * @param array $ids The IDs to delete
 * @return boolean TRUE on success; otherwise FALSE
 */
function example_docDelete($ids){
	return db_delete_multi('document', array('docId' => $ids));
}

