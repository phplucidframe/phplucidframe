<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */
$pageTitle = _t('AsynFileUploader Example');
$view = _app('view');
$id = _arg(2);

_app('title', $pageTitle);

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
        ->where('pi.post_id', $id)
        ->getSingleResult();
}

$doc = db_select('document', 'd')
    ->fields('d', array('id', 'file_name'))
    ->getSingleResult();
*/

$photo = _asynFileUploader('photo');
$photo->setCaption('Choose Image'); # default to "Choose File"
$photo->setMaxSize(MAX_FILE_UPLOAD_SIZE); # default to 10MB
# image dimension to resize, array('W1xH2', 'W1xH2');
# this should be defined in site.config.php, for example, $lc_imageDimensions = array('400x300', '200x150');
$photo->setDimensions(array('400x300', '200x150'));
$photo->setExtensions(array('jpg', 'jpeg', 'png', 'gif')); # default to any file
$photo->setUploadAsOriginalFileName(true); // the original file name will be used for uploaded file
// $photo->setUploadDir('path/to/upload/dir'); # default to /files/tmp
// $photo->setButtons('btnSubmit'); # The button #btnSubmit will be disabled while file upload is in progress
// $photo->isFileNameDisplayed(false);
// $photo->isDeletable(false);
// $photo->setOnUpload('example_photoUpload'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
// $photo->setOnDelete('example_photoDelete'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
$photo->setHidden('postId', $id); # FK
// if (isset($image) && $image) {
//     # $image is retrieved in query.php
//     $photo->setValue($image->file_name, $image->id);
// }

$doc = _asynFileUploader('doc');
$doc->setMaxSize(MAX_FILE_UPLOAD_SIZE);
$doc->setExtensions(array('pdf', 'doc', 'docx', 'odt', 'ods', 'txt'));
// $doc->setOnUpload('example_docUpload'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
// $doc->setOnDelete('example_docDelete'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
// if (isset($doc) && $doc) {
//     # $doc is retrieved in query.php
//     $doc->setValue($doc->file_name, $doc->id);
// }

$sheet = _asynFileUploader('sheet');
$sheet->setMaxSize(MAX_FILE_UPLOAD_SIZE); # default to 10MB
$sheet->setExtensions(array('xls', 'xlsx', 'csv'));

$view->data = array(
    'pageTitle' => $pageTitle,
    'photo'     => $photo,
    'doc'       => $doc,
    'sheet'     => $sheet,
    'id'        => $id,
);
