<?php
/**
 * This file is part of the PHPLucidFrame library.
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
 * Check config.php
 */
if (!file_exists(INC . 'config.php')) {
    die('Copy "/inc/config.default.php" and rename to "/inc/config.php".');
}
/**
 * Check site.config.php
 */
if (!file_exists(INC . 'site.config.php')) {
    die('Copy "/inc/site.config.default.php" and rename to "/inc/site.config.php".');
}
/**
 * File include helper
 * Find files under the default directories inc/, js/, css/ according to the defined site directories $lc_sites
 *
 * @param $file	string File name with directory path
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
        $fileWithPath = $key . $file;
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
            return $root . $file;
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
    if (!in_array($lc_env, array('development', 'production'))) {
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
 * @return string
 */
function __secret()
{
    $file = INC . '.secret';
    return (is_file($file) && file_exists($file)) ? trim(file_get_contents($file)) : '';
}
