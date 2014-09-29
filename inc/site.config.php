<?php
#####################################################
# This is a global site-specific configuration file #
#####################################################
define('MAX_FILE_UPLOAD_SIZE', 20); # in MB

define('TODAY', date('Y-m-d'));

# The site meta description for search engines
$lc_metaDescription 	= 'LucidFrame is a micro application development framework - a toolkit for PHP users. It provides several general purpose helper functions and logical structure for web application development';
# The site meta keywords for search engines
$lc_metaKeywords 		= 'PHP LucidFrame, PHP, Framework, Web Application Development, Toolkit';
# The site contact email address - This address will be used as "To" for all incoming mails
$lc_siteReceiverEmail 	= ($_SERVER['HTTP_HOST'] == 'localhost') ? 'test@localhost.com' : 'test@example.com';
# The site sender email address - This address will be used as "From" for all outgoing mails
$lc_siteSenderEmail 	= "{$lc_siteName} <noreply@{$lc_siteDomain}>";

# $lc_auth: configuration for the user authentication
# This overrides $lc_auth in /inc/config.php, but you could configure it in config.php without defining here
$lc_auth = array(
	'table' => 'user', // table name, for example, user
	'fields' => array(
		'id'	=> 'uid', 	// PK field name, for example, user_id
		'role'  => 'role'	// User role field name for example, user_role
	),
	'perms'	=> array()
	/* for example
			array(
				'editor' => array(), // for example, 'role-name' => array('content-add', 'content-edit', 'content-delete')
				'admin' => array(),
			)
	*/
);