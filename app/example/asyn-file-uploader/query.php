<?php
/**
 * The query.php (optional) should retrieve and process data from database and make it available to view.php.
 */

$id = _arg(2);

/**
 * The following commented section works with the sample database
 * You may uncomment it to test
 * ~/example/asyn-file-uploader/1
 * ~/example/asyn-file-uploader/2
 */

/*
if ($id && is_numeric($id)) {
	$sql = 'SELECT pimgId, pimgFileName FROM '.db_prefix().'post_image
			WHERE postId = :id';
	$image = db_fetchResult($sql, array('id' => $id));
}

$sql = 'SELECT docId, docFileName FROM '.db_prefix().'document';
$doc = db_fetchResult($sql);
*/
