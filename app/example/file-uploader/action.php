<?php
/**
 * The action.php (optional) handles form submission.
 * It should perform form validation, create, update, delete of data manipulation to database.
 * By default, a form is initiated for AJAX and action.php is automatically invoked if the action attribute is not given in the <form> tag.
 */
$success = false;

if (sizeof($_POST)) {
    $post = _post($_POST);
    $image = isset($_FILES['filImage']) ? $_FILES['filImage'] : null;

    $validations = array(
        'filImage' => array(
            'caption'    => _t('Image'),
            'value'      => $image,
            'rules'      => array('mandatory', 'fileExtension', 'fileMaxSize'),
            'extensions' => array('jpg', 'jpeg', 'png', 'gif'),
            'maxSize'    => MAX_FILE_UPLOAD_SIZE,
            'messages'   => array(
                'mandatory'  => _t('Please select an image file.')
            )
        )
    );

    /* form token check && input validation check */
    if (form_validate($validations) === true) {
        $file = new File();
        // set file upload directory; default to `/files/tmp/`
        // this should be defined in site.config.php such as `define('POST_IMAGE_DIR', FILE . 'posts/');`
        // and use here `$file->set('uploadDir', POST_IMAGE_DIR . 'tmp/');`
        $file->set('uploadDir', FILE . 'tmp/'); // optional

        // set image dimension to resize
        // this should be defined in site.config.php such as `$lc_imageDimensions = array('400x300', '200x150');`
        // and use here `$file->set('dimensions', _cfg('imageDimensions'));`
        // optional; if this is omitted, only primary image will be uploaded to the uploadDir set above
        // by resizing according to `$lc_imageFilterSet['maxDimension']`
        $file->set('dimensions', array('400x300', '200x150'));

        // image resize mode:
        // FILE_RESIZE_BOTH (by default) - resize to the fitted dimension to the given dimension
        // FILE_RESIZE_WIDTH - resize to the given width, but height is aspect ratio of the width
        // FILE_RESIZE_HEIGHT - resize to the given height, but width is aspect ratio of the height
        $file->set('resizeMode', FILE_RESIZE_BOTH); // (optional) this overrides the global setting `$lc_imageFilterSet['resizeMode']`
        $file->set('maxDimension', '800x600'); // (optional) this overrides the global setting `$lc_imageFilterSet['maxDimension']`
        $file->set('jpgQuality', 75); // (optional) this overrides the global setting `$lc_imageFilterSet['jpgQuality']`

        $uploads = $file->upload('filImage'); // argument could be $_FILES['filImage'] or 'filImage'
        if ($uploads) {
            $success = true; # this should be set to true only when db operation is successful.
            if ($success) {
                form_set('success', true);

                $flashMsg = array();
                $flashMsg[] = _t('File has been uploaded.');
                $flashMsg[] = 'name: ' . $uploads['name'];
                $flashMsg[] = 'fileName: ' . $uploads['fileName'];
                $flashMsg[] = 'originalFileName: ' . $uploads['originalFileName'];
                $flashMsg[] = 'extension: ' . $uploads['extension'];
                flash_set($flashMsg, 'file-upload-success');

                _redirect();
            }
        } else {
            $error = $file->getError();
            validation_addError('filImage', $error['message']);
            form_set('error', validation_get('errors'));
        }
    } else {
        form_set('error', validation_get('errors'));
    }
}
