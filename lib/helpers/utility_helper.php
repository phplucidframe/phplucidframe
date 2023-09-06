<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for general purpose functions.
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

use LucidFrame\Console\Command;
use LucidFrame\Console\Console;
use LucidFrame\Console\ConsoleTable;
use LucidFrame\Core\Middleware;
use LucidFrame\Core\Pager;
use LucidFrame\File\AsynFileUploader;
use LucidFrame\File\File;
use LucidFrame\Core\SchemaManager;
use LucidFrame\Core\App;

/**
 * Set/Get a global variable/object
 * @param string $name The name of the variable/object
 * @param mixed $value The value or the variable or object
 * @return mixed The value stored globally
 */
function _app($name, $value = null)
{
    if (count(func_get_args()) == 2) {
        return App::$$name = $value;
    } else {
        return App::$$name;
    }
}

/**
 * Returns the current PHPLucidFrame version
 * @return string
 */
function _version()
{
    $findIn = array(LIB, ROOT);
    foreach ($findIn as $dir) {
        $versionFile = $dir . 'VERSION';
        if (is_file($versionFile) && file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
    }

    return 'Unknown';
}

/**
 * @internal
 * @ignore
 *
 * ob_start callback function to output buffer
 * It also adds the following to <html>
 *
 * - a class for IE "ie ieX"
 * - lang="xx" for multi-lingual site
 * - itemscope and itemtype attribute
 *
 * Hook to implement `__flush()` at app/helpers/utility_helper.php
 *
 * @param string $buffer Contents of the output buffer.
 * @param int $phase Bitmask of PHP_OUTPUT_HANDLER_* constants.
 *
 *    PHP_OUTPUT_HANDLER_CLEANABLE
 *    PHP_OUTPUT_HANDLER_FLUSHABLE
 *    PHP_OUTPUT_HANDLER_REMOVABLE
 *
 * @return string
 * @see php.net/ob_start
 */
function _flush($buffer, $phase)
{
    if (function_exists('__flush')) {
        $buffer = __flush($buffer, $phase); # Run the hook if any
    } else {
        $posHtml = stripos($buffer, '<html');
        $posHead = stripos($buffer, '<head');

        $beforeHtmlTag = substr($buffer, 0, $posHtml);
        $afterHtmlTag = substr($buffer, $posHead);
        $htmlTag = trim(str_ireplace($beforeHtmlTag, '', substr($buffer, 0, $posHead)));

        if (trim($htmlTag)) {
            $htmlTag = trim(ltrim($htmlTag, '<html.<HTML'), '>. ');
            $attributes = array();
            $attrList = explode(' ', $htmlTag);
            foreach ($attrList as $list) {
                $attr = explode('=', $list);
                $attr[0] = trim($attr[0]);
                if (count($attr) == 2) {
                    $attr[1] = trim($attr[1], '".\'');
                }
                $attributes[$attr[0]] = $attr;
            }

            $IE = false;
            $IEVersion = '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
                $IE = true;
                if (preg_match('/(MSIE|rv)\s(\d+)/i', $userAgent, $m)) {
                    $IEVersion = 'ie' . $m[2];
                }
            }

            if (array_key_exists('class', $attributes)) {
                # if there is class attribute provided
                if ($IE) {
                    $attributes['class'][1] .= ' ie ' . $IEVersion;
                }
                if (_multilingual()) {
                    $attributes['class'][1] .= ' ' . _lang();
                }
            } else {
                # if there is not class attributes provided
                if ($IE || _multilingual()) {
                    $value = array();
                    if ($IE) {
                        $value[] = 'ie ' . $IEVersion; # ie class
                    }
                    if (_multilingual()) {
                        $value[] = _lang(); # lang class
                    }
                    if (count($value)) {
                        $attributes['class'] = array('class', implode(' ', $value));
                    }
                }
            }

            if (_multilingual()) {
                # lang attributes
                if (!array_key_exists('lang', $attributes)) {
                    # if there is no lang attribute provided
                    $attributes['lang'] = array('lang', _lang());
                }
            }

            if (!array_key_exists('itemscope', $attributes)) {
                $attributes['itemscope'] = array('itemscope');
            }

            if (!array_key_exists('itemtype', $attributes)) {
                # if there is no itemtype attribute provided
                # default to "WebPage"
                $attributes['itemtype'] = array('itemtype', "http://schema.org/WebPage");
            }

            ksort($attributes);
            $html = '<html';
            foreach ($attributes as $key => $value) {
                $html .= ' '.$key;
                if (isset($value[1])) {
                    # some attribute may not have value, such as itemscope
                    $html .= '="' . $value[1] . '"';
                }
            }
            $html .= '>' . "\r\n";
            $buffer = $beforeHtmlTag . $html . $afterHtmlTag;
        }
    }

    # compress the output
    $buffer = _minifyHTML($buffer);

    $posDoc = stripos($buffer, '<!DOCTYPE');

    return substr($buffer, $posDoc);
}

/**
 * Minify and compress the given HTML according to the configuration `$lc_minifyHTML`
 * @param  string $html HTML to be compressed or minified
 * @return string The compressed or minifed HTML
 */
function _minifyHTML($html)
{
    if (_cfg('minifyHTML')) {
        # 1. strip whitespaces after tags, except space
        # 2. strip whitespaces before tags, except space
        # 3. shorten multiple whitespace sequences
        return preg_replace(array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s'), array('>', '<', '\\1'), $html);
    }

    return $html;
}

/**
 * Get a full path directory
 * @param  string $name The short code for directory
 * @return string Full path directory
 */
function _dir($name)
{
    switch($name) {
        case 'inc':
            return ROOT . 'inc' . _DS_;
        case 'db':
            return ROOT . 'db' . _DS_;
        case 'lib':
            return ROOT . 'lib' . _DS_;
        case 'helper':
            return ROOT . 'lib' . _DS_ . 'helpers' . _DS_;
        case 'class':
            return ROOT . 'lib' . _DS_ . 'classes' . _DS_;
        case 'i18n':
            return ROOT . 'i18n' . _DS_;
        case 'vendor':
            return ROOT . 'vendor' . _DS_;
        case 'business':
            return ROOT . 'business' . _DS_;
        case 'asset':
            return ROOT . 'assets' . _DS_;
        case 'image':
            return ROOT . 'assets' . _DS_ . 'images' . _DS_;
        case 'file':
            return ROOT . 'files' . _DS_;
        case 'cache':
            return ROOT . 'files' . _DS_ . 'cache' . _DS_;
        case 'test':
            return ROOT . 'tests' . _DS_;
    }

    return ROOT;
}

/**
 * Auto-load a library, script or file
 * @param string $name The file name without extension
 * @param string $path The directory path for the library, script or file; default to helpers/
 * @return void
 */
function _loader($name, $path = HELPER)
{
    global $lc_autoload;

    $path = rtrim($path, _DS_) . _DS_;

    $dir = $path . $name . _DS_;
    if (is_dir($dir)) {
        // include all files from the library
        $files = scandir($dir);
        foreach ($files as $fileName) {
            $dir = rtrim(rtrim($dir, '/'), '\\');
            $file = $dir . _DS_ . $fileName;

            if (!in_array(substr($fileName, -3), array('php', 'inc')) || !is_file($file)) {
                continue;
            }

            if (file_exists($file)) {
                $lc_autoload[] = $file;
            }
        }
    } else {
        // include one file from the library
        $name = rtrim($name, '.php');
        $lc_autoload[] = $path . $name . '.php';
    }

    $lc_autoload = array_unique($lc_autoload);
}

/**
 * Removing a library, script or file from auto-load
 * @param string $name The file name without extension
 * @param string $path The directory path for the library, script or file; default to helpers/
 * @return void
 */
function _unloader($name, $path = HELPER)
{
    global $lc_autoload;

    $file = $path . $name . '.php';
    $key = array_search($file, $lc_autoload);
    if ($key !== false) {
        unset($lc_autoload[$key]);
        $lc_autoload = array_values($lc_autoload);
    }
}

/**
 * @internal
 * @ignore
 *
 * Check a library, script or file is ready to load
 * @param string $name The file name without extension
 * @param string $path The directory path for the library, script or file; default to helpers/
 * @return mixed The file name if it is ready to load, otherwise FALSE
 */
function _readyloader($name, $path = HELPER)
{
    global $lc_autoload;

    if (stripos($name, '.php') === false) {
        $file = $path . $name . '.php';
    } else {
        $file = $name;
    }

    return (array_search($file, $lc_autoload) !== false && is_file($file) && file_exists($file)) ? $file : false;
}

/**
 * Autoload classes from directory
 * @param string $dir Directory path from which all files to be included
 * @param string $scope The sub-site scope/namespace (if it is given, the directory will only be loaded under that scope)
 * @return void
 */
function _autoloadDir($dir, $scope = '')
{
    $scope = trim($scope, '/');

    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $fileName) {
            $dir = rtrim(rtrim($dir, '/'), '\\');
            $file = $dir . _DS_ . $fileName;

            if (!in_array(substr($fileName, -3), array('php', 'inc')) || !is_file($file)) {
                continue;
            }

            if (file_exists($file) && (empty($scope) || $scope == LC_NAMESPACE)) {
                require_once $file;
            }
        }
    }
}

/**
 * Declare global JS variables
 * Hook to implement `__script()` at app/helpers/utility_helper.php
 *
 * @return void
 */
function _script()
{
    $sitewideWarnings = _cfg('sitewideWarnings');
    $sites = _cfg('sites');
    $script = '<script type="text/javascript">';
    $script .= 'var LC = {};';
    if (WEB_ROOT) {
        $script .= 'var WEB_ROOT = "'.WEB_ROOT.'";';
        $script .= 'LC.root = WEB_ROOT;';
    }
    if (WEB_APP_ROOT) {
        $script .= 'var WEB_APP_ROOT = "'.WEB_APP_ROOT.'";';
        $script .= 'LC.appRoot = WEB_ROOT;';
    }
    $script .= 'LC.self = "'._self().'";';
    $script .= 'LC.lang = "'._lang().'";';
    $script .= 'LC.baseURL = "'._cfg('baseURL').'/";';
    $script .= 'LC.route = "'._r().'";';
    $script .= 'LC.cleanRoute = "'._cfg('cleanRoute').'";';
    $script .= 'LC.namespace = "'.LC_NAMESPACE.'";';
    $script .= 'LC.sites = '.(is_array($sites) && count($sites) ? json_encode($sites) : 'false').';';
    $script .= 'LC.sitewideWarnings = '.json_encode($sitewideWarnings).';';
    # run hook
    if (function_exists('__script')) {
        __script();
    }

    # user defined variables
    $jsVars = _cfg('jsVars');
    $script .= 'LC.vars = {};';
    $script .= 'LC.vars.baseDir = "' . _cfg('baseDir') . '";';
    if (count($jsVars)) {
        foreach ($jsVars as $name => $val) {
            if (is_object($val)) {
                $val = (array) $val;
            }

            if (is_array($val)) {
                $script .= 'LC.vars.'.$name.' = '.json_encode($val).';';
            } elseif (is_numeric($val)) {
                $script .= 'LC.vars.'.$name.' = '.$val.';';
            } else {
                $script .= 'LC.vars.'.$name.' = "'.$val.'";';
            }
        }
    }
    $script .= '</script>';
    echo $script;
}

/**
 * Passing values from PHP to Javascript making available to `LC.vars`
 * @param string $name The JS variable name
 * @param mixed $value The value for the JS variable
 */
function _addJsVar($name, $value = '')
{
    global $lc_jsVars;
    $lc_jsVars[$name] = $value;
}

/**
 * JS file include helper
 *
 * @param string $file An absolute file path or just file name.
 *  The file name only will be prepended the folder name js/ and it will be looked in every sub-sites "js" folder
 * @param string $subDir The sub-directory under assets directory, where the file exists
 * @param bool $return [optional] If you would like to capture the output of _js, use the return parameter.
 *  If this parameter is set to true, _js will return its output, instead of printing it (which it does by default).
 * @return boolean Return true if the file found and included, otherwise false
 */
function _js($file, $subDir = '', $return = false)
{
    $version = '?v' . _cfg('assetVersion');

    if (stripos($file, 'http') === 0 || stripos($file, '//') === 0) {
        $html = '<script src="' . $file . '" type="text/javascript"></script>';
        if ($return) {
            return $html;
        }

        echo $html;
        return true;
    }

    if ($subDir) {
        $subDir = trim('assets/' . $subDir, '/') . '/';
    } else {
        $subDir = 'assets/js/';
    }

    $includeFiles = array();
    if ($file == 'jquery.ui' || $file == 'jquery-ui') {
        # jQuery UI
        $file = (stripos($file, '.js') !== false) ? $file : 'jquery-ui.min.js';
        $includeFiles[] = $subDir . 'vendor/jquery-ui/' . $file;
    } elseif ($file == 'jquery') {
        # jQuery
        $file = (stripos($file, '.js') !== false) ? $file : 'jquery.min.js';
        $includeFiles[] = $subDir . 'vendor/jquery/' . $file;
    } else {
        # Other files
        $includeFiles[] = $subDir . $file;
    }

    foreach ($includeFiles as $includeFile) {
        $includeFile = _i($includeFile);
        if (stripos($includeFile, 'http') === 0) {
            if (stripos($includeFile, WEB_APP_ROOT) === 0) {
                $fileWithSystemPath = APP_ROOT . str_replace(WEB_APP_ROOT, '', $includeFile);
            } else {
                $fileWithSystemPath = ROOT . str_replace(WEB_ROOT, '', $includeFile);
            }
            if (file_exists($fileWithSystemPath)) {
                $html = '<script src="' . $includeFile . $version . '" type="text/javascript"></script>';
                if ($return) {
                    return $html;
                }

                echo $html;
                return true;
            }
        }
    }

    return false;
}

/**
 * CSS file include helper
 *
 * @param string $file An absolute file path or file name only.
 *  The file name only will be prepended the folder name css/ and it will be looked in every sub-sites "css" folder
 * @param string $subDir The sub-directory under assets directory, where the file exists
 * @param bool $return [optional] If you would like to capture the output of _js, use the return parameter.
 *  If this parameter is set to true, _js will return its output, instead of printing it (which it does by default).
 * @return boolean Return true if the file found and included, otherwise false
 */
function _css($file, $subDir = '', $return = false)
{
    $version = '?v' . _cfg('assetVersion');

    if (stripos($file, 'http') === 0 || stripos($file, '//') === 0) {
        $html = '<link href="' . $file . '" rel="stylesheet" type="text/css" />';
        if ($return) {
            return $html;
        }

        echo $html;
        return true;
    }

    if ($subDir) {
        $subDir = trim('assets/' . $subDir, '/') . '/';
    } else {
        $subDir = 'assets/css/';
    }

    $includeFiles = array();
    if ($file == 'jquery.ui' || $file == 'jquery-ui') {
        # jQuery UI
        $includeFiles[] = 'assets/js/vendor/jquery-ui/jquery-ui.min.css';
    } else {
        # Other files
        $includeFiles[] = $subDir . $file;
    }

    foreach ($includeFiles as $includeFile) {
        $includeFile = _i($includeFile);
        if (stripos($includeFile, 'http') === 0) {
            if (stripos($includeFile, WEB_APP_ROOT) === 0) {
                $fileWithSystemPath = APP_ROOT . str_replace(WEB_APP_ROOT, '', $includeFile);
            } else {
                $fileWithSystemPath = ROOT . str_replace(WEB_ROOT, '', $includeFile);
            }

            if (file_exists($fileWithSystemPath)) {
                $html = '<link href="' . $includeFile . $version . '" rel="stylesheet" type="text/css" />';
                if ($return) {
                    return $html;
                }

                echo $html;
                return true;
            }
        }
    }

    return false;
}

/**
 * Get the image file name with absolute web path
 *
 * @param string $file An image file name only (no need directory path)
 * @return string The absolute image URL if the file found or empty string if it is not found
 */
function _img($file)
{
    $fileWithPath = 'assets/images/' . $file;
    $fileWithPath = _i($fileWithPath);

    if (empty($fileWithPath)) {
        return '';
    }

    if (stripos($fileWithPath, APP_ROOT) === 0) {
        return WEB_APP_ROOT . str_replace(APP_ROOT, '', $fileWithPath);
    } else {
        return WEB_ROOT . str_replace(ROOT, '', $fileWithPath);
    }
}

if (!function_exists('_image')) {
    /**
     * Display an image fitting into the desired dimension
     * It expects the file existing in one of the directories `./files` (the constant `FILE`)
     * and `./images` (the constant `IMAGE`)
     * This function has dependency on file_helper. If there is no file_helper found,
     * the arguments `$dimension` and `$attributes` will be ignored.
     *
     * @param string $file The image file name with path excluding
     *   the base directory name (FILE or IMAGE) without leading slash.
     * @param string $caption The image caption
     * @param string $dimension The desired dimension in "widthxheight"
     * @param array $attributes The HTML attributes in array like key => value
     *
     * @return void
     */
    function _image($file, $caption = '', $dimension = '0x0', array $attributes = array())
    {
        $directory = array(
            'images' => IMAGE,
            'files' => FILE,
        );
        # find the image in the two directories - ./files and ./images
        foreach ($directory as $dir => $path) {
            $image = $path . $file;
            if (is_file($image) && file_exists($image)) {
                list($width, $height) = getimagesize($image);
                if (strpos($path, 'assets') !== false) {
                    $dir = 'assets/images';
                }
                break;
            }
        }
        if (isset($width) && isset($height)) {
            # if the image is found
            $image = WEB_ROOT . $dir . '/' . $file;
            echo File::img($image, $caption, $width.'x'.$height, $dimension, $attributes);
        } else {
            # if the image is not found
            echo '<div class="image404" align="center">';
            echo function_exists('_t') ? _t('No Image') : 'No Image';
            echo '</div>';
        }
    }
}

if (!function_exists('_pr')) {
    /**
     * Convenience method for `print_r`.
     * Displays information about a variable in a way that's readable by humans.
     * If given a string, integer or float, the value itself will be printed.
     * If given an array, values will be presented in a format that shows keys and elements.
     *
     * @param mixed $input The variable to debug
     * @param boolean $pre TRUE to print using `<pre>`, otherwise FALSE
     *
     * @return void
     */
    function _pr($input, $pre = true)
    {
        if ($pre) {
            echo '<pre>';
        }
        if (is_array($input) || is_object($input)) {
            print_r($input);
        } else {
            if (is_bool($input)) {
                var_dump($input);
            } else {
                echo $input;
            }
            if ($pre == false) {
                echo '<br>';
            }
        }
        if ($pre) {
            echo '</pre>';
        }
    }
}

if (!function_exists('_dpr')) {
    /**
     * Convenience method for `print_r` + `die`.
     * Displays information about a variable in a way that's readable by humans.
     * If given a string, integer or float, the value itself will be printed.
     * If given an array, values will be presented in a format that shows keys and elements.
     *
     * @param mixed $input The variable to debug
     * @param boolean $pre TRUE to print using `<pre>`, otherwise FALSE
     *
     * @return void
     */
    function _dpr($input, $pre = true)
    {
        _pr($input);
        exit;
    }
}

if (!function_exists('_dump')) {
    /**
     * Convenience method for `var_dump`.
     * Dumps information about a variable
     *
     * @param mixed $input mixed The variable to debug
     * @param boolean $pre boolean TRUE to print using `<pre>`, otherwise FALSE
     *
     * @return void
     */
    function _dump($input, $pre = true)
    {
        if ($pre) {
            echo '<pre>';
        }
        var_dump($input);
        if ($pre) {
            echo '</pre>';
        }
    }
}

/**
 * Convenience method to get/set a global variable
 *
 * @param string $key The global variable name
 * @param mixed $value The value to set to the global variable; if it is not given, it is Getter method.
 * @return mixed The value of the global variable
 */
function _g($key, $value = '')
{
    if (empty($key)) {
        return null;
    }

    if (count(func_get_args()) == 2) {
        return __dotNotationToArray($key, 'global', $value);
    } else {
        return __dotNotationToArray($key);
    }
}

/**
 * Convenience method for htmlspecialchars.
 *
 * @param string $string The string being converted
 * @return string The converted string
 */
function _h($string)
{
    if (empty($string)) {
        return $string;
    }

    $string = stripslashes($string);
    $string = htmlspecialchars_decode($string, ENT_QUOTES);

    return htmlspecialchars($string, ENT_QUOTES); # ENT_QUOTES will convert both double and single quotes.
}

/**
 * Get the current site language code
 * @return string The language code
 */
function _lang()
{
    return _cfg('lang');
}

/**
 * Get the language to process
 * Read "lang" from query string; if it is not found, get the default language code
 * Basically, it is useful for admin content management by language
 * Hook to implement `__getLang()` at app/helpers/utility_helper.php
 *
 * @return string The language code
 */
function _getLang()
{
    if (function_exists('__getLang')) {
        return __getLang(); # run the hook if any
    }

    $lang = (_arg('lang')) ? _arg('lang') : _defaultLang();

    return ($lang) ? $lang : _defaultLang();
}

/**
 * Get the default site language code
 * @return string The default site language code
 */
function _defaultLang()
{
    return _cfg('defaultLang');
}

/**
 * Get array of the defined languages
 * @param string|array $excepts The exceptional langauges to exclude
 * @return array|boolean The filtered language array or FALSE for no multi-language
 */
function _langs($excepts = null)
{
    global $lc_languages;

    $langs = array();
    if ($excepts) {
        foreach ($lc_languages as $lcode => $lname) {
            if (is_array($excepts) && in_array($lcode, $excepts)) {
                continue;
            }
            if (is_string($excepts) && $lcode == $excepts) {
                continue;
            }
            $langs[$lcode] = $lname;
        }
    } else {
        $langs = $lc_languages;
    }

    return count($langs) ? $langs : false;
}

/**
 * Get the current site language code by converting dash (URL-friendly) to underscore (db-friendly)
 * @param string $lang The language code (optional - if not provided, the current language code will be used)
 * @return string The language code
 */
function _queryLang($lang = null)
{
    if (!$lang) {
        $lang = _cfg('lang');;
    }

    return str_replace('-', '_', $lang);
}

/**
 * Get the current site language code by converting underscore (db-friendly) to dash (URL-friendly)
 * @param string $lang The language code (optional - if not provided, the current language code will be used)
 * @return string The language code
 */
function _urlLang($lang = null)
{
    if (!$lang) {
        $lang = _cfg('lang');
    }

    return str_replace('_', '-', $lang);
}

/**
 * Get the default site language code by converting dash to underscore
 * @return string The language code
 */
function _defaultQueryLang()
{
    return str_replace('-', '_', _cfg('defaultLang'));
}

/**
 * Get the current site language name of the given language code
 * If the site is multilingual, return empty
 * If no given code, return the language name of the default language code
 *
 * @param string $lang The language code (optional - if not provided,
 *   the default language code from $lc_defaultLang will be used)
 * @return string The language name as per defined in /inc/config.php
 */
function _langName($lang = '')
{
    if (!_multilingual()) {
        return '';
    }

    global $lc_languages;
    $lang = str_replace('_', '-', $lang);

    if (isset($lc_languages[$lang])) {
        return $lc_languages[$lang];
    } else {
        return $lc_languages[_cfg('defaultLang')];
    }
}

/**
 * Get the current site is multi-lingual or not
 * @return boolean
 */
function _multilingual()
{
    if (_cfg('languages')) {
        return (count(_cfg('languages')) > 1) ? true : false;
    } else {
        return false;
    }
}

/**
 * Get the server protocol
 * For example, http, https, ftp, etc.
 *
 * @return string The protocol - http, https, ftp, etc.
 */
function _protocol()
{
    $protocol = current(explode('/', $_SERVER['SERVER_PROTOCOL']));

    return strtolower($protocol);
}

/**
 * Check SSL or not
 *
 * @return boolean TRUE if https otherwise FALSE
 */
function _ssl()
{
    return _cfg('ssl');
}

/**
 * Get the current routing path
 * For example,
 *
 * - example.com/foo/bar would return foo/bar
 * - example.com/en/foo/bar would also return foo/bar
 * - example.com/1/this-is-slug (if accomplished by RewriteRule) would return the underlying physical path
 *
 * @return string The route path starting from the site root
 */
function _r()
{
    return route_path();
}

/**
 * The more realistic function to get the current routing path on the address bar regardless of RewriteRule behind
 * For example,
 *
 * - example.com/foo/bar would return foo/bar
 * - example.com/en/foo/bar would also return foo/bar
 * - example.com/foo/bar?id=1 would also return foo/bar
 * - example.com/1/this-is-slug would return 1/this-is-slug
 *
 * @return string The route path starting from the site root
 */
function _rr()
{
    if (!_isRewriteRule()) {
        return _r();
    }

    $uri = REQUEST_URI;
    if (strpos($uri, '?') !== false) { // exclude query string
        $uri = substr($uri, 0, strpos($uri, '?'));
    }

    return $uri;
}

/**
 * Get the clean routing path without the query string
 * For example, `example.com/post/1/edit` would return `post`
 * @return string The route path starting from the site root
 */
function _cr()
{
    return _cfg('cleanRoute');
}

/**
 * Get the absolute URL path
 * @param string $path     Routing path such as "foo/bar"; null for the current path
 * @param array  $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @param string $lang Language code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 * @return string
 */
function _url($path = null, $queryStr = array(), $lang = '')
{
    return route_url($path, $queryStr, $lang);
}

/**
 * Get the absolute URL path
 * @param array $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @param string $lang Languague code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 * @return string
 */
function _self($queryStr = array(), $lang = '')
{
    return route_url(null, $queryStr, $lang);
}

/**
 * Send HTTP header
 * @param int $status The HTTP status code
 * @param string $message Message along with status code
 * @return void
 */
function _header($status, $message = null)
{
    _g('httpStatusCode', $status);

    if (PHP_SAPI != 'cli' && _cfg('env') != ENV_TEST && __env() != ENV_TEST) {
        header('HTTP/1.1 ' . $status . ($message ? ' ' . $message : ''));
    }
}

/**
 * Header redirect to a specific location
 * @param string $path Routing path such as "foo/bar"; null for the current path
 * @param array $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @param string $lang The Language code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 * @param int $status The HTTP status code
 *   use `_redirect301()` instead; do not provide this for default 302 redirect.
 * @return void
 */
function _redirect($path = null, $queryStr = array(), $lang = '', $status = null)
{
    if (stripos($path, 'http') === 0) {
        if ($status === 301) {
            _header(301, 'Moved Permanently');
        }
        header('Location: ' . $path);
        exit;
    }

    if ($path == 'self') {
        $url = _self(null, $lang);
    } else {
        $url = route_url($path, $queryStr, $lang);
    }

    if ($status === 301) {
        _header(301, 'Moved Permanently');
    }

    header('Location: ' . $url);
    exit;
}

/**
 * Header redirect to a specific location by sending 301 status code
 * @param string $path     Routing path such as "foo/bar"; null for the current path
 * @param array  $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @param string $lang Languague code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 * @return void
 */
function _redirect301($path = null, $queryStr = array(), $lang = '')
{
    _redirect($path, $queryStr, $lang, 301);
}

/**
 * Display 401 page
 * @param string $message The error message
 * @return void
 */
function _page401($message = '')
{
    $message = $message ?: _t('Access Denied');

    if (_isContentType('application/json')) {
        _jsonError($message, '', 401);
    }

    _cfg('layoutMode', true);
    include(INC . 'tpl/401.php');
    exit;
}

/**
 * Display 403 page
 * @param string $message The error message
 * @return void
 */
function _page403($message = '')
{
    $message = $message ?: _t('403 Forbidden');

    if (_isContentType('application/json')) {
        _jsonError($message, '', 403);
    }

    _cfg('layoutMode', true);
    include(INC . 'tpl/403.php');
    exit;
}

/**
 * Display 404 page
 * @param string $message The error message
 * @param string $entity The entity name
 * @return void
 */
function _page404($message = '', $entity = '')
{
    $message = $message ?: _t('404 Not Found');

    if (_isContentType('application/json')) {
        _jsonError($message, $entity, 404);
    }

    _cfg('layoutMode', true);
    include(INC . 'tpl/404.php');
    exit;
}

/**
 * Display error page
 * @param string $message The error message
 * @param int $code The error code
 * @param int $status HTTP status code
 * @return void
 */
function _error($message, $code, $status = 500)
{
    if (_isContentType('application/json')) {
        _json(['error' => $message], $status);
    }

    _cfg('layoutMode', true);
    _g('httpStatusCode', $status);
    $type = __kernelErrorTypes($code);

    _header($status);

    include(INC . 'tpl/exception.php');
    exit;
}

/**
 * Check if the current routing is a particular URL RewriteRule processing or not
 * @return boolean
 */
function _isRewriteRule()
{
    return (strcasecmp(REQUEST_URI, _r()) !== 0) ? true : false;
}

/**
 * Setter for canonical URL if the argument is given and print the canonical link tag if the argument is not given
 * @param string $url The specific URL
 * @return void|string
 */
function _canonical($url = null)
{
    global $lc_canonical;
    if (!is_null($url)) {
        $lc_canonical = $url;
    } else {
        return (_cfg('canonical')) ? _cfg('canonical') : _url();
    }
}

/**
 * Print hreflang for language and regional URLs
 * @return void
 */
function _hreflang()
{
    global $lc_languages;
    if (_multilingual()) {
        foreach ($lc_languages as $hrefLang => $langDesc) {
            if (_canonical() == _url()) {
                $alternate = _url('', null, $hrefLang);
                $xdefault  = _url('', null, false);
            } else {
                $alternate = preg_replace('/\/'._lang().'\b/', '/'.$hrefLang, _canonical());
                $xdefault  = preg_replace('/\/'._lang().'\b/', '', _canonical());
            }
            echo '<link rel="alternate" hreflang="'.$hrefLang.'" href="'.$alternate.'" />'."\n";
        }
        echo '<link rel="alternate" hreflang="x-default" href="'.$xdefault.'" />'."\n";
    }
}

/**
 * Check if the URI has a language code and return it when it matches
 * For example,
 *
 * - /LucidFrame/en/....
 * - /LucidFrame/....
 * - /en/...
 * - /....
 *
 * @return mixed The language code if it has one, otherwise return FALSE
 */
function _getLangInURI()
{
    global $lc_languages;

    if (!isset($_SERVER['REQUEST_URI'])) {
        return false;
    }

    if (!is_array($lc_languages)) {
        $lc_languages = array('en' => 'English');
    }

    $baseURL = trim(_cfg('baseURL'), '/');
    $baseURL = ($baseURL) ? "/$baseURL/" : '/';
    $baseURL = str_replace('/', '\/', $baseURL); // escape literal `/`
    $baseURL = str_replace('.', '\.', $baseURL); // escape literal `.`
    $regex   = '/^('.$baseURL.')\b('.implode('|', array_keys($lc_languages)).'){1}\b(\/?)/i';

    if (preg_match($regex, $_SERVER['REQUEST_URI'], $matches)) {
        return $matches[2];
    }

    return false;
}

/**
 * Validate that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
 *
 * @param string $host The host name
 * @return boolean TRUE if only containing valid characters, or FALSE otherwise.
 */
function _validHost($host)
{
    return preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host);
}

/**
 * Get the page title glued by a separator
 *
 * @param mixed $args multiple arguments or array of arguments
 * @return string The formatted page title
 */
function _title()
{
    global $lc_siteName;
    global $lc_titleSeparator;

    $args = func_get_args();

    if (count($args) == 0) {
        $title = _app('title');
        if (is_array($title)) {
            $args = $title;
        }

        if (is_string($title)) {
            $args = array($title);
        }
    }

    if (count($args) == 0) {
        return $lc_siteName;
    }

    if (count($args) == 1) {
        if (is_array($args[0])) {
            $args = _filterArrayEmpty($args[0]);
            $title = $args;
        } else {
            $title = ($args[0]) ? array($args[0]) : array();
        }
    } else {
        $args = _filterArrayEmpty($args);
        $title = $args;
    }

    $lc_titleSeparator = trim($lc_titleSeparator);
    if ($lc_titleSeparator) {
        $lc_titleSeparator = ' '.$lc_titleSeparator.' ';
    } else {
        $lc_titleSeparator = ' ';
    }

    if (count($title)) {
        $title = implode($lc_titleSeparator, $title);
        if ($lc_siteName) {
            $title .= ' | '.$lc_siteName;
        }
        return $title;
    }

    return $lc_siteName;
}

/**
 * Filters elements of an array which have empty values
 *
 * @param array $input The input array
 * @return array The filtered array
 */
function _filterArrayEmpty($input)
{
    return array_filter($input, '_notEmpty');
}

/**
 * Check the given value is not empty
 *
 * @param string $value The value to be checked
 * @return boolean TRUE if not empty; FALSE if empty
 */
function _notEmpty($value)
{
    return trim($value) !== '';
}

/**
 * Generate breadcrumb by a separator
 *
 * @param mixed $args Array of string arguments or multiple string arguments
 * @return void
 */
function _breadcrumb()
{
    global $lc_breadcrumbSeparator;

    $args = func_get_args();

    if (!$lc_breadcrumbSeparator) {
        $lc_breadcrumbSeparator = '&raquo;';
    }

    if (count($args) == 1 && is_array($args[0])) {
        $args = $args[0];
    }

    echo implode(" {$lc_breadcrumbSeparator} ", $args);
}

/**
 * Shorten a string for the given length
 *
 * @param string  $str    A plain text string to be shortened
 * @param integer $length The character count
 * @param string $trail  To append `...` or not. `null` to not show
 *
 * @return string The shorten text string
 */
function _shorten($str, $length = 50, $trail = '...')
{
    if (empty($str)) {
        return $str;
    }

    $str = strip_tags(trim($str));
    if (strlen($str) <= $length) {
        return $str;
    }

    $short = trim(substr($str, 0, $length));
    $lastSpacePos = strrpos($short, ' ');
    if ($lastSpacePos !== false) {
        $short = substr($short, 0, $lastSpacePos);
    }

    if ($trail) {
        $short = rtrim($short, '.') . $trail;
    }

    return $short;
}

if (!function_exists('_fstr')) {
    /**
     * Format a string
     *
     * @param string|array $value    A text string or array of text strings to be formatted
     * @param string       $glue     The glue string between each element
     * @param string       $lastGlue The glue string between the last two elements
     *
     * @return string The formatted text string
     */
    function _fstr($value, $glue = ', ', $lastGlue = 'and')
    {
        if (empty($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return $value == '' ? _nullFill($value) : nl2br($value);
        } elseif (is_array($value) && sizeof($value) > 1) {
            $last          = array_slice($value, -2, 2);
            $lastImplode   = implode(' '.$lastGlue.' ', $last);
            $first         = array_slice($value, 0, sizeof($value)-2);
            $firstImplode  = implode($glue, $first);

            return $firstImplode ? $firstImplode.$glue.$lastImplode : $lastImplode;
        } else {
            return nl2br($value[0]);
        }
    }
}

if (!function_exists('_fnum')) {
    /**
     * Format a number
     *
     * @param int    $value    A number to be formatted
     * @param int    $decimals The decimal places. Default is 2.
     * @param string $unit     The unit appended to the number (optional)
     *
     * @return string The formatted number
     */
    function _fnum($value, $decimals = 2, $unit = '')
    {
        if ($value === '') {
            return _nullFill($value);
        } elseif (is_numeric($value)) {
            $value = number_format($value, $decimals, '.', ',');

            return $unit ? $value . ' ' . $unit : $value;
        }

        return $value;
    }
}

if (!function_exists('_fnumSmart')) {
    /**
     * Format a number in a smarter way, i.e., decimal places are omitted when necessary.
     * Given the 2 decimal places, the value 5.00 will be shown 5 whereas the value 5.01 will be shown as it is.
     *
     * @param int $value A number to be formatted
     * @param int $decimals The decimal places. Default is 2.
     * @param string $unit The unit appended to the number (optional)
     *
     * @return string The formatted number
     */
    function _fnumSmart($value, $decimals = 2, $unit = '')
    {
        $value = _fnum($value, $decimals, $unit);
        $v = explode('.', $value);
        if ($decimals > 0 && isset($v[1])) {
            if (preg_match('/0{'.$decimals.'}/i', $v[1])) {
                $value = $v[0];
            }
        }

        return $value;
    }
}

if (!function_exists('_fnumReverse')) {
    /**
     * Remove the number formatting (e.g., thousand separator) from the given number
     *
     * @param  mixed $num A number to remove the formatting
     * @return mixed The number
     */
    function _fnumReverse($num)
    {
        return str_replace(',', '', $num);
    }
}

if (!function_exists('_fdate')) {
    /**
     * Format a date
     *
     * @param  string $date   A date to be formatted
     * @param  string $format The date format; The config variable will be used if it is not passed
     * @return string The formatted date
     */
    function _fdate($date = '', $format = '')
    {
        if (!$format) {
            $format = _cfg('dateFormat');
        }

        if (func_num_args() === 0) {
            return date($format);
        }

        if (empty($date)) {
            return '';
        }

        return is_string($date) ? date($format, strtotime($date)) : date($format, $date);
    }
}

if (!function_exists('_fdatetime')) {
    /**
     * Format a date/time
     *
     * @param  string $dateTime  A date/time to be formatted
     * @param  string $format    The date/time format; The config variable will be used if it is not passed
     * @return string The formatted date/time
     */
    function _fdatetime($dateTime = '', $format = '')
    {
        if (!$format) {
            $format = _cfg('dateTimeFormat');
        }

        if (func_num_args() == 0) {
            return date($format);
        }

        if (empty($dateTime)) {
            return '';
        }

        return date($format, strtotime($dateTime));
    }
}

if (!function_exists('_ftimeAgo')) {
    /**
     * Display elapsed time in wording, e.g., 2 hours ago, 1 year ago, etc.
     *
     * @param string|int $time The elapsed time in unix timestamp or date/time string
     * @param string $format The date/time format to show when 4 days passed
     * @return string
     */
    function _ftimeAgo($time, $format = 'M j Y')
    {
        if (empty($time)) {
            return '';
        }

        $now = time();
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        $secElapsed = $now - $time;
        if ($secElapsed <= 60) {
            return _t('just now');
        } elseif ($secElapsed <= 3540) {
            $min = $now - $time;
            $min = round($min/60);
            return _t('%d minutes ago', $min);
        } elseif ($secElapsed <= 3660) {
            return _t('1 hour ago');
        } elseif (date('j-n-y', $now) == date('j-n-y', $time)) {
            return date("g:i a", $time);
        } elseif (date('j-n-y', mktime(0, 0, 0, date('n', $now), date('j', $now)-1, date('Y', $now))) == date('j-n-y', $time)) {
            return _t('yesterday');
        } elseif ($secElapsed <= 345600) {
            return date('l', $time);
        } else {
            return date($format, $time);
        }
    }
}

if (!function_exists('_msg')) {
    /**
     * Print or return the message formatted with HTML
     *
     * @param mixed  $msg     A message string or Array of message strings
     * @param string $class   The CSS class name
     * @param mixed  $return  What is expected to return from this function.
     *  `null` (default) no return and just print it.
     *  `html` return HTML.
     * @param string $display  CSs display property value - block, none, inline-block, etc.
     *
     * @return string The formatted date
     */
    function _msg($msg, $class = 'error', $return = null, $display = null)
    {
        $class = $class ?: 'error';
        $return = strtolower($return);

        $html = '';
        $html .= '<div class="message"';
        if ($display) {
            $html .= ' style="display:' . $display . '"';
        }
        $html .= '>';
        $html .= '<div class="message-'. $class . ' alert alert-' . ($class == 'error' ? 'danger' : $class) . '">';
        if (is_array($msg)) {
            if (count($msg) > 0) {
                $html .= '<ul>';
                foreach ($msg as $m) {
                    if (is_array($msg) && isset($m['message'])) {
                        $html .= '<li>'.$m['message'].'</li>';
                    } else {
                        $html .= '<li>'.$m.'</li>';
                    }
                }
                $html .= '</ul>';
            } else {
                $html = '';
            }
        } else {
            $html .= $msg;
        }

        $html .= '</div></div>';

        if (is_array($msg) && count($msg) == 0) {
            $html = '';
        }

        if ($return == 'html' || $return === true) {
            return $html;
        } else {
            echo $html;
        }

        return '';
    }
}

/**
 * Find the size of the given file.
 *
 * @param string $file   The file name (file must exist)
 * @param int    $digits Number of precisions
 * @param array  $sizes  Array of size units, e.g., array("TB","GB","MB","KB","B"). Default is array("MB","KB","B")
 *
 * @return string|bool The size unit (B, KiB, MiB, GiB, TiB, PiB, EiB, ZiB, YiB) or `FALSE` for non-existence file
 */
function _filesize($file, $digits = 2, $sizes = array("MB","KB","B"))
{
    if (is_file($file)) {
        $filePath = $file;
        if (!realpath($filePath)) {
            $filePath = $_SERVER["DOCUMENT_ROOT"].$filePath;
        }
        $fileSize = filesize($filePath);
        $total = count($sizes);
        while ($total-- && $fileSize > 1024) {
            $fileSize /= 1024;
        }

        return round($fileSize, $digits)." ".$sizes[$total];
    }

    return false;
}

if (!function_exists('_randomCode')) {
    /**
     * Generate a random string from the given array of letters.
     * @param  int    $length   The length of required random string
     * @param  array  $letters  Array of letters from which randomized string is derived from.
     *   Default is a to z and 0 to 9.
     * @param  string $prefix   Prefix to the generated string
     * @return string The random string of required length
     */
    function _randomCode($length = 5, $letters = array(), $prefix = '')
    {
        # Letters & Numbers for default
        if (sizeof($letters) == 0) {
            $letters = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        }

        shuffle($letters); # Shuffle letters
        $randArr = array_splice($letters, 0, $length);

        return $prefix . implode('', $randArr);
    }
}

if (!function_exists('_slug')) {
    /**
     * Generate a slug of human-readable keywords
     *
     * @param string $string     Text to slug
     * @param string $table      Table name to check in. If it is empty, no check in the table
     * @param array  $condition  Condition to append table check-in, e.g, `array('fieldName !=' => value)`
     *
     * @return string The generated slug
     */
    function _slug($string, $table = '', array $condition = array())
    {
        $specChars = array(
            '`','~','!','@','#','$','%','\^','&',
            '*','(',')','=','+','{','}','[',']',
            ':',';',"'",'"','<','>','\\','|','?','/',','
        );
        $table  = db_table($table);
        $slug   = strtolower(trim($string));
        $slug   = trim($slug, '-');
        # clear special characters
        $slug   = preg_replace('/(&amp;|&quot;|&#039;|&lt;|&gt;)/i', '', $slug);
        $slug   = str_replace($specChars, '-', $slug);
        $slug   = str_replace(array(' ', '.'), '-', $slug);
        $slug   = trim($slug, '-');

        $condition = array_merge(
            array('slug' => $slug),
            $condition
        );

        while (true && $table) {
            $count = db_count($table)->where($condition)->fetch();
            if ($count == 0) {
                break;
            }

            $segments = explode('-', $slug);
            if (sizeof($segments) > 1 && is_numeric($segments[sizeof($segments)-1])) {
                $index = array_pop($segments);
                $index++;
            } else {
                $index = 1;
            }

            $segments[] = $index;
            $slug = implode('-', $segments);
        }

        $slug = preg_replace('/[\-]+/', '-', $slug);

        return trim($slug, '-');
    }
}

/**
 * Return the SQL date (Y-m-d) from the given date and format
 *
 * @param string $date        Date to convert
 * @param string $givenFormat Format for the given date
 * @param string $separator   Separator in the date. Default is dash "-"
 *
 * @return string|null the SQL date string if the given date is valid, otherwise null
 */
function _sqlDate($date, $givenFormat = 'dmy', $separator = '-')
{
    if (empty($date)) {
        return null;
    }

    $dt      = explode($separator, $date);
    $format  = str_split($givenFormat);
    $ft      = array_flip($format);

    $y = $dt[$ft['y']];
    $m = $dt[$ft['m']];
    $d = $dt[$ft['d']];

    return checkdate($m, $d, $y) ? $y . '-' . $m .'-'. $d : null;
}

/**
 * Encrypts the given text using security salt if mcrypt extension is enabled, otherwise using md5()
 *
 * @param  string $text Text to be encrypted
 * @return string The encrypted text
 */
function _encrypt($text)
{
    $secret = _cfg('securitySecret');
    if (!$secret || !function_exists('openssl_encrypt')) {
        return md5($text);
    }

    $method = _cipher();
    $ivlen  = openssl_cipher_iv_length($method);
    $iv     = openssl_random_pseudo_bytes($ivlen);

    $textRaw = openssl_encrypt($text, $method, $secret, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $textRaw, $secret, true);

    return base64_encode($iv . $hmac . $textRaw );
}

/**
 * Decrypts the given text using security salt if mcrypt extension is enabled,
 * otherwise return the original encrypted string
 *
 * @param   string $encryptedText Text to be decrypted
 * @return  string The decrypted text
 */
function _decrypt($encryptedText)
{
    $secret = _cfg('securitySecret');
    if (!$secret || !function_exists('openssl_decrypt')) {
        return $encryptedText;
    }

    $method  = _cipher();
    $sha2len = 32;
    $ivlen   = openssl_cipher_iv_length($method);
    $text    = base64_decode($encryptedText);
    $iv      = substr($text, 0, $ivlen);

    $rawText = substr($text, $ivlen + $sha2len);
    $plainText = openssl_decrypt($rawText, $method, $secret, OPENSSL_RAW_DATA, $iv);

    $hmac = substr($text, $ivlen, $sha2len);
    $mac = hash_hmac('sha256', $rawText, $secret, true);
    if (hash_equals($hmac, $mac)) {
        return $plainText;
    }

    return $text;
}

/**
 * Get current cipher method
 * @return string
 */
function _cipher()
{
    $method = _cfg('cipher');
    if (!in_array($method, openssl_get_cipher_methods())) {
        $method = 'AES-256-CBC';
    }

    return $method;
}

/**
 * Simple quick helper function for <meta> tag attribute values
 *
 * @param  string $key   The <meta> tag name
 * @param  string $value If the value is empty, this is a Getter function; otherwise Setter function
 * @return void|mixed
 */
function _meta($key, $value = '')
{
    global $_meta;
    $value = trim($value);
    if (empty($value)) {
        return (isset($_meta[$key])) ? $_meta[$key] : '';
    } else {
        if (in_array($key, array('description', 'og:description', 'twitter:description', 'gp:description'))) {
            $value = trim(substr($value, 0, 200));
        }
        $_meta[$key] = $value;
    }
}

/**
 * Print SEO meta tags
 * @return void
 */
function _metaSeoTags()
{
    if (_meta('description')) {
        _cfg('metaDescription', _meta('description'));
    }

    if (_meta('keywords')) {
        _cfg('metaKeywords', _meta('keywords'));
    }

    $tags = array();
    $tags['description'] = _cfg('metaDescription');
    $tags['keywords']    = _cfg('metaKeywords');

    $tags['og']                 = array();
    $tags['og']['title']        = _meta('og:title') ? _meta('og:title') : _cfg('siteName');
    $tags['og']['url']          = _meta('og:url') ? _meta('og:url') : _url();
    $tags['og']['type']         = _meta('og:type') ? _meta('og:type') : 'website';
    $tags['og']['image']        = _meta('og:image') ? _meta('og:image') : _img('logo-social.jpg');
    $tags['og']['description']  = _meta('og:description') ? _meta('og:description') : _cfg('metaDescription');
    $tags['og']['site_name']    = _meta('og:site_name') ? _meta('og:site_name') : _cfg('siteName');

    $tags['twitter']            = array();
    $tags['twitter']['card']    = _meta('twitter:card') ? _meta('twitter:card') : 'summary';
    $tags['twitter']['site']    = _meta('twitter:site') ? '@'._meta('twitter:site') : '@'._cfg('siteDomain');
    $tags['twitter']['title']   = _meta('twitter:title') ? _meta('twitter:title') : _cfg('siteName');
    $tags['twitter']['description'] = _meta('twitter:description') ? _meta('twitter:description') : _cfg('metaDescription');
    $tags['twitter']['image']   = _meta('twitter:image') ? _meta('twitter:image') : _img('logo-social.jpg');

    if (function_exists('__metaSeoTags')) {
        echo __metaSeoTags($tags);
    } else {
        echo "\n";
        foreach ($tags as $name => $tag) {
            if ($name == 'og') {
                foreach ($tag as $key => $content) {
                    echo '<meta property="og:' . $key . '" content="' . $content . '" />'."\n";
                }
            } elseif ($name == 'twitter') {
                foreach ($tag as $key => $content) {
                    echo '<meta name="twitter:' . $key . '" content="' . $content . '" />'."\n";
                }
            } else {
                echo '<meta name="' . $name . '" content="' . $tag . '" />'."\n";
            }
        }
    }
}

/**
 * Simple mail helper function
 *  The formatting of the email addresses must comply with RFC 2822. Some examples are:
 *
 *  - user@example.com
 *  - user@example.com, anotheruser@example.com
 *  - User <user@example.com>
 *  - User <user@example.com>, Another User <anotheruser@example.com>*
 *
 * @param string  $from     The sender of the mail
 * @param string  $to       The receiver or receivers of the mail
 * @param string  $subject  Subject of the email to be sent.
 * @param string  $message  Message to be sent
 * @param string  $cc       The CC receiver or receivers of the mail
 * @param string  $bcc      The Bcc receiver or receivers of the mail
 *
 * @return boolean Returns TRUE if the mail was successfully accepted for delivery, FALSE otherwise
 */
function _mail($from, $to, $subject = '', $message = '', $cc = '', $bcc = '')
{
    $charset = mb_detect_encoding($message);
    $message = nl2br(stripslashes($message));

    $EEOL = PHP_EOL; //"\n";
    $headers  = 'From: ' . $from . $EEOL;
    $headers .= 'MIME-Version: 1.0' . $EEOL;
    $headers .= 'Content-type: text/html; charset=' . $charset  . $EEOL;
    $headers .= 'Reply-To: ' . $from . $EEOL;
    $headers .= 'Return-Path:'.$from . $EEOL;
    if ($cc) {
        $headers .= 'Cc: ' . $cc . $EEOL;
    }
    if ($bcc) {
        $headers .= 'Bcc: ' . $bcc . $EEOL;
    }
    $headers .= 'X-Mailer: PHP';

    return mail($to, $subject, $message, $headers);
}
/**
 * Get translation strings from the POST array
 * and prepare to insert or update into the table according to the specified fields
 *
 * @param array  $post   The POST array
 * @param array  $fields The array of field name and input name mapping, e.g., array('fieldName' => 'inputName')
 * @param string $lang   The language code to fetch (if it is not provided, all languages will be fetched)
 *
 * @return array The data array
 */
function _postTranslationStrings($post, $fields, $lang = null)
{
    global $lc_languages;

    $data = array();
    foreach ($fields as $key => $name) {
        if ($lang) {
            $lcode = _queryLang($lang);
            if (isset($post[$name.'_'.$lcode])) {
                $data[$key.'_'.$lcode] = $post[$name.'_'.$lcode];
            }
        } else {
            if (isset($post[$name])) {
                $data[$key.'_'._defaultLang()] = $post[$name];
            }
            foreach ($lc_languages as $lcode => $lname) {
                $lcode = _queryLang($lcode);
                if (isset($post[$name.'_'.$lcode])) {
                    $data[$key.'_'.$lcode] = $post[$name.'_'.$lcode];
                }
            }
        }
    }

    return $data;
}

/**
 * Get translation strings from the query result
 * and return the array of `$i18n[fieldName][lang] = $value`
 *
 * @param object|array $data   The query result
 * @param array|string $fields The array of field names to get data, e.g.,
 *   'fieldName' or `array('fieldName1', 'fieldName2')`
 * @param string       $lang   The language code to fetch (if it is not provided, all languages will be fetched)
 *
 * @return array|object        The array or object of translation strings
 */
function _getTranslationStrings($data, $fields, $lang = null)
{
    global $lc_languages;

    $isObject = is_object($data);
    $data = (array) $data;

    if (is_string($fields)) {
        $fields = array($fields);
    }

    foreach ($fields as $name) {
        if ($lang) {
            $lcode = _queryLang($lang);
            if (isset($data[$name.'_'.$lcode]) && $data[$name.'_'.$lcode]) {
                $data[$name.'_i18n'] = $data[$name.'_'.$lcode];
            } else {
                $data[$name.'_i18n'] = $data[$name];
            }
        } else {
            foreach ($lc_languages as $lcode => $lname) {
                $lcode = _queryLang($lcode);
                if (isset($data[$name.'_'.$lcode])) {
                    $data[$name.'_i18n'][$lcode] = $data[$name.'_'.$lcode];
                }
            }
        }
    }

    if ($isObject) {
        $data = (object) $data;
    }

    return $data;
}

/**
 * Detect the current page visited by a search bot or crawler
 * @return boolean `TRUE` if it is a bot's visit; otherwise `FALSE`
 * @see http://www.useragentstring.com/pages/useragentstring.php?typ=Crawler
 */
function _isBot()
{
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (empty($userAgent)) {
        return false;
    }

    $bots = array(
        'Googlebot',
        'Slurp',
        'msnbot',
        'bingbot',
        'yahoo',
        'search.msn.com',
        'Baidu',
        'baiduspider',
        'Yandex',
        'nutch',
        'FAST',
        'Sosospider',
        'Exabot',
        'sogou',
        'bot',
        'crawler',
        'spider',
        'Feedfetcher-Google',
        'ASPSeek',
        'simpy',
        'ips-agent',
        'Libwww-perl',
        'ask jeeves',
        'fastcrawler',
        'infoseek',
        'lycos',
        'mediapartners-google',
        'CRAZYWEBCRAWLER',
        'adsbot-google',
        'curious george',
        'ia_archiver',
        'MJ12bot',
        'Uptimebot',
        'Dataprovider.com',
        'Go-http-client',
        'Barkrowler',
        'panscient.com',
        'Symfony BrowserKit',
        'Apache-HttpClient',
        'serpstatbot',
        'BLEXBot',
        'DotBot',
        'AhrefsBot',
    );
    foreach ($bots as $bot) {
        if (false !== strpos(strtolower($userAgent), strtolower($bot))) {
            return true;
        }
    }

    return false;
}

/**
 * Write output
 * @since  PHPLucidFrame v 1.14.0
 * @param  string $text The text to output
 * @param  [mixed $args [, mixed ...]] Arguments to the text
 * @return void
 */
function _write($text = '')
{
    $args = func_get_args();
    $text = array_shift($args);
    if ($text) {
        echo vsprintf($text, $args);
    }
}

/**
 * Write output with line feed (\n)
 * @since  PHPLucidFrame v 1.11.0
 * @param  string $text The text to output
 * @param  [mixed $args [, mixed ...]] Arguments to the text
 * @return void
 */
function _writeln($text = '')
{
    $args = func_get_args();
    $text = array_shift($args);
    if ($text) {
        echo vsprintf($text, $args);
    }

    echo "\n";
}

/**
 * Write spacer for indentation purpose
 * @since  PHPLucidFrame v 1.11.0
 * @param  int  $width No. of spaces
 * @return void|string
 */
function _indent($width = 2)
{
    return str_repeat(' ', $width);
}

/**
 * Simple helper to create an instance of LucidFrame\Console\Command
 * @since  PHPLucidFrame v 1.11.0
 * @param  string $command The command name
 * @return object LucidFrame\Console\Command
 */
function _consoleCommand($command)
{
    return new Command($command);
}

/**
 * Simple helper to create an instance of LucidFrame\Console\ConsoleTable
 * @since  PHPLucidFrame v 1.12.0
 * @return object LucidFrame\Console\ConsoleTable
 */
function _consoleTable()
{
    return new ConsoleTable();
}

/**
 * Simple helper to get all registered commands
 * @since  PHPLucidFrame v 1.12.0
 * @return array The array of command LucidFrame\Console\Command
 */
function _consoleCommands()
{
    return Console::getCommands();
}

/**
 * Simple helper to create Pager object
 * @since   PHPLucidFrame v 1.11.0
 * @param   string $pageQueryStr The customized page query string name, default is page
 * @return  object LucidFrame\Core\Pager
 */
function _pager($pageQueryStr = '')
{
    return new Pager($pageQueryStr);
}

/**
 * Simple helper to create File object
 * @since   PHPLucidFrame v 1.11.0
 * @param   string $fileName (optinal) Path to the file
 * @return  object LucidFrame\File\File
 */
function _fileHelper($fileName = '')
{
    return new File($fileName);
}

/**
 * Simple helper to create AsynFileUploader object
 * @since   PHPLucidFrame v 1.11.0
 * @param   string/array anonymous The input file name or The array of property/value pairs
 * @return  object LucidFrame\File\AsynFileUploader
 */
function _asynFileUploader()
{
    if (func_num_args()) {
        return new AsynFileUploader(func_get_arg(0));
    } else {
        return new AsynFileUploader();
    }
}

/**
 * Simple helper to register a middleware
 * @since PHPLucidFrame v 2.0.0
 * @param Closure $closure Anonymous function
 * @param string $event before (default) or after
 * @return object LucidFrame\Core\Middleware
 */
function _middleware(\Closure $closure, $event = 'before')
{
    $middleware = new Middleware();

    return $middleware->register($closure, $event);
}

/**
 * Get view file
 * @return string The view file with absolute path
 */
function _view()
{
    if (_cfg('view')) {
        $viewName = 'view_'._cfg('view');
    } elseif (_g('view')) {
        $viewName = 'view_'._g('view');
    } else {
        $viewName = 'view';
    }

    return _i(_ds(_cr(), $viewName.'.php'));
}

/**
 * Return directories and file names glued by directory separator
 * @return string
 */
function _ds()
{
    return implode(_DS_, func_get_args());
}

/**
 * Check if the request is an AJAX request
 * @return boolean TRUE if the request is XmlHttpRequest, otherwise FALSE
 */
function _isAjax()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    }

    return false;
}

/**
 * Check if HTTP request method is POST and has request data
 * @return bool
 */
function _isHttpPost()
{
    return _isRequestMethod('POST') && count($_POST);
}

/**
 * Header sent as text/json
 * @param array|object $data Array/Object of data to be encoded as JSON
 * @param int $status HTTP status code, default to 200
 * @return void
 */
function _json($data = [], $status = 200)
{
    if (_isRequestMethod('OPTIONS')) {
        _header(200);
        exit;
    }

    _header($status);

    header('Content-Type: application/json');
    if ($status != 204) {
        echo json_encode($data);
    }

    Middleware::runAfter();
    exit;
}

/**
 * Response error as JSON
 * @param string|array $message The error message or array of error message
 * @param string $field The field name
 * @param int $status HTTP status code
 * @return void
 */
function _jsonError($message, $field = '', $status = 400)
{
    $errors = [];
    if (is_array($message)) {
        $errors = $message;
    } else {
        $errors[] = [
            'field' => $field,
            'message' => $message,
        ];

    }

    _json(['error' => $errors], $status);
}

/**
 * Fetch all HTTP request headers
 * @return array An associative array of all the HTTP headers in the current request, or FALSE on failure.
 */
function _requestHeaders()
{
    if (function_exists('getallheaders')) {
        return getallheaders();
    }

    if (function_exists('apache_request_headers')) {
        return apache_request_headers();
    }

    $headers = array();
    foreach ($_SERVER as $name => $value) {
        $name = strtolower($name);
        if (substr($name, 0, 5) == 'http_') {
            $headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($name, 5))))] = $value;
        } elseif ($name == 'content_type') {
            $headers['Content-Type'] = $value;
        } elseif ($name == 'content_length') {
            $headers['Content-Length'] = $value;
        }
    }

    return $headers;
}

/**
 * Fetch a HTTP request header by name
 * @param  string $name The HTTP header name
 * @return string The HTTP header value from the request
 */
function _requestHeader($name)
{
    $headers = _requestHeaders();
    if (!is_array($headers)) {
        return null;
    }

    $headers = array_change_key_case($headers);
    $name = strtolower($name);

    if (isset($headers[$name])) {
        return $headers[$name];
    }

    return null;
}

/**
 * Get request method
 * @return string|null
 */
function _requestMethod()
{
    if (isset($_SERVER['REQUEST_METHOD'])) {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    return null;
}

/**
 * Check if the request method is the given one
 * @param string $method The request method
 * @return bool
 */
function _isRequestMethod($method)
{
    return _requestMethod() == strtoupper($method);
}

/**
 * Convert form data into js variable
 * @param string $name The form name or scope name
 * @param array $data Array of data
 */
function _addFormData($name, array $data)
{
    echo '<script>LC.Form.formData["' . $name . '"] = ' . json_encode($data) . ';</script>';
}

/**
 * Return a value or empty sign
 * Hook to implement `__nullFill()` at app/helpers/utility_helper.php
 * @param mixed $value The value to check and show
 * @return string
 */
function _nullFill($value)
{
    if (function_exists('__nullFill')) {
        return __nullFill($value);
    }

    return $value ?: '<span class="null-fill">-</span>';
}

/**
 * Get default entity object from the schema
 * @param string $table The mapped table name without prefix
 * @param string|null $dbNamespace The current db namespace
 * @return object The empty stdClass object with field names as properties
 */
function _entity($table, $dbNamespace = null)
{
    if (!$dbNamespace) {
        $dbNamespace = _cfg('defaultDbSource');
    }

    $schema = _schema($dbNamespace, true);

    $entity = array();
    if ($schema && isset($schema[$table])) {
        $options = array_merge(SchemaManager::$relationships, array('options'));
        foreach ($schema[$table] as $field => $def) {
            if (in_array($field, $options)) {
                continue;
            }

            if (isset($def['autoinc'])) {
                $value = 0;
            } else {
                $value = isset($def['null']) ? null : '';
                if (isset($def['default'])) {
                    $value = $def['default'];
                }
            }

            if ($field == 'created' || $field == 'updated') {
                $value = date('Y-m-i H:i:s');
            }

            if ($def['type'] == 'array' || $def['type'] == 'json') {
                $value = array();
            }

            $entity[$field] = $value;
        }
    }

    return (object) $entity;
}

/**
 * Add CSS file to be included in head section
 * @param string $file An absolute file path or file name only.
 *  The file name only will be prepended the folder name css/ and it will be looked in every sub-sites "css" folder
 */
function _addHeadStyle($file)
{
    $view = _app('view');
    $view->addHeadStyle($file);
}

/**
 * Add JS file to be included in head section
 * @param string $file An absolute file path or file name only.
 *  The file name only will be prepended the folder name js/ and it will be looked in every sub-sites "js" folder
 */
function _addHeadScript($file)
{
    $view = _app('view');
    $view->addHeadScript($file);
}

/**
 * Convert English number to Myanmar number
 * @param string $num
 * @return string
 */
function _en2myNum($num)
{
    $digits = array(
        '/0/' => '၀',
        '/1/' => '၁',
        '/2/' => '၂',
        '/3/' => '၃',
        '/4/' => '၄',
        '/5/' => '၅',
        '/6/' => '၆',
        '/7/' => '၇',
        '/8/' => '၈',
        '/9/' => '၉',
    );

    return preg_replace(array_keys($digits), array_values($digits), $num);
}

/**
 * Check if array is associative or sequential
 * @param array $arr The array to be checked
 * @return bool
 */
function _arrayAssoc(array $arr)
{
    if (empty($arr)) {
        return false;
    }

    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Check if HTTP header has the given content type
 * @param string $type HTTP header content type
 * @return bool
 */
function _isContentType($type)
{
    return isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == $type;
}
