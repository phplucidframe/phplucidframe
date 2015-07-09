<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/tpl/header.php') ); ?>

<h3><?php echo $pageTitle; ?></h3>
<p>This is an example page which shows generic form handling without AJAX.</p>
<p>
    <form id="frmComment" method="post" class="no-ajax">
        <?php echo flash_get(); ?>
        <div class="message"></div>
        <div class="table">
            <div class="row">
                <input type="text" name="txtName" size="40" value="<?php echo Form::value('txtName'); ?>" placeholder="<?php echo _t('Enter your name *'); ?>" />
            </div>
            <div class="row">
                <input type="text" name="txtEmail" size="50" value="<?php echo Form::value('txtEmail'); ?>" placeholder="<?php echo _t('Enter your email, e.g., username@example.com *'); ?>" />
            </div>
            <div class="row">
                <input type="text" name="txtConfirmEmail" size="50" value="<?php echo Form::value('txtConfirmEmail'); ?>" placeholder="<?php echo _t('Re-type your email *'); ?>" />
            </div>
            <div class="row">
                <textarea name="txaComment" rows="7" cols="60" placeholder="<?php echo _t('Enter comment *'); ?>"><?php echo Form::value('txaComment'); ?></textarea>
            </div>
            <div class="row">
                <input type="submit" name="btnSubmit" value="<?php echo _t('Post Comment'); ?>" class="button green" />
            </div>
        </div>
        <?php Form::token(); ?>
    </form>
    <?php Form::respond('frmComment', Validation::$errors); ?>
</p>

<?php include( _i('inc/tpl/footer.php') ); ?>
