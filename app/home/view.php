<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php. 
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/header.php') ); ?>

<h1><?php echo _t('Welcome to LucidFrame'); ?></h1>
<p>LucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development. 
The goal is to provide a structured framework with small footprint that enables rapidly robust web application development.</p>
<p>LucidFrame is simple, fast and easy to install. The minimum requirements are a web server and a copy of LucidFrame.</p>
<h3><?php echo _t('Prerequisites'); ?></h3>
<ul>
	<li>Web Server (For example, Apache with mod_rewrite enabled)</li>
	<li>PHP version 5.1.6 or newer (mcrypt extension enabled, but by no means required.)</li>
	<li>MySQL 5.0+ with MySQLi enabled.</li>
	<li>jQuery (LucidFrame provides AJAX Form and List APIs which require <a href="http://jquery.com" target="_blank">jQuery</a>)</li>
</ul>
<p>
	<a href="<?php echo _url('blog'); ?>" class="button">View AJAX List Example</a>
	<a href="<?php echo _url('articles'); ?>" class="button">View Ordinary List Example</a>
	<a href="<?php echo _url('blog/2/url-rewrite-to-a-lucid-page-including-a-form-example'); ?>" class="button">View AJAX Form Example</a>		
</p>

<?php include( _i('inc/footer.php') ); ?>