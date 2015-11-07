<?php
/**
 * This is a global site-specific configuration file
 *
 * @package     LC
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

define('MAX_FILE_UPLOAD_SIZE', 20); # in MB

define('TODAY', date('Y-m-d'));

# The site meta description for search engines
$lc_metaDescription = 'PHPLucidFrame (a.k.a LucidFrame) is a PHP application development framework that is simple, easy, lightweight and yet powerful.';
# The site meta keywords for search engines
$lc_metaKeywords = 'PHP LucidFrame, PHP, Framework, Web Application Development, Toolkit';
# The site contact email address - This address will be used as "To" for all incoming mails
# Update this in `/inc/parameter/*.php`
$lc_siteReceiverEmail = _p('siteReceiverEmail');
# The site sender email address - This address will be used as "From" for all outgoing mails
# Update this in `/inc/parameter/*.php`
$lc_siteSenderEmail = _p('siteSenderEmail');
# $lc_titleSeparator - Page title separator
$lc_titleSeparator = '-';
# $lc_breadcrumbSeparator - Breadcrumb separator
$lc_breadcrumbSeparator = '&raquo;';
# $lc_dateFormat: Date format
$lc_dateFormat = 'd-m-Y';
# $lc_dateTimeFormat: Date Time format
$lc_dateTimeFormat = 'd-m-Y h:ia';
# $lc_pageNumLimit: number of page numbers to be shown in pager
$lc_pageNumLimit = 10;
# $lc_itemsPerPage: number of items per page in pager
$lc_itemsPerPage = 15;
# $lc_reqSign: Sign for mandatory fields
$lc_reqSign = '<span class="required">*</span>';
# $lc_nullFill: Sign for the empty fields
$lc_nullFill = '<span class="nullFill">-</span>';
