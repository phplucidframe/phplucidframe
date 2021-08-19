<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by index.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<h3><?php echo $pageTitle ?></h3>
<p>This is an example page which shows generic form handling without AJAX.</p>
<div>
    <form id="frmComment" method="post" class="no-ajax">
        <?php echo flash_get('comment_posted'); ?>
        <div class="message"></div>
        <div class="table">
            <div class="row">
                <input type="text" name="txtName" value="<?php echo form_value('txtName'); ?>" placeholder="<?php echo _t('Enter your name *'); ?>" class="lc-form-input fluid-50" />
            </div>
            <div class="row">
                <input type="text" name="txtEmail" value="<?php echo form_value('txtEmail'); ?>" placeholder="<?php echo _t('Enter your email, e.g., username@example.com *'); ?>" class="lc-form-input fluid-50" />
            </div>
            <div class="row">
                <input type="text" name="txtConfirmEmail" value="<?php echo form_value('txtConfirmEmail'); ?>" placeholder="<?php echo _t('Re-type your email *'); ?>" class="lc-form-input fluid-50" />
            </div>
            <div class="row">
                <textarea name="txaComment" rows="7" class="lc-form-input fluid-50" placeholder="<?php echo _t('Enter comment *'); ?>"><?php echo form_value('txaComment'); ?></textarea>
            </div>
            <div class="row">
                <input type="submit" name="btnSubmit" value="<?php echo _t('Post Comment'); ?>" class="button green" />
            </div>
        </div>
        <?php form_token(); ?>
    </form>
    <?php form_respond('frmComment', validation_get('errors')); ?>
</div>
