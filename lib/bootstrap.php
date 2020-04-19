<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * This file is loaded automatically by the app/index.php
 * This file loads/creates any application wide configuration settings, such as
 * Database, Session, loading additional configuration files.
 * This file includes the resources that provide global functions/constants that your application uses.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = __FILE__;
}

if (!defined('APP_DIR')) {
    define('APP_DIR', 'app');
}
define('_DS_', DIRECTORY_SEPARATOR);

if (!defined('APP_ROOT')) {
    $APP_ROOT = rtrim(getcwd(), _DS_) . _DS_;
    if (isset($_GET['bootstrap']) && strrpos($APP_ROOT, APP_DIR . _DS_) === false) {
        $APP_ROOT .= APP_DIR . _DS_;
    }
    define('APP_ROOT', $APP_ROOT); # including trailing slash
}

if (!defined('ROOT')) {
    $regex = '/\\' . _DS_ . APP_DIR . '\b$/';
    $ROOT = preg_replace($regex, '', rtrim(APP_ROOT, _DS_));
    if (strrpos($ROOT, _DS_) != strlen($ROOT) - 1) {
        $ROOT .= _DS_; # include trailing slash if not
    }
    define('ROOT', $ROOT);
}

if (strcasecmp(APP_ROOT, ROOT) === 0) {
    die('Enable mod_rewrite in your server and "AllowOverride All" from .htaccess');
}

# Constants for environment
define('ENV_DEV', 'development');
define('ENV_STAGING', 'staging');
define('ENV_PROD', 'production');
define('ENV_TEST', 'test');

define('FILE_ENV', '.lcenv');

# path to inc/ folder
define('INC', ROOT . 'inc' . _DS_);
# path to db/ folder
define('DB', ROOT . 'db' . _DS_);
# path to lib/ folder
define('LIB', ROOT . 'lib' . _DS_);
# path to lib/helpers/ folder
define('HELPER', LIB . 'helpers' . _DS_);
# path to lib/classes/ folder
define('CLASSES', LIB . 'classes' . _DS_);
# path to i18n/ folder
define('I18N', ROOT . 'i18n' . _DS_);
# path to vendor/ folder
define('VENDOR', ROOT . 'vendor' . _DS_);
# path to business/ folder
define('BUSINESS', ROOT . 'business' . _DS_);
# path to files/ folder
define('FILE', ROOT . 'files' . _DS_);
# path to images/ folder
if (is_dir(ROOT . 'assets' . _DS_ . 'images' . _DS_)) {
    define('IMAGE', ROOT . 'assets' . _DS_ . 'images' . _DS_);
} else {
    define('IMAGE', ROOT . 'images' . _DS_);
}
# path to assets/ folder
define('ASSETS', ROOT . 'assets' . _DS_);
# path to files/tests folder
define('TEST', ROOT . 'tests' . _DS_);
define('TEST_DIR', ROOT . 'tests' . _DS_);
# path to files/cache folder
define('CACHE', FILE . 'cache' . _DS_);

# System prerequisites
require LIB . 'lc.inc';
# System configuration variables
require INC . 'config.php';
# Load environment settings
__envLoader();

# Utility helpers (required)
if ($file = _i('helpers' . _DS_ . 'utility_helper.php', false)) {
    include $file;
}
require HELPER . 'utility_helper.php';

# Autoload all system files by directory
_autoloadDir(CLASSES);
_autoloadDir(CLASSES . 'console');
_autoloadDir(LIB . 'commands');

# DB configuration & DB helper (required)
$_DB = new \LucidFrame\Core\Database();

_loader('session_helper', HELPER);
_loader('i18n_helper', HELPER);
_loader('validation_helper', HELPER);
_loader('auth_helper', HELPER);
_loader('pager_helper', HELPER);
_loader('form_helper', HELPER);
_loader('file_helper', HELPER);

if (file_exists(INC . 'autoload.php')) {
    require INC . 'autoload.php';
}

# Session helper (unloadable from /inc/autoload.php)
if ($file = _i('helpers' . _DS_ . 'session_helper.php', false)) {
    include $file;
}
if ($moduleSession = _readyloader('session_helper')) {
    require $moduleSession;
    __session_init();
}
_unloader('session_helper', HELPER);

# Translation helper (unloadable from /inc/autoload.php)
if ($moduleI18n = _readyloader('i18n_helper')) {
    require $moduleI18n;
}
_unloader('i18n_helper', HELPER);

# Route helper (required)
require HELPER . 'route_helper.php'; # WEB_ROOT and WEB_APP_ROOT is created in route_helper
# Routing configuration
include INC . 'route.config.php';
__route_init();

define('CSS', WEB_ROOT . 'assets/css/');
define('JS', WEB_ROOT . 'assets/js/');
define('WEB_VENDOR', WEB_ROOT . 'vendor/');

# Load translations
if ($moduleI18n) {
    __i18n_load();
}

# Site-specific configuration variables
require_once APP_ROOT . 'inc' . _DS_ . 'site.config.php';
if ($file = _i('inc' . _DS_ . 'site.config.php', false)) {
    require_once $file;
}

# Validation helper (unloadable from /inc/autoload.php)
if ($file = _i('helpers' . _DS_ . 'validation_helper.php')) {
    include $file;
}

if ($moduleValidation = _readyloader('validation_helper')) {
    if ($moduleValidation != $file) {
        require $moduleValidation;
    }
    __validation_init();
}
_unloader('validation_helper', HELPER);

# Auth helper (unloadable from /inc/autoload.php)
if ($file = _i('helpers' . _DS_ . 'auth_helper.php')) {
    include $file;
}
if ($moduleAuth = _readyloader('auth_helper')) {
    if ($moduleAuth != $file) {
        require $moduleAuth;
    }
}
_unloader('auth_helper', HELPER);

# Pager helper
if ($file = _i('helpers' . _DS_ . 'pager_helper.php')) {
    include $file;
}
if ($modulePager = _readyloader('pager_helper')) {
    if ($modulePager != $file) {
        require $modulePager;
    }
}
_unloader('pager_helper', HELPER);

# Security helper (required)
require HELPER . 'security_helper.php';

# Form helper (unloadable from /inc/autoload.php)
if ($file = _i('helpers' . _DS_ . 'form_helper.php')) {
    include $file;
}
if ($moduleForm = _readyloader('form_helper')) {
    if ($moduleForm != $file) {
        require $moduleForm;
    }
}
_unloader('form_helper', HELPER);

# File helper
if ($file = _i('helpers' . _DS_ . 'file_helper.php')) {
    include $file;
}
if ($moduleFile = _readyloader('file_helper')) {
    if ($moduleFile != $file) {
        require $moduleFile;
    }
}
_unloader('file_helper', HELPER);

# Global Authentication Object
$_auth = ($moduleAuth) ? auth_get() : null;

# Check security prerequisite
security_prerequisite();

$module = null;
foreach ($lc_autoload as $file) {
    if ($module = _readyloader($file)) {
        require $module;
    }
}
unset($module);
unset($file);
unset($commandDirs);
unset($cmdDir);

# Composer Autoloader
$composerAutoloader = VENDOR . 'autoload.php';
if (is_file($composerAutoloader) && file_exists($composerAutoloader)) {
    require $composerAutoloader;
}

# Handling request to a route and map to a page
$_page = router();

# Autoload all app files by directory
_autoloadDir(APP_ROOT . 'cmd');
_autoloadDir(APP_ROOT . 'cmd' . _DS_ . 'classes');
_autoloadDir(APP_ROOT . 'entity');
_autoloadDir(APP_ROOT . 'middleware');
