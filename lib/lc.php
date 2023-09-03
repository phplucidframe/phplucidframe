<?php
/**
 * This file is part of the PHPLucidFrame library.
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

use LucidFrame\Core\SchemaManager;

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
 * @ignore
 * HTTP status code
 */
$lc_httpStatusCode = 200;
/**
 * @internal
 * @ignore
 * Site-wide warnings to be shown
 */
$lc_sitewideWarnings = array();
/**
 * @internal
 * @ignore
 * Auto load/unload configuration
 */
$lc_autoload = array();
/**
 * @internal
 * @ignore
 * Namespace which will later be available as a constant LC_NAMESPACE
 */
$lc_namespace = '';
/**
 * @internal
 * @ignore
 * The clean route without query string or without file name
 */
$lc_cleanRoute = '';
/**
 * @internal
 * @ignore
 * The global javascript variables that will be rentered in the <head> section
 */
$lc_jsVars = array();
/**
 * @internal
 * @ignore
 * The canonical URL for the current page
 */
$lc_canonical = '';
/**
 * @internal
 * @ignore
 * The array of configurations from parameter.env.inc
 */
$lc_envParameters = null;
/**
 * @internal
 * @ignore
 * Meta information for the current page
 */
$_meta = array();
/**
 * @internal
 * @ignore
 * @type array It contains the built and executed queries through out the script execution
 */
global $db_builtQueries;
$db_builtQueries = array();
$db_printQuery = false;

/***************************/
/* Internal functions here */
/***************************/

/**
 * @internal
 * @ignore
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

    if (PHP_SAPI !== 'cli') {
        register_shutdown_function('__kernelShutdownHandler');
    }
}

/**
 * @internal
 * @ignore
 * Dot notation access to multi-dimensional array
 * Get the values by providing dot notation string key
 * Set the values by providing dot notation string key
 *
 * @param string  $key       The string separated by dot (period)
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

    $type = count(func_get_args()) > 2 ? 'setter' : 'getter';
    $keys = explode(".", $key);
    # extract the first key
    $firstKey = array_shift($keys);
    # extract the last key
    $lastKey = end($keys);
    # No. of keys exclusive of the first key
    $count = count($keys); # more than 0 if there is at least one dot
    $justOneLevelKey = ($count === 0);

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

    $current = null;
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
        return $count ? (isset($current[$lastKey]) ? $current[$lastKey] : null)  : $current;
    }
    return null;
}

/**
 * @internal
 * @ignore
 * Load running environment settings
 * Initialize the site language(s), error reporting
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
    global $lc_timeZone;
    global $lc_memoryLimit;
    global $lc_maxExecTime;

    /**
     * Don't escape quotes when reading files from the database, disk, etc.
     */
    ini_set('magic_quotes_runtime', '0');
    /**
     * Set the maximum amount of memory in bytes that a script is allowed to allocate.
     * This helps prevent poorly written scripts for eating up all available memory on a server
     */
    ini_set('memory_limit', $lc_memoryLimit);
    /**
     * Set the maximum time in seconds a script is allowed to run before it is terminated by the parser.
     * This helps prevent poorly written scripts from tying up the server. The default setting is 30.
     */
    ini_set('max_execution_time', $lc_maxExecTime);

    /**
     * Default Time Zone
     */
    date_default_timezone_set($lc_timeZone);

    $lc_env = strtolower($lc_env);
    if (!in_array($lc_env, __envList())) {
        $lc_env = ENV_PROD;
    }
    if ($lc_env == ENV_PROD) {
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

    if (empty($lc_languages) || !is_array($lc_languages)) {
        $lc_languages = array('en' => 'English');
    }

    $REQUEST_URI = $_SERVER['REQUEST_URI'];

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
 * @ignore
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
 * @internal
 * @ignore
 * Read and get the environment setting from .lcenv
 * @return string
 */
function __env()
{
    $defaultEnv = ENV_DEV;

    $oldFile = ROOT . '.env';
    if (is_file($oldFile) && file_exists($oldFile)) {
        $defaultEnv = trim(file_get_contents($oldFile));
        if (in_array($defaultEnv, __envList())) {
            unlink($oldFile);
        }
    }

    $file = ROOT . FILE_ENV;
    if (!(is_file($file) && file_exists($file))) {
        file_put_contents($file, $defaultEnv);
    }

    $env = trim(file_get_contents($file));
    if (!in_array($env, __envList())) {
        $env = ENV_PROD;
    }

    return $env;
}

/**
 * @internal
 * @ignore
 * Return list of env name array
 * @return array
 */
function __envList()
{
    return array(ENV_PROD, ENV_STAGING, ENV_DEV, ENV_TEST, 'dev', 'prod');
}

/**
 * @internal
 * @ignore
 *
 * Custom error handler
 *
 * @param  integer $code    Error code
 * @param  string  $message Error message
 * @param  string  $file    File name
 * @param  integer $line    Error line number
 * @return boolean
 */
function __kernelErrorHandler($code, $message, $file, $line)
{
    if (!(error_reporting() & $code)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }

    $type = __kernelErrorTypes($code);
    $trace = array_reverse(debug_backtrace());

    $status = _g('httpStatusCode');
    if (empty($status) || $status == 200) {
        $status = 500;
        _g('httpStatusCode', $status);
    }

    _header($status);

    include( _i('inc/tpl/exception.php') );
    exit;
}

/**
 * @internal
 * @ignore
 *
 * Custom shutdown handler
 */
function __kernelShutdownHandler()
{
    $error = error_get_last();

    if (is_array($error)) {
        __kernelErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

/**
 * @internal
 * @ignore
 *
 * Get friendly error type by code
 * @param  integer $code Error code
 * @return string The friendly error type
 */
function __kernelErrorTypes($code)
{
    switch($code) {
        case E_ERROR: # 1
            return 'E_ERROR: Fatal error';

        case E_WARNING: # 2
            return 'E_WARNING: Warning';

        case E_PARSE: # 4
            return 'E_PARSE: Parse error';

        case E_NOTICE: # 8
            return 'E_NOTICE: Notice';

        case E_CORE_ERROR: # 16
            return 'E_CORE_ERROR: Fatal error';

        case E_CORE_WARNING: # 32
            return 'E_CORE_WARNING: Warning';

        case E_COMPILE_ERROR: # 64
            return 'E_COMPILE_ERROR: Fatal error';

        case E_COMPILE_WARNING: # 128
            return 'E_COMPILE_WARNING: Warning';

        case E_USER_ERROR: # 256
            return 'E_USER_ERROR: User-generated error';

        case E_USER_WARNING: # 512
            return 'E_USER_WARNING: User-generated warning';

        case E_USER_NOTICE: # 1024
            return 'E_USER_NOTICE: User-generated notice';

        case E_STRICT: # 2048
            return 'E_STRICT: Information';

        case E_RECOVERABLE_ERROR: # 4096
            return 'E_RECOVERABLE_ERROR: Catchable fatal error';

        case E_DEPRECATED: # 8192
            return 'E_DEPRECATED: Deprecated warning';

        case E_USER_DEPRECATED: # 16384
            return 'E_USER_DEPRECATED: User-generated deprecated warning';
    }

    return 'E_ERROR, Error';
}

/**
 * Autoload helper
 * @param string|array $modules The module file name
 */
function __autoloadHelper($modules)
{
    $modules = is_array($modules) ? $modules : array($modules);
    $helperDirs = _baseDirs('helpers');

    foreach ($modules as $helper) {
        foreach ($helperDirs as $dir) {
            $moduleFile = $dir . $helper . '_helper.php';
            if (is_file($moduleFile) && file_exists($moduleFile)) {
                include($moduleFile);
            }
        }
    }
}

/**
 * @internal
 * @ignore
 *
 * Check if db is loadable (skip db initialization upon some CLI commands execution)
 * @return bool
 */
function __dbLoadable()
{
    global $argv;

    return !(PHP_SAPI == 'cli' && $argv[0] === 'lucidframe' && in_array($argv[1], ['list', 'env', 'secret:generate']));
}

/*************************/
/* Public functions here */
/*************************/

/**
 * Get schema definition file
 * @param  string $dbNamespace The namespace for the database
 * @param  boolean TRUE to look for the file in /db/build/; FALSE in /db/
 *  `TRUE` to look for the file in such priority
 *      1. /db/build/schema.{namespace}.lock
 *      2. /db/build/schema.lock (only for default)
 *      3. /db/schema.{namespace}.php
 *      4. /db/schema.php (only for default)
 *
 *  `FALSE` to look in this priority
 *      1. /db/schema.{namespace}.php
 *      2. /db/schema.php (only for default)
 *
 * @return mixed
 *  array   The schema definition
 *  null    Incorrect schema definition
 *  boolean False when the file doesn't exist
 */
function _schema($dbNamespace = 'default', $cache = false)
{
    $files = array();
    if ($cache) {
        $files[] = SchemaManager::getSchemaLockFileName($dbNamespace);
        $files[] = SchemaManager::getSchemaLockFileName();
    }

    $files[] = DB."schema.{$dbNamespace}.php";
    $files[] = DB."schema.php";

    foreach ($files as $f) {
        if (is_file($f) && file_exists($f)) {
            $file = $f;
            if (pathinfo($file, PATHINFO_EXTENSION) == 'lock') {
                return unserialize(file_get_contents($file));
            } else {
                $schema = include($file);
                return is_array($schema) ? $schema : null;
            }
        }
    }

    return false;
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

    $ext = strtolower(substr($file, strrpos($file, '.')+1)); # get the file extension
    if (in_array($ext, array('js', 'css'))) {
        $appRoot = WEB_APP_ROOT;
        $root = WEB_ROOT;
    } else {
        $appRoot = APP_ROOT;
        $root = ROOT;
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
    }

    $folders = array();
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
                APP_ROOT.$lc_sites[LC_NAMESPACE]._DS_ => $appRoot . $lc_sites[LC_NAMESPACE] . _DS_,
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
            $libHelper = true;
        } else {
            $fileWithPath = $key . $file;
            $libHelper = false;
        }

        if (is_file($fileWithPath) && file_exists($fileWithPath)) {
            if ($libHelper === false) {
                $fileWithPath = $value . $file;
            }

            return $fileWithPath;
        }

        if ($recursive == false) {
            break;
        }
    }

    if (strstr($_SERVER['PHP_SELF'], APP_DIR)) {
        if ($recursive == true) {
            if ($root === ROOT && substr($file, 0, 7) === 'helpers') {
                $file = LIB . $file;
            } else {
                $file = $root . $file;
            }
        } else {
            $file = $root . $file;
        }

        if (is_file($file) && file_exists($file)) {
            return $file;
        }
    }

    return '';
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
function _cfg($key, $value = '')
{
    if (strrpos($key, 'lc_') === 0) {
        $key = substr($key, 3);
    }

    $key = 'lc_' . $key;

    return count(func_get_args()) == 2 ? __dotNotationToArray($key, 'global', $value) : __dotNotationToArray($key, 'global');
}

/**
 * Convenience method to get the value of the array config variable by its key
 *
 * @param string $name The config array variable name without prefix
 * @param string $key The key of the config array of which value to be retrieved
 * @return mixed|string|null The value of a single column of the config array variable
 */
function _cfgOption($name, $key)
{
    $config = _cfg($name);

    return isset($config[$key]) ? $config[$key] : null;
}

/**
 * Get the parameter value by name defined in `/inc/parameter/(development|production|staging|test).php`
 * @param  string $name The parameter name defined as key in `/inc/parameter/(development|production|staging|test).php`.
 *  The file development, production, staging or test will be determined according to the value from `.lcenv`.
 *  If `$name` is `env` (by default), it returns the current environment setting from `.lcenv`.
 * @return mixed The value defined `/inc/parameter/(development|production|staging|test).php`
 */
function _p($name = 'env')
{
    if ($name == 'env') {
        return __env();
    }

    global $argv;

    if (PHP_SAPI == 'cli' && $argv[0] === 'lucidframe') {
        # keep the current environment when `php lucidframe` is run
        $env = _cfg('env');
    } elseif (PHP_SAPI == 'cli' || stripos($_SERVER['REQUEST_URI'], 'tests/') !== false) {
        # force change to "test" environment when run `php tests/tests.php` from CLI
        # or when run `/tests/tests.php` from browser
        $env = 'test';
        _cfg('env', $env);
    } else {
        # neither CLI nor test
        $env = _cfg('env');
    }

    if (!in_array($env, __envList())) {
        die(sprintf('Wrong environment configuration. Use "%s" or "%s" or "%s" or "%s".', ENV_DEV, ENV_STAGING, ENV_PROD, ENV_TEST));
    }

    $param = include(INC . 'parameter/' . $env . '.php');

    return __dotNotationToArray($name, $param);
}

/**
 * Get the parameter value by name defined in `/inc/parameter/parameter.env.inc`
 * @param  string $name The parameter name in dot annotation format such as `prod.db.default.database`
 * @param  mixed $default The default value if the parameter name doesn't exist
 * @return mixed The value defined in `/inc/parameter/parameter.env.inc`
 */
function _env($name, $default = '')
{
    global $lc_envParameters;

    if ($lc_envParameters === null) {
        $file = INC . 'parameter/parameter.env.inc';
        if (is_file($file) && file_exists($file)) {
            $lc_envParameters = include($file);
        } else {
            return $default;
        }
    }

    $value = __dotNotationToArray($name, $lc_envParameters);

    return $value ?: $default;
}

/**
 * Get base URL with protocol
 * @return bool|string
 */
function _baseUrlWithProtocol()
{
    if (isset($_SERVER['SERVER_PROTOCOL'])) {
        $baseUrl = _cfg('baseURL');
        $protocol = _cfg('ssl') ? 'https' : 'http';
        $base = strtolower($protocol) . '://' . $_SERVER['HTTP_HOST'];
        if ($baseUrl) {
            $base .= '/' . $baseUrl;
        }

        return $base;
    }

    return false;
}

/**
 * Get base directory list by priority
 * @param string $subDir The sub-directory name
 * @return string[]
 */
function _baseDirs($subDir = '')
{
    $folders = array();

    $namespace = LC_NAMESPACE;
    if (!empty($_GET['lc_namespace'])) {
        $namespace = $_GET['lc_namespace'];
    }

    $sites = _cfg('sites');
    if (count($sites) && array_key_exists($namespace, $sites)) {
        $folders[] = rtrim(APP_ROOT . $sites[$namespace] . _DS_ . $subDir, _DS_) . _DS_;
    }

    $folders[] = rtrim(APP_ROOT . $subDir, _DS_) . _DS_;
    $folders[] = rtrim(LIB . $subDir, _DS_) . _DS_;

    return $folders;
}


__prerequisite();
