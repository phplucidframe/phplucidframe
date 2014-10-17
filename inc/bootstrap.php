<?php
define('APP_DIR', 'app');

if( !defined('APP_ROOT') ){
	$APP_ROOT = rtrim(getcwd(), '/').'/';
	if(isset($_GET['bootstrap'])) $APP_ROOT .= APP_DIR . '/';
	define('APP_ROOT', $APP_ROOT); # including trailing slash
}

if( !defined('ROOT') ){
	$ROOT = str_replace(APP_DIR, '', rtrim(APP_ROOT, '/'));
	if( strrpos($ROOT, '/') != strlen($ROOT)-1 ) $ROOT .= '/'; # include trailing slash if not
	define('ROOT', $ROOT);
}

# path to inc/ folder
define('INC', ROOT.'inc/');
# path to helpers/ folder
define('HELPER', ROOT.'helpers/');
# path to i18n/ folder
define('I18N', ROOT.'i18n/');
# path to vendor/ folder
define('VENDOR', ROOT.'vendor/');
# path to business/ folder
define('BUSINESS', ROOT.'business/');
# path to files/ folder
define('FILE', ROOT.'files/');
# path to files/cache filder
define('CACHE', FILE.'cache/');

# System configuration variables
require INC . 'config.php';

$lc_sitewideWarnings = array();

if( !isset($lc_languages) || (isset($lc_languages) && !is_array($lc_languages)) ){
	$lc_languages = array('en' => 'English');
}

$requestURI = trim(ltrim($_SERVER['REQUEST_URI'], '/'.$lc_baseURL)); # /base-dir/path/to/sub/dir to path/to/sub/dir
$requestURI = substr( $_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '/'.$lc_baseURL) + strlen($lc_baseURL) + 1 );
$requestURI = ltrim($requestURI, '/');
$request 	= explode('/', $requestURI);
$lc_namespace = $request[0];

# Clean lang code in URL
if(array_key_exists($lc_namespace, $lc_languages)){
	array_shift($request);
	$requestURI = ltrim(ltrim($requestURI, $lc_namespace), '/'); # clean the language code from URI
	if(count($request)) $lc_namespace = $request[0];
	else $lc_namespace = '';
}

if( !(isset($lc_sites) && is_array($lc_sites) && array_key_exists($lc_namespace, $lc_sites)) ){
	$lc_namespace = '';
}

# REQUEST_URI excluding the base URL
define('REQUEST_URI', trim($requestURI, '/'));
# Namespace according to the site directories
define('LC_NAMESPACE', $lc_namespace);

unset($requestURI);
unset($request);

/**
 * File include helper
 * Find files under the default directories inc/, js/, css/ according to the defined site directories $lc_sites
 *
 * @param $file	string File name with directory path
 * @param $recursive boolean True to find the file name until the site root
 *
 * @return string File name with absolute path if it is found, otherwise return an empty string
 */
function _i($file, $recursive=true){
	global $lc_baseURL;
	global $lc_sites;
	global $lc_languages;

	$ext = strtolower(substr($file, strrpos($file, '.')+1)); # get the file extension
	if( in_array($ext, array('js', 'css')) ){
		$appRoot = WEB_APP_ROOT;
		$root 	 = WEB_ROOT;
		$clientFile = true;
	}else{
		$appRoot = APP_ROOT;
		$root 	 = ROOT;
		$clientFile = false;
	}

	if( !is_array($lc_languages) ){
		$lc_languages = array('en' => 'English');
	}

	$requestURI = trim(ltrim($_SERVER['REQUEST_URI'], '/'.$lc_baseURL)); # /base-dir/path/to/sub/dir to path/to/sub/dir
	$request 	= explode('/', $requestURI);

	$needle = $request[0];
	# Clean lang code in URL
	if(array_key_exists($needle, $lc_languages)){
		array_shift($request);
		if(count($request)) $needle = $request[0];
		else $needle = '';
	}

	if( LC_NAMESPACE == '' ){
	# Find in APP_ROOT -> ROOT
		$folders = array(
			APP_ROOT 	=> $appRoot,
			ROOT 		=> $root
		);

	}
		
	if(isset($lc_sites) && is_array($lc_sites) && count($lc_sites)){
		if(array_key_exists(LC_NAMESPACE, $lc_sites)){
		# Find in SUB-DIR -> APP_ROOT -> ROOT
			$folders = array(
				APP_ROOT.$lc_sites[LC_NAMESPACE].'/'	=> $appRoot . $lc_sites[LC_NAMESPACE] . '/',
				APP_ROOT 		 			=> $appRoot,
				ROOT 			 			=> $root
			);
		}
	}
	
	# $key is for file_exists()
	# $value is for include() or <script> or <link>
	foreach($folders as $key => $value){
		$fileWithPath = $key . $file;
		if( is_file($fileWithPath) && file_exists($fileWithPath) ){
			$fileWithPath = $value . $file;
			return $fileWithPath;
		}
		if($recursive == false) break;
	}	
	
	if(strstr($_SERVER['PHP_SELF'], APP_DIR)){
		if(is_file($file) && file_exists($file)){
			return $file;
		}
		if($recursive == true){
			return $root . $file;
		}		
	}else{
		return '';
	}
}

# DB configuration & DB helper (required)
if(isset($lc_databases[$lc_defaultDbConnection]) && is_array($lc_databases[$lc_defaultDbConnection]) && $lc_databases[$lc_defaultDbConnection]['engine']){
	if( $file = _i( 'helpers/db_helper.php', false) ) include_once $file;
	require_once HELPER . 'db_helper.'.$lc_databases[$lc_defaultDbConnection]['engine'].'.php';

	if(db_host($lc_defaultDbConnection) && db_user($lc_defaultDbConnection) && db_name($lc_defaultDbConnection)){
		# Start DB connection
		db_connect($lc_defaultDbConnection);
	}
}

# Utility helpers (required)
if( $file = _i( 'helpers/utility_helper.php', false) ) include_once $file;
require_once HELPER . 'utility_helper.php';

_loader('i18n_helper', HELPER);
_loader('session_helper', HELPER);
_loader('validation_helper', HELPER);
_loader('auth_helper', HELPER);
_loader('pager_helper', HELPER);
_loader('form_helper', HELPER);
_loader('file_helper', HELPER);

if(file_exists(INC.'autoload.php')) require_once INC.'autoload.php';

# Translation helper (unloadable from /inc/autoload.php)
if( $moduleI18n = _readyloader('i18n_helper') ) require_once $moduleI18n;
_unloader('i18n_helper', HELPER);

# Session helper (unloadable from /inc/autoload.php)
if( $file = _i( 'helpers/session_helper.php', false) ) include_once $file;
if( $moduleSession = _readyloader('session_helper') ) require_once $moduleSession;
_unloader('session_helper', HELPER);

# Route helper (required)
require HELPER . 'route_helper.php'; # WEB_ROOT and WEB_APP_ROOT is created in route_helper

# Load translations
if( $moduleI18n ) i18n_load();

# Site-specific configuration variables
require INC . 'site.config.php';
if( $file = _i( 'inc/site.config.php', false) ) include_once $file;

define('CSS', WEB_ROOT.'css/');
define('JS', WEB_ROOT.'js/');
define('WEB_VENDOR', WEB_ROOT.'vendor/');

# Validation helper (unloadable from /inc/autoload.php)
if( $file = _i( 'helpers/validation_helper.php', false) ) include_once $file;
if( $moduleValidation = _readyloader('validation_helper') ) require_once $moduleValidation;
_unloader('validation_helper', HELPER);

# Auth helper (unloadable from /inc/autoload.php)
if( $file = _i( 'helpers/auth_helper.php', false) ) include_once $file;
if( $moduleAuth = _readyloader('auth_helper') ) require_once $moduleAuth;
_unloader('auth_helper', HELPER);

# Pager helper
if( $file = _i( 'helpers/pager_helper.php', false) ) include_once $file;
if( $modulePager = _readyloader('pager_helper') ) require_once $modulePager;
_unloader('pager_helper', HELPER);

# Security helper (required)
require_once HELPER . 'security_helper.php';

# Ajax Form helper (unloadable from /inc/autoload.php)
if( $moduleForm = _readyloader('form_helper') ) require_once $moduleForm;
_unloader('form_helper', HELPER);

# File helper (unloadable from /inc/autoload.php)
if( $moduleFile = _readyloader('file_helper') ) require_once $moduleFile;
_unloader('file_helper', HELPER);

# Global Authentication Object
$_auth = ($moduleAuth) ? auth_get() : NULL;

# Check security prerequisite
security_prerequisite();

$module = NULL;
foreach($lc_autoload as $file){
	if($module = _readyloader($file)) require_once $module;
}
unset($module);
unset($file);