<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by index.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
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
                    <?php $photo->html() ?>
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
                    $doc->html(array(
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
                    _asynFileUploader()->html(array(
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
                <?php $sheet->html() ?>
                </div>
            </div>
        </div>
        <div class="row">
            <input type="submit" id="btnSubmit" name="btnSubmit" value="<?php echo _t('Submit'); ?>" class="button green" />
        </div>
    </div>
    <?php form_token(); ?>
</form>

<script type="application/javascript">
    // This function shows the PHP POSTed data array in JSON format
    // when clicked the Submit button
    function postOutput($post) {
        console.log($post);
        alert(JSON.stringify($post));
        alert('Check your developer console');
    }

    // The following are example hooks defined for the last AsynFileUploader named "sheet"
    LC.AsynFileUploader.addHook('sheet', 'afterUpload', function(name, data) {
        console.log('afterUpload');
        console.log(name);
        console.log(data);
    });

    LC.AsynFileUploader.addHook('sheet', 'afterDelete', function(name, data) {
        console.log('afterDelete');
        console.log(name);
        console.log(data);
    });

    LC.AsynFileUploader.addHook('sheet', 'onError', function(name, error) {
        console.log('onError');
        console.log(name);
        console.log(error);
        alert(error.plain);
    });

</script>
