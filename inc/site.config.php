<?php
##############################################
# This is a site-specific configuration file #
##############################################
define('MAX_FILE_UPLOAD_SIZE', 20); # in MB

define('TODAY', date('Y-m-d'));

# The site contact email address - This address will be used as "To" for all incoming mails
$lc_siteReceiverEmail 	= ($_SERVER['HTTP_HOST'] == 'localhost') ? 'test@localhost.com' : 'test@example.com';
# The site sender email address - This address will be used as "From" for all outgoing mails
$lc_siteSenderEmail 	= "{$lc_siteName} <noreply@{$lc_siteDomain}>";