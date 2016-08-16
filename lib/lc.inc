<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

/**
 * @ignore Flag for image resize to the fitted dimension to the given dimension
 */
define('FILE_RESIZE_BOTH', 'both');
/**
 * @ignore Flag for image resize to the given height, but width is aspect ratio of the height
 */
define('FILE_RESIZE_HEIGHT', 'height');
/**
 * @ignore Flag for image resize to the given width, but height is aspect ratio of the width
 */
define('FILE_RESIZE_WIDTH', 'width');
/**
 * @ignore File upload error flag for the failure of `move_uploaded_file()`
 */
define('FILE_UPLOAD_ERR_MOVE', 100);
/**
 * @ignore File upload error flag for the failure of image creation of GD functions
 */
define('FILE_UPLOAD_ERR_IMAGE_CREATE', 101);
/**
 * Query fetch types
 */
define('LC_FETCH_ASSOC', 1);
define('LC_FETCH_ARRAY', 2);
define('LC_FETCH_OBJECT', 3);
/**
 * Console command option types
 */
define('LC_CONSOLE_OPTION_REQUIRED', 4);
define('LC_CONSOLE_OPTION_OPTIONAL', 5);
define('LC_CONSOLE_OPTION_NOVALUE', 6);

/**
 * @internal
 * Site-wide warnings to be shown
 */
$lc_sitewideWarnings = array();
/**
 * @internal
 * Auto load/unload configuration
 */
$lc_autoload = array();
/**
 * @internal
 * Namespace which will later be available as a constant LC_NAMESPACE
 */
$lc_namespace = '';
/**
 * @internal
 * The clean route without query string or without file name
 */
$lc_cleanRoute = '';
/**
 * @internal
 * The global javascript variables that will be rentered in the <head> section
 */
$lc_jsVars = array();
/**
 * @internal
 * The canonical URL for the current page
 */
$lc_canonical = '';
/**
 * @internal
 * Meta information for the current page
 */
$_meta = array();
/**
 * @internal
 * @type array It contains the built and executed queries through out the script execuation
 */
global $db_builtQueries;
$db_builtQueries = array();
$db_printQuery = false;

/**
 * @internal
 * Prerequisite check
 */
function __prerequisite()
{
    if (version_compare(phpversion(), '5.3.0', '<')) {
        die('PHPLucidFrame requires at least PHP 5.3.0. Your PHP installation is ' . phpversion() . '.');
    }

    /**
     * Check config.php
     */
    if (!file_exists(INC . 'config.php')) {
        copy(INC . 'config.default.php', INC . 'config.php');
    }
    /**
     * Check site.config.php
     */
    if (!file_exists(INC . 'site.config.php')) {
        copy(INC . 'site.config.default.php', INC . 'site.config.php');
    }
}
/**
 * File include helper
 * Find files under the default directories inc/, js/, css/ according to the defined site directories $lc_sites
 *
 * @param $file    string File name with directory path
 * @param $recursive boolean True to find the file name until the site root
 *
 * @return string File name with absolute path if it is found, otherwise return an empty string
 */
function _i($file, $recursive = true)
{
    global $lc_baseURL;
    global $lc_sites;
    global $lc_languages;
    global $lc_namespace;

    $ext = strtolower(substr($file, strrpos($file, '.')+1)); # get the file extension
    if (in_array($ext, array('js', 'css'))) {
        $appRoot = WEB_APP_ROOT;
        $root = WEB_ROOT;
        $clientFile = true;
    } else {
        $appRoot = APP_ROOT;
        $root = ROOT;
        $clientFile = false;
    }

    if (!is_array($lc_languages)) {
        $lc_languages = array('en' => 'English');
    }

    $REQUEST_URI = $_SERVER['REQUEST_URI'];

    $requestURI = trim(ltrim($REQUEST_URI, '/'.$lc_baseURL)); # /base-dir/path/to/sub/dir to path/to/sub/dir
    $request    = explode('/', $requestURI);

    $needle = $request[0];
    # Clean lang code in URL
    if (array_key_exists($needle, $lc_languages)) {
        array_shift($request);
        $needle = count($request) ? $request[0] : '';
    }

    if (LC_NAMESPACE == '') {
    # Find in APP_ROOT -> ROOT
        $folders = array(
            APP_ROOT => $appRoot,
            ROOT => $root
        );

    }

    if (isset($lc_sites) && is_array($lc_sites) && count($lc_sites)) {
        if (array_key_exists(LC_NAMESPACE, $lc_sites)) {
        # Find in SUB-DIR -> APP_ROOT -> ROOT
            $folders = array(
                APP_ROOT.$lc_sites[LC_NAMESPACE].'/' => $appRoot . $lc_sites[LC_NAMESPACE] . '/',
                APP_ROOT => $appRoot,
                ROOT => $root
            );
        }
    }

    # $key is for file_exists()
    # $value is for include() or <script> or <link>
    foreach ($folders as $key => $value) {
        if ($key === ROOT && substr($file, 0, 7) === 'helpers') {
            $fileWithPath = LIB . $file;
        } else {
            $fileWithPath = $key . $file;
        }

        if (is_file($fileWithPath) && file_exists($fileWithPath)) {
            $fileWithPath = $value . $file;
            return $fileWithPath;
        }

        if ($recursive == false) {
            break;
        }
    }

    if (strstr($_SERVER['PHP_SELF'], APP_DIR)) {
        if (is_file($file) && file_exists($file)) {
            return $file;
        }

        if ($recursive == true) {
            if ($root === ROOT && substr($file, 0, 7) === 'helpers') {
                return LIB . $file;
            } else {
                return $root . $file;
            }
        }
    } else {
        return '';
    }
}
/**
 * Get the host name or server name
 */
function _host()
{
    return !isset($_SERVER['HTTP_HOST']) ? isset($_SERVER['SERVER_NAME'])
        ? $_SERVER['SERVER_NAME'] : php_uname('n') : $_SERVER['HTTP_HOST'];
}
/**
 * Convenience method to get/set a config variable without global declaration within the calling function
 *
 * @param string $key The config variable name without prefix
 * @param mixed $value The value to set to the config variable; if it is omitted, it is Getter method.
 * @return mixed The value of the config variable
 */
function _cfg($key = '', $value = '')
{
    if (empty($key)) {
        return null;
    }
    if (strrpos($key, 'lc_') === 0) {
        $key = substr($key, 3);
    }
    $key = 'lc_' . $key;
    return (count(func_get_args()) == 2) ? __dotNotationToArray($key, 'global', $value) : __dotNotationToArray($key, 'global');
}
/**
 * Get the parameter value by name defined in /inc/parameter/*
 * @param  string $name The parameter name
 * @return mixed
 */
function _p($name)
{
    global $argv;

    if (PHP_SAPI == 'cli' && $argv[0] === 'lucidframe') {
        # keep the current environment when `php lucidframe` is run
        $env = _cfg('env');
    } elseif (PHP_SAPI == 'cli' || stripos($_SERVER['REQUEST_URI'], 'tests/') !== false) {
        # force change to "test" environment when run `php tests/test.php` from CLI
        # or when run `/tests/tests.php` from browser
        $env = 'test';
        _cfg('env', $env);
    } else {
        # neither CLI nor test
        $env = _cfg('env');
    }

    if (!in_array($env, array('development', 'production', 'test'))) {
        die('Wrong environment configuration. Use "development" or "production" or "test".');
    }

    $param = include(INC.'parameter/'.$env.'.php');

    return __dotNotationToArray($name, $param);
}
/**
 * @internal
 * Dot notation access to multi-dimensional array
 * Get the values by providing dot notation string key
 * Set the values by providing dot notation string key
 *
 * @param string  $key       The string separated by dot (peroid)
 * @param string  $scope     What scope in which the values will be stored - global or session
 * @param mixed   $value     The optional value to set or updated
 * @param boolean $serialize The value is to be serialized or not
 *
 * @return mixed The value assigned
 */
function __dotNotationToArray($key, $scope = 'global', $value = '', $serialize = false)
{
    if (empty($key)) {
        return null;
    }
    if (!in_array($scope, array('global', 'session')) && !is_array($scope)) {
        return null;
    }
    if (is_array($scope)) {
        $input = &$scope;
    }

    $type = (count(func_get_args()) > 2) ? 'setter' : 'getter';
    $keys = explode(".", $key);
    # extract the first key
    $firstKey = array_shift($keys);
    # extract the last key
    $lastKey = end($keys);
    # No. of keys exclusive of the first key
    $count = count($keys); # more than 0 if there is at least one dot
    $justOneLevelKey = ($count === 0) ? true : false;

    if ($type == 'getter' && $justOneLevelKey) {
        # just one-level key
        if ($scope == 'session') {
            $firstKey = S_PREFIX . $firstKey;
            return (array_key_exists($firstKey, $_SESSION)) ? $_SESSION[$firstKey] : null;
        } elseif ($scope == 'global') {
            return (array_key_exists($firstKey, $GLOBALS)) ? $GLOBALS[$firstKey] : null;
        } elseif (is_array($scope) && isset($input)) {
            return (array_key_exists($firstKey, $input)) ? $input[$firstKey] : null;
        }
    }

    if ($scope == 'session') {
        $firstKey = S_PREFIX . $firstKey;
        if (!array_key_exists($firstKey, $_SESSION)) {
            $_SESSION[$firstKey] = null;
        }
        $current = &$_SESSION[$firstKey];
    } elseif ($scope == 'global') {
        if (!array_key_exists($firstKey, $GLOBALS)) {
            $GLOBALS[$firstKey] = null;
        }
        $current = &$GLOBALS[$firstKey];
    } elseif (is_array($scope) && isset($input)) {
        if (!array_key_exists($firstKey, $input)) {
            $input[$firstKey] = null;
        }
        $current = &$input[$firstKey];
    }

    $theLastHasValue = false;
    if (($type == 'setter' && $count) || ($type == 'getter' && $count > 1)) {
        # this will be skipped if no dot notation
        foreach ($keys as $k) {
            if ($k == $lastKey && isset($current[$lastKey])) {
                if ($type === 'getter') {
                    return $current[$lastKey];
                }

                $theLastHasValue = true;
                if ($scope != 'session') {
                    # if the last-key has the value of not-array, create array and push the later values.
                    $current[$lastKey] = is_array($current[$k]) ? $current[$k] : array($current[$k]);
                }
                break;
            }
            if ($count && !isset($current[$k]) && !is_array($current)) {
                $current = array($k => null);
            }
            $current = &$current[$k];
        }
    }
    # Set the values if it is setter
    if ($type == 'setter') {
        if (is_array($current) && $theLastHasValue) {
            # when $theLastHasValue, dot notation is given and it is array
            $current[$lastKey] = ($serialize) ? serialize($value) : $value;
        } else {
            $current = ($serialize) ? serialize($value) : $value;
        }
        return $current;
    } elseif ($type == 'getter') {
        # Get the values if it is getter
        return ($count) ? (isset($current[$lastKey]) ? $current[$lastKey] : null)  : $current;
    }
    return null;
}
/**
 * @internal
 * Load running environment settings
 * Initalize the site language(s), error reporting
 * Define two constants - REQUEST_URI and LC_NAMESPACE
 *
 * @return void
 */
function __envLoader()
{
    global $lc_languages;
    global $lc_baseURL;
    global $lc_sites;
    global $lc_env;
    global $lc_debugLevel;
    global $lc_minifyHTML;

    $lc_env = strtolower($lc_env);
    if (!in_array($lc_env, array('development', 'production', 'test'))) {
        $lc_env = 'production';
    }
    if ($lc_env == 'production') {
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
    } else {
        $lc_minifyHTML = false;
        switch($lc_debugLevel) {
            case 1:
                error_reporting(E_ERROR | E_PARSE);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 0);
                break;
            case 2:
                error_reporting(E_ERROR | E_PARSE | E_NOTICE | E_WARNING);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                break;
            case 3:
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                break;
            default:
                error_reporting($lc_debugLevel);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
        }
    }

    if (!isset($lc_languages) || (isset($lc_languages) && !is_array($lc_languages))) {
        $lc_languages = array('en' => 'English');
    }

    $REQUEST_URI = $_SERVER['REQUEST_URI'];

    $requestURI = trim(ltrim($REQUEST_URI, '/'.$lc_baseURL)); # /base-dir/path/to/sub/dir to path/to/sub/dir
    $requestURI = substr($REQUEST_URI, strpos($REQUEST_URI, '/'.$lc_baseURL) + strlen($lc_baseURL) + 1);
    $requestURI = ltrim($requestURI, '/');
    $request    = explode('/', $requestURI);
    $lc_namespace = $request[0];

    # Clean lang code in URL
    if (array_key_exists($lc_namespace, $lc_languages)) {
        array_shift($request);
        $requestURI = ltrim(ltrim($requestURI, $lc_namespace), '/'); # clean the language code from URI
        $lc_namespace = count($request) ? $request[0] : '';
    }

    if (!(isset($lc_sites) && is_array($lc_sites) && array_key_exists($lc_namespace, $lc_sites))) {
        $lc_namespace = '';
    }

    # REQUEST_URI excluding the base URL
    define('REQUEST_URI', trim($requestURI, '/'));
    # Namespace according to the site directories
    define('LC_NAMESPACE', $lc_namespace);

    unset($requestURI);
    unset($request);
}
/**
 * @internal
 * Read .secret and return the hash string which is the value for $lc_securitySecret
 * @param  string $file The optional file path
 * @return string
 */
function __secret($file = null)
{
    if ($file !== null && is_file($file) && file_exists($file)) {
        return trim(file_get_contents($file));
    }

    $file = INC . '.secret';
    return (is_file($file) && file_exists($file)) ? trim(file_get_contents($file)) : '';
}

/**
 * Get schema definition file
 * @param  string $dbNamespace The namespace for the database
 * @param  boolean TRUE to look for the file in /db/build/; FALSE in /db/
 *  `TRUE` to look for the file in such priority
 *      1. /db/build/schema.{namespace}.inc
 *      2. /db/build/schema.inc (only for default)
 *      3. /db/schema.{namespace}.php
 *      4. /db/schema.php (only for default)
 *
 *  `FALSE` to look in this priority
 *      1. /db/schema.{namespace}.php
 *      2. /db/schema.php (only for default)
 *
 * @return mixed
 *  array   The schema defination
 *  null    Incorrect schema defination
 *  boolean False when the file doesn't exist
 */
function _schema($dbNamespace = 'default', $cache = false)
{
    if ($cache) {
        $path = DB.'build'._DS_;
        $ext = 'inc';
    } else {
        $path = DB;
        $ext = 'php';
    }

    if ($dbNamespace === 'default') {
        $files = array("{$path}schema.{$dbNamespace}.{$ext}", "{$path}schema.{$ext}");
    } else {
        $files = array("{$path}schema.{$dbNamespace}.{$ext}");
    }

    if ($cache) {
        $files[] = DB."schema.{$dbNamespace}.php";
        $files[] = DB."schema.php";
    }
    $files = array_unique($files);

    $file = false;
    foreach ($files as $f) {
        if (is_file($f) && file_exists($f)) {
            $file = $f;
            break;
        }
    }

    if ($file) {
        $schema = include($file);
        return is_array($schema) ? $schema : null;
    } else {
        return false;
    }
}

__prerequisite();
