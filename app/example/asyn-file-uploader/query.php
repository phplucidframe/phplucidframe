<?php
/**
 * The query.php (optional) should retrieve and process data from database and make it available to view.php.
 */

$id = _arg(2);

### The following commented section works with the sample database ###

//if($id && is_numeric($id)){
//	$sql = 'SELECT pimgId "key", pimgFileName value FROM '.db_prefix().'post_image
//			WHERE postId = :id';
//	$images = db_extract($sql, array('id' => $id));
//}
//
//$sql = 'SELECT docId "key", docFileName value FROM '.db_prefix().'document
//		ORDER BY created DESC
//		LIMIT 1';
//$doc = db_extract($sql);
