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
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\Router;

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
    die('Enable module rewrite in your server and add AllowOverride All.');
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
# path to thirdparty/ folder
define('THIRD_PARTY', ROOT . 'third-party' . _DS_);
# path to vendor/ folder
define('VENDOR', ROOT . 'vendor' . _DS_);
# path to business/ folder
define('BUSINESS', ROOT . 'business' . _DS_);
# path to files/ folder
define('FILE', ROOT . 'files' . _DS_);
# path to files/ folder
define('LOG', FILE . 'logs' . _DS_);
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
require LIB . 'lc.php';
# System configuration variables
require INC . 'config.php';
# Load environment settings
__envLoader();

$baseUrl = _baseUrlWithProtocol();
if ($baseUrl) {
    # path to the web root
    define('WEB_ROOT', $baseUrl . '/');
    # path to the web app root
    define('WEB_APP_ROOT', WEB_ROOT . APP_DIR . '/');
    # path to the home page
    define('HOME', WEB_ROOT);
} else {
    # accessing from command line
    # path to the web root
    define('WEB_ROOT', '/');
    # path to the web app root
    define('WEB_APP_ROOT', 'app/');
    # path to the home page
    define('HOME', '/');
}

# System constants
require INC . 'constants.php';
require APP_ROOT . 'inc' . _DS_ . 'constants.php';
if ($file = _i('inc' . _DS_ . 'constants.php', false)) {
    require_once $file;
}

# Utility helpers (required)
__autoloadHelper(array('utility'));

# Autoload all system files by directory
_autoloadDir(CLASSES);
_autoloadDir(CLASSES . 'console');
_autoloadDir(LIB . 'commands');

if (__dbLoadable()) {
    # DB configuration & DB helper (required)
    _app('db', new \LucidFrame\Core\Database());
}

if (file_exists(INC . 'autoload.php')) {
    require INC . 'autoload.php';
}

# Site-specific configuration variables
require APP_ROOT . 'inc' . _DS_ . 'site.config.php';
if ($file = _i('inc' . _DS_ . 'site.config.php', false)) {
    require_once $file;
}

__autoloadHelper(array('session', 'i18n'));

if (__sessionLoadable()) {
    # Initialize session
    __session_init();
}

# Route helper (required)
require HELPER . 'route_helper.php'; # WEB_ROOT and WEB_APP_ROOT is created in route_helper
# Routing configuration
require INC . 'route.config.php';
require APP_ROOT . 'inc' . _DS_ . 'route.config.php';
if ($file = _i('inc' . _DS_ . 'route.config.php', false)) {
    require_once $file;
}
# Initialize routes
Router::init();

if (defined('WEB_ROOT')) {
    define('CSS', WEB_ROOT . 'assets/css/');
    define('JS', WEB_ROOT . 'assets/js/');
    define('WEB_VENDOR', WEB_ROOT . 'vendor/');
}

__autoloadHelper(array('validation', 'auth', 'pager', 'security', 'form', 'file'));

# Load translations
__i18n_load();

# Initialize validation
__validation_init();

# Initialize global authentication object
_app('auth', auth_get());

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
_app('page', router());

# Autoload all app files by directory
if (PHP_SAPI === 'cli') {
    _autoloadDir(APP_ROOT . 'cmd');
}
_autoloadDir(APP_ROOT . 'entity'); // @deprecated Use /services instead
_autoloadDir(APP_ROOT . 'middleware');
_autoloadDir(APP_ROOT . 'services');

# Initialize view object
_app('view', new \LucidFrame\Core\View());
