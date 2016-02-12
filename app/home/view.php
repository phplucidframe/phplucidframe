<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/tpl/header.php') ); ?>

<h1><?php echo _t('Welcome to PHPLucidFrame'); ?></h1>
<?php echo _tc('about'); ?>
<h3><?php echo _t('Prerequisites'); ?></h3>
<ul>
    <li>Web Server (For example, Apache with <code class="inline">mod_rewrite</code> enabled)</li>
    <li>PHP version 5.3.0 or newer (<code class="inline">mcrypt</code> extension enabled, but by no means required.)</li>
    <li>MySQL 5.0+ with MySQLi enabled.</li>
</ul>
<p>
    <a href="http://phplucidframe.github.io" class="button large green"><?php echo _t('Download PHPLucidFrame'); ?></a>
</p>
<p>
    <a href="<?php echo _url('example/blog'); ?>" class="button">AJAX List <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/articles'); ?>" class="button">Ordinary List <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('blog/2/url-rewrite-to-a-lucid-page-including-a-form-example'); ?>" class="button green">AJAX Form <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/comment'); ?>" class="button green">Generic Form <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/asyn-file-uploader'); ?>" class="button black">AsynFileUploader <?php echo _t('Example'); ?></a>
</p>

<?php include( _i('inc/tpl/footer.php') ); ?>
