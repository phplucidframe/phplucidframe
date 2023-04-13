<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by index.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<h3><?php echo $pageTitle; ?></h3>
<p>This is an example page which shows generic file upload handling.</p>
<div>
    <form id="frmUpload" method="post" class="no-ajax" enctype="multipart/form-data">
        <?php echo flash_get('file-upload-success'); ?>
        <div class="message"></div>
        <div class="table">
            <div class="row">
                <input type="file" name="filImage" id="filImage" />
            </div>
            <div class="row">
                <input type="submit" name="btnUpload" value="<?php echo _t('Upload'); ?>" class="button blue" />
            </div>
        </div>
        <?php form_token(); ?>
    </form>
    <?php form_respond('frmUpload', validation_get('errors')); ?>
</div>
