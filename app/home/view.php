<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<h1><?php echo _t('Welcome to PHPLucidFrame'); ?></h1>
<?php echo _tc('about'); ?>
<h3><?php echo _t('Prerequisites'); ?></h3>
<ul>
    <li>Web Server (For example, Apache with <code class="inline">mod_rewrite</code> enabled)</li>
    <li>PHP version 5.6 or newer is recommended. It should work on 5.3 as well, but we strongly advise you NOT to run such old versions of PHP.</li>
    <li>MySQL 5.0+ or newer</li>
</ul>
<p>
    <a href="http://phplucidframe.com" class="button large blue" target="_blank"><?php echo _t('Download PHPLucidFrame'); ?></a>
</p>
<p>
    <a href="<?php echo _url('example/blog'); ?>" class="button mini">AJAX List <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/articles'); ?>" class="button mini">Ordinary List <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('blog/2/url-rewrite-to-a-lucid-page-including-a-form-example'); ?>" class="button blue mini">AJAX Form <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/comment'); ?>" class="button blue mini">Generic Form <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/asyn-file-uploader'); ?>" class="button black mini">AsynFileUploader <?php echo _t('Example'); ?></a>
    <a href="<?php echo _url('example/component'); ?>" class="button black mini">Live Component <?php echo _t('Example'); ?></a>
</p>

<script type="text/javascript">
    LC.Page.Home.init();
</script>
