<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/tpl/header.php') ); ?>

<h1><?php echo $pageTitle; ?></h1>

<form id="frmAsynFileUpload" method="post">
    <div class="message error"></div>
    <div class="table">
        <div class="row clearfix">
            <div class="col">
                <div class="row">
                    <div class="thumbnail thumbnail-100">
                        <span></span>
                        <a href="#">
                            <div class="img" id="photo-preview">Preview <br>(jpg, jpeg, png, gif)</div>
                        </a>
                    </div>
                </div>
                <div class="row">
                <?php
                    $file = _asynFileUploader('photo');
                    $file->setCaption('Choose Image'); # default to "Choose File"
                    $file->setMaxSize(MAX_FILE_UPLOAD_SIZE); # default to 10MB
                    # image dimension to resize, array('W1xH2', 'W1xH2');
                    # this should be defined in site.config.php, for example, $lc_imageDimensions = array('400x300', '200x150');
                    $file->setDimensions(array('400x300', '200x150'));
                    $file->setExtensions(array('jpg', 'jpeg', 'png', 'gif')); # default to any file
                    $file->setUploadAsOriginalFileName(true); // the original file name will be used for uploaded file
                    // $file->setUploadDir('path/to/upload/dir'); # default to /files/tmp
                    // $file->setButtons('btnSubmit'); # The button #btnSubmit will be disabled while file upload is in progress
                    // $file->isFileNameDisplayed(false);
                    // $file->isDeletable(false);
                    // $file->setOnUpload('example_photoUpload'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
                    // $file->setOnDelete('example_photoDelete'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
                    $file->setHidden('postId', $id); # FK
                    if (isset($image) && $image) {
                        # $image is retrieved in query.php
                        $file->setValue($image->file_name, $image->id);
                    }
                    $file->html();
                ?>
                </div>
            </div>
            <div class="col">
                <div class="row">
                    <div class="thumbnail thumbnail-100">
                        <span></span>
                        <a href="#">
                            <div class="img" id="doc-preview">
                                Preview<br>
                                (pdf, doc, docx, odt, ods, txt)
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                <?php
                    $file = _asynFileUploader('doc');
                    $file->setMaxSize(MAX_FILE_UPLOAD_SIZE);
                    $file->setExtensions(array('pdf', 'doc', 'docx', 'odt', 'ods', 'txt'));
                    // $file->setOnUpload('example_docUpload'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
                    // $file->setOnDelete('example_docDelete'); # Uncomment this if db is connected. This hook is defined in /app/helpers/file_helper.php
                    if (isset($doc) && $doc) {
                        # $doc is retrieved in query.php
                        $file->setValue($doc->file_name, $doc->id);
                    }
                    $file->html(array(
                        'id' => 'document'
                        // TODO: when id is specified, the button is not replaced by the throbber while upload or delete is in progress
                    ));
                ?>
                </div>
            </div>
            <div class="col">
                <div class="row">
                    <div class="thumbnail thumbnail-100">
                        <span></span>
                        <a href="#">
                            <div class="img" id="file-preview">Preview <br>(any file)</div>
                        </a>
                    </div>
                </div>
                <div class="row">
                <?php
                    $file = _asynFileUploader();
                    $file->html(array(
                        'class' => 'button black'
                    ));
                ?>
                </div>
            </div>
            <div class="col">
                <div class="row">
                    <div>No Preview for this</div>
                    <div>(xlx, xlsx, csv)</div>
                </div>
                <div class="row">
                <?php
                    $file = _asynFileUploader('sheet');
                    $file->setMaxSize(MAX_FILE_UPLOAD_SIZE); # default to 10MB
                    $file->setExtensions(array('xls', 'xlsx', 'csv'));
                    $file->html();
                ?>
                </div>
            </div>
        </div>
        <div class="row">
            <input type="submit" id="btnSubmit" name="btnSubmit" value="<?php echo _t('Submit'); ?>" class="button green" />
        </div>
    </div>
    <?php form_token(); ?>
</form>

<?php include( _i('inc/tpl/footer.php') ); ?>
