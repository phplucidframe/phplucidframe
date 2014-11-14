<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/tpl/header.php') ); ?>

<h1><?php echo _t('Welcome to LucidFrame'); ?></h1>
<?php echo _tc('about'); ?>
<h3><?php echo _t('Prerequisites'); ?></h3>
<ul>
	<li>Web Server (For example, Apache with mod_rewrite enabled)</li>
	<li>PHP version 5.2.0 or newer (mcrypt extension enabled, but by no means required.)</li>
	<li>MySQL 5.0+ with MySQLi enabled.</li>
</ul>
<p>
	<a href="<?php echo _url('blog'); ?>" class="button">View AJAX List Example</a>
	<a href="<?php echo _url('articles'); ?>" class="button">View Ordinary List Example</a>
	<a href="<?php echo _url('blog/2/url-rewrite-to-a-lucid-page-including-a-form-example'); ?>" class="button">View AJAX Form Example</a>
</p>

<?php include( _i('inc/tpl/footer.php') ); ?>
