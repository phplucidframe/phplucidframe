<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */
$pageTitle = _t('AsynFileUploader Example');
$view = _app('view');
$id = _arg(2);

/**
 * The following commented section works with the sample database
 * You may uncomment it to test
 * ~/example/asyn-file-uploader/1
 * ~/example/asyn-file-uploader/2
 */

/*
if ($id && is_numeric($id)) {
    $image = db_select('post_image', 'pi')
        ->fields('pi', array('id', 'file_name'))
        ->where('pi.postId', $id)
        ->getSingleResult();
}

$doc = db_select('document', 'd')
    ->fields('d', array('id', 'file_name'))
    ->getSingleResult();
*/

$view->data = array(
    'pageTitle' => $pageTitle,
    'id' => $id,
);
