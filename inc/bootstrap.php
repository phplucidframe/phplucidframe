<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * This file is loaded automatically by the app/index.php
 * This file loads/creates any application wide configuration settings, such as
 * Database, Session, loading additional configuration files.
 * This file includes the resources that provide global functions/constants that your application uses.
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

define('APP_DIR', 'app');
define('_DS_', DIRECTORY_SEPARATOR);

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

if(strcasecmp(APP_ROOT, ROOT) === 0){
	die('Enable mod_rewrite in your server and "AllowOverride All" from .htaccess');
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

# System prerequisites
require_once INC . 'lc.inc';
# System configuration variables
require_once INC . 'config.php';
# Load environment settings
__envLoader();

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

_loader('session_helper', HELPER);
_loader('i18n_helper', HELPER);
_loader('validation_helper', HELPER);
_loader('auth_helper', HELPER);
_loader('pager_helper', HELPER);
_loader('form_helper', HELPER);
_loader('file_helper', HELPER);

if(file_exists(INC.'autoload.php')) require_once INC.'autoload.php';

# Session helper (unloadable from /inc/autoload.php)
if( $file = _i( 'helpers/session_helper.php', false) ) include_once $file;
if( $moduleSession = _readyloader('session_helper') ) require_once $moduleSession;
_unloader('session_helper', HELPER);

# Translation helper (unloadable from /inc/autoload.php)
if( $moduleI18n = _readyloader('i18n_helper') ) require_once $moduleI18n;
_unloader('i18n_helper', HELPER);

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
