<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This is a system-specific configuration file. All site general configuration are done here.
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

/**
* Don't escape quotes when reading files from the database, disk, etc.
*/
ini_set('magic_quotes_runtime', '0');
/**
 * Set the maximum amount of memory in bytes that a script is allowed to allocate.
 * This helps prevent poorly written scripts for eating up all available memory on a server
 */
ini_set('memory_limit', '256M');
/**
 * Set the maximum time in seconds a script is allowed to run before it is terminated by the parser.
 * This helps prevent poorly written scripts from tying up the server. The default setting is 30.
*/
ini_set('max_execution_time', 36000);
/**
 * Default Time Zone
 */
date_default_timezone_set('Asia/Rangoon');
/**
 * Routing query name
 */
define('ROUTE', 'route');
/**
 * Session prefix
 * All session variable names will be prefixed with this
 */
define('S_PREFIX', '__LucidFrame__');
/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration.
 * Any settings declared here will override the settings of the default config.
 *
 * ## Options
 *
 * - `name` - The name of the session to use. Defaults to 'LCSESSID'
 * - `use_cookies` - Whether cookies will be used to store the session id on the client side. Defaults to 1 (enabled).
 * - `use_only_cookies` - Whether the module will *only* use cookies to store the session id on the client side. Defaults to 1 (enabled).
 * - `use_trans_sid` - Transparent sid support is enabled or not
 * - `cache_limiter` - The cache control method used for session pages: nocache (default), private, private_no_expire, or public.
 * - `cookie_httponly` - Marks the cookie as accessible only through the HTTP protocol.
 * - `gc_divisor` - The probability that the gc (garbage collection) process is started on every session initialization. Default to 100.
 * - `gc_probability` - In conjunction with `gc_divisor` is used to manage probability that the gc (garbage collection) routine is started. Default to 1.
 * - `gc_maxlifetime` - The number of minutes after which data will be seen as 'garbage' and potentially cleaned up. Defaults to 240 minutes.
 * - `cookie_lifetime`- The number of minutes you want session cookies to live for. Defaults to 180 minutes
 *    The value 0 means "until the browser is closed.". Defaults to 180 mintues.
 * - `cookie_path` - The path to set in the session cookie. Defaults to '/'
 * - `save_path`- The path of the directory used to save session data. Defaults to ''.
 *
 * see more options at http://php.net/manual/en/session.configuration.php
 *
 * The hook `session_beforeStart()` is available to define in /app/helpers/session_helper.php
 * so that you could do something before session starts.
 */
$lc_session = array(
	'type' => 'default', // no need to change this for the time being
	'options' => array(
	)
);

# $lc_databases: The array specifies the database connection
$lc_databases = array(
	'default' => array(
		'engine' 	=> 'mysqli',
		'host'		=> 'localhost',
		'database'	=> '',
		'username'	=> '',
		'password'	=> '',
		'prefix'	=> '',
		'collation'	=> 'utf8_general_ci'
	)
);

# $lc_env: The setting for running environment: `development` or `production`
$lc_env = 'development';
# $lc_debugLevel: The debug level. If $lc_env = 'production', this is not considered.
# `1` - show fatal errors, parse errors, but no PHP startup errors
# `2` - show fatal errors, parse errors, warnings and notices
# `3` - show all errors and warnings, except of level E_STRICT prior to PHP 5.4.0.
# `int level` - set your own error reporting level. The parameter is either an integer representing a bit field, or named constants
#  @see http://php.net/manual/en/errorfunc.configuration.php#ini.error-reporting
$lc_debugLevel = 3;
# $lc_defaultDbConnection: The default database connection
$lc_defaultDbConnection = 'default';
# $lc_siteName: Site Name
$lc_siteName = 'LucidFrame';
# $lc_siteDomain: Site Domain Name
$lc_siteDomain = $_SERVER['HTTP_HOST'];
# $lc_baseURL: No trailing slash (only if it is located in a sub-directory)
# Leave blank if it is located in the document root
$lc_baseURL = 'LucidFrame';
# $lc_sites: consider sub-directories as additional site roots and namespaces
/**
 * ### Syntax
 * 	array(
 * 		'virtual_folder_name (namespace)'  => 'physical_folder_name_directly_under_app_directory
 * 	)
 * For example, if you have the configuration `'admin' => 'admin'` here, you let LucidFrame know to include the files
 * from those directories below without specifying the directory name explicilty in every include:
 *   /app/admin/css
 *   /app/admin/inc
 *   /app/admin/helpers
 *   /app/admin/js
 * you could also set 'lc-admin' => 'admin', then you can access http://localhost/LucidFrame/lc-admin
 * Leave this an empty array if you don't want this feature
 * @see https://github.com/cithukyaw/LucidFrame/wiki/Configuration-for-The-Sample-Administration-Module
 */
$lc_sites = array();
# $lc_homeRouting: Home page routing; if it is not set, default is 'home'
$lc_homeRouting = 'home';
# $lc_translationEnabled - Enable/Disable language translation
$lc_translationEnabled = true;
# $lc_languages: Site languages (leave this blank for single-language site)
$lc_languages = array(
	/* 'lang_code' => 'lang_name' */
	 'en' => 'English',
	 'my' => 'Myanmar',
);
# $lc_defaultLang: Default site language (leave blank for single-language site)
$lc_defaultLang = 'en';
# $lc_lang: Current selected language
$lc_lang = $lc_defaultLang;
# $lc_cleanURL: Enable/Disable clean URL
$lc_cleanURL = true;
# $lc_securitySalt: the key with which the data will be encrypted
# default hash string is located at ./inc/security.salt
# It is strongly recommended to change this and use the hash functions to create a key from a string.
# If you leave this blank, md5() only will be used for encryption
$lc_securitySalt = __salt();
# $lc_formTokenName - Customize your form token name at your own
$lc_formTokenName = 'LCFormToken';
# $lc_useDBAutoFields: Whether use DB auto field such as slug, created, updated, deleted, etc. or not
$lc_useDBAutoFields = true;
# $lc_minifyHTML: Compacting HTML code, including any inline JavaScript and CSS contained in it,
# can save many bytes of data and speed up downloading, parsing, and execution time.
# It is forced to `false` when $lc_env = 'development'
$lc_minifyHTML = true;
/*
 * Auth Module Configuration
 */
# $lc_auth: configuration for the user authentication
# This can be overidden by defining $lc_auth in /inc/site.config.php
$lc_auth = array(
	'table' => '', // table name, for example, user
	'fields' => array(
		'id'	=> '', 	// PK field name, for example, user_id
		'role'  => ''	// User role field name, for example, user_role
	),
	'perms'	=> array()
	/* for example
			array(
				'editor' => array(), // for example, 'role-name' => array('content-add', 'content-edit', 'content-delete')
				'admin' => array(),
			)
	*/
);
