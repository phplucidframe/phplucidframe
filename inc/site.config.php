<?php
/**
 * This is a global site-specific configuration file
 *
 * @package		LC
 * @since		PHPLucidFrame v 1.0.0
 * @copyright	Copyright (c), PHPLucidFrame.
 * @author 		Sithu K. <hello@sithukyaw.com>
 * @link 		http://phplucidframe.sithukyaw.com
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

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
