<?php
################################################
# This is a system-specific configuration file #
# All site general configration are done here  #
################################################

# Don't escape quotes when reading files from the database, disk, etc.
ini_set('magic_quotes_runtime', '0');

# session.save_path defines the argument which is passed to the save handler.
# If you choose the default files handler, this is the path where the files are created. Defaults to /tmp
//ini_set('session.save_path', FILE . 'sessions/');

# Use session cookies, not transparent sessions that puts the session id in the query string.
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
# Don't send HTTP headers using PHP's session handler.
ini_set('session.cache_limiter', 'none');
# Use httponly session cookies.
ini_set('session.cookie_httponly', '1');
/**
 * Some distributions of Linux (most notably Debian) ship their PHP
 * installations with garbage collection (gc) disabled. Since this depends on
 * PHP's garbage collection for clearing sessions, ensure that garbage
 * collection occurs by using the most common settings.
 */
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
/**
 * Set session lifetime (in seconds), i.e. the time from the user's last visit
 * to the active session may be deleted by the session garbage collector. When
 * a session is deleted, authenticated users are logged out, and the contents
 * of the user's $_SESSION variable is discarded.
 */
ini_set('session.gc_maxlifetime', 86400); 	# 24 hours
/**
 * Set session cookie lifetime (in seconds), i.e. the time from the session is
 * created to the cookie expires, i.e. when the browser is expected to discard
 * the cookie. The value 0 means "until the browser is closed".
 */
ini_set('session.cookie_lifetime', 86400); 	# 24 hours
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

// For IE<=8
session_cache_limiter("must-revalidate");

session_start();
/**
 * Default Time Zone
 */
date_default_timezone_set('Asia/Rangoon');

# Routing query name
define('ROUTE', 'route');
# session prefix
define('S_PREFIX', '__LucidFrame__');

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
$lc_sites = array(
	'admin' => 'admin',
	/* 'virtual_folder_name (namespace)'  => 'physical_folder_name_directly_under_app_directory */
	/* you could also set 'lc-admin' => 'admin', then you can access http://localhost/LucidFrame/lc-admin */
);
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
# It is strongly recommended to change this and use the mhash functions to create a key from a string.
# If you leave this blank, md5() only will be used for encryption
$lc_securitySalt = file_get_contents(INC . 'security.salt');
# $lc_formTokenName - Customize your form token name at your own
$lc_formTokenName = 'LCFormToken';
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