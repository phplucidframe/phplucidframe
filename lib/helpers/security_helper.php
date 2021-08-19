<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for input sanitizing, data escaping, CSRF protection and XSS prevention
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

/**
 * @internal
 * @ignore
 *
 * Check the default security secret to be changed
 */
function security_prerequisite()
{
    $defaultSecret = md5('lucidframe');
    $secret = trim(_cfg('securitySecret'));
    if (function_exists('mcrypt_encrypt') && (empty($secret) || strcmp($secret, $defaultSecret) === 0)) {
        $msg = 'To change your own security secret, ';
        $msg .= 'open your terminal or command line, <code class="inline">cd</code> to your project directory, ';
        $msg .= 'then run <code class="inline">php lucidframe secret:generate</span>';
        _cfg('sitewideWarnings', function_exists('_t') ? _t($msg) : $msg);
    }
}

/**
 * Return a component of the current path.
 * When viewing a page http://www.example.com/foo/bar and its path would be "foo/bar",
 * for example, _arg(0) returns "foo" and _arg(1) returns "bar"
 *
 * @param mixed $index
 *  The index of the component, where each component is separated by a '/' (forward-slash),
 *  and where the first component has an index of 0 (zero).
 * @param string $path
 *  A path to break into components. Defaults to the path of the current page.
 *
 * @return mixed
 *  The component specified by `$index`, or `null` if the specified component was not found.
 *  If called without arguments, it returns an array containing all the components of the current path.
 */
function _arg($index = null, $path = null)
{
    if (isset($_GET[$index])) {
        return _get($_GET[$index]);
    }

    if (is_null($path)) {
        $path = route_path();
    }
    $arguments = explode('/', $path);

    if (is_numeric($index)) {
        if (!isset($index)) {
            return $arguments;
        }
        if (isset($arguments[$index])) {
            return strip_tags(trim($arguments[$index]));
        }
    } elseif (is_string($index)) {
        $query = '-' . $index . '/';
        $pos = strpos($path, $query);
        if ($pos !== false) {
            $start  = $pos + strlen($query);
            $path  = substr($path, $start);
            $end   = strpos($path, '/-');
            if ($end) {
                $path = substr($path, 0, $end);
            }
            if (substr_count($path, '/')) {
                return explode('/', $path);
            } else {
                return $path;
            }
        }
    } elseif (is_null($index)) {
        return explode('/', str_replace('/-', '/', $path));
    }

    return '';
}

/**
 * Sanitize input values from GET
 * @param  mixed $get (Optional) The value or The array of values being sanitized.
 * @return mixed The cleaned value
 */
function _get($get = null)
{
    if ($get === null) {
        $get = $_GET;
    }

    if (is_array($get)) {
        foreach ($get as $name => $value) {
            if (is_array($value)) {
                $get[$name] = _get($value);
            } else {
                $value = _sanitize($value);
                $value = urldecode($value);
                $get[$name] = $value;
            }
        }
        return $get;
    } else {
        $value = strip_tags(trim($get));
        return urldecode($value);
    }
}

/**
 * Sanitize input values from POST
 * @param  mixed $post (Optional) The value or The array of values being sanitized
 * @return mixed the cleaned value
 */
function _post($post = null)
{
    if ($post === null) {
        $post = $_POST;
    }

    if (is_array($post)) {
        foreach ($post as $name => $value) {
            if (is_array($value)) {
                $post[$name] = _post($value);
            } else {
                $value = stripslashes($value);
                $value = _sanitize($value);
                $post[$name] = $value;
            }
        }
    } else {
        $value = stripslashes($post);
        $value = _sanitize($value);
        return $value;
    }

    return $post;
}

/**
 * Accessing PUT request data
 * @param  string $name The optional name of the value to be sanitized
 * @return mixed the cleaned value
 */
function _put($name = null)
{
    return __input($name);
}

/**
 * Accessing PATCH request data
 * @param  string $name The optional name of the value to be sanitized
 * @return mixed the cleaned value
 */
function _patch($name = null)
{
    return __input($name);
}

/**
 * Strips javascript tags in the value to prevent from XSS attack
 * @param mixed $value The value or The array of values being stripped.
 * @return mixed the cleaned value
 */
function _xss($value)
{
    if (is_object($value)) {
        return $value;
    }

    if (is_array($value)) {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $value[$key] = _xss($val);
            } else {
                $value[$key] = __xss($val);
            }
        }
    } else {
        $value = __xss($value);
    }

    return $value;
}
/**
 * Sanitize strings
 * @param  mixed $input Value to filter
 * @return mixed The filtered value
 */
function _sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_NOQUOTES);
}

/**
 * @internal
 * @ignore
 *
 * Accessing PUT/PATCH data
 * @param  string $name The optional name of the value to be sanitized
 * @return mixed the cleaned value
 */
function __input($name = null)
{
    $input = file_get_contents("php://input");
    if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
        $vars = json_decode($input, true);
    } else {
        parse_str($input, $vars);
    }

    if ($name) {
        return isset($vars[$name]) ? _sanitize(stripslashes($vars[$name])) : null;
    }

    foreach ($vars as $key => $value) {
        $vars[$key] = _sanitize(stripslashes($value));
    }

    return $vars;
}
/**
 * @internal
 * @ignore
 *
 * Strips javascript tags in the value to prevent from XSS attack
 * @param mixed $value The value being stripped.
 * @return mixed the cleaned value
 */
function __xss($value)
{
    $value = trim(stripslashes($value));
    $ascii = '[\x00-\x20|&\#x0A;|&\#x0D;|&\#x09;|&\#14;|<|!|\-|>]*';

    # Remove some tags
    $value = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|!--\#exec|style|form|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript)|title|xml|\?xml)[^>]*+>#i', '', $value);
    # Remove any attribute starting with "on" or xmlns
    $value = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $value);
    # Remove javascript: protocol
    $value = preg_replace('#([a-z]*)'.$ascii.'='.$ascii.'([`\'"]*)'.$ascii.'j'.$ascii.'a'.$ascii.'v'.$ascii.'a'.$ascii.'s'.$ascii.'c'.$ascii.'r'.$ascii.'i'.$ascii.'p'.$ascii.'t'.$ascii.':#iu', '$1=$2noscript:', $value);
    # Remove vbscript: protocol
    $value = preg_replace('#([a-z]*)'.$ascii.'='.$ascii.'([`\'"]*)'.$ascii.'v'.$ascii.'b'.$ascii.'s'.$ascii.'c'.$ascii.'r'.$ascii.'i'.$ascii.'p'.$ascii.'t'.$ascii.':#iu', '$1=$2noscript:', $value);
    # Remove livescript: protocol (older versions of Netscape only)
    $value = preg_replace('#([a-z]*)'.$ascii.'='.$ascii.'([`\'"]*)'.$ascii.'l'.$ascii.'i'.$ascii.'v'.$ascii.'e'.$ascii.'s'.$ascii.'c'.$ascii.'r'.$ascii.'i'.$ascii.'p'.$ascii.'t'.$ascii.':#iu', '$1=$2noscript:', $value);
    # Remove -moz-binding: css (Firefox only)
    $value = preg_replace('#([a-z]*)'.$ascii.'([\'"]*)'.$ascii.'(-moz-binding|javascript)'.$ascii.':#u', '$1$2noscript:', $value);
    # Remove dec/hex entities in tags, such as &#106;, &#0000106;, &#x6A;
    $value = preg_replace('#([a-z]*)'.$ascii.'='.$ascii.'([`\'"]*)((&\#x*[0-9A-F]+);*)+#iu', '$1', $value);

    # CSS expression; only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $chunk = str_split('expression');
    # to match
    # - expression:
    # - expr/*XSS*/ession:
    # - ex/*XSS*//*/*/pression:
    $expression = $ascii;
    foreach ($chunk as $chr) {
        $expression .= $chr . '(\/\*.*\*\/)*';
    }
    $expression .= $ascii;
    $value = preg_replace('#(<[^>]+?)style'.$ascii.'='.$ascii.'[`\'"]*.*?'.$expression.'\([^>]*+>#i', '$1>', $value);

    # CSS behaviour
    $chunk = str_split('behavior');
    $behavior = $ascii;
    foreach ($chunk as $chr) {
        $behavior .= $chr . '(\/\*.*\*\/)*';
    }
    $behavior .= $ascii;
    $value = preg_replace('#(<[^>]+?)style'.$ascii.'='.$ascii.'[`\'"]*.*?'.$behavior.'[^>]*+>#i', '$1>', $value);

    return $value;
}
