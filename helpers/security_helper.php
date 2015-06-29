<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for input sanitizing, data escaping, CSRF protection and XSS prevention
 *
 * @package     LC\Helpers\Security
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * @internal
 * Check the default security secret to be changed
 */
function security_prerequisite() {
    $defaultSecret = md5('lucidframe');
    $secret = trim(_cfg('securitySecret'));
    if (function_exists('mcrypt_encrypt') && strcmp($secret, $defaultSecret) === 0) {
        $msg = 'Change your own security hash in the file "/inc/.secret".';
        $msg .= 'Get your own hash string at <a href="http://phplucidframe.sithukyaw.com/hash-generator" target="_blank">phplucidframe.sithukyaw.com/hash-generator</a>.';
        _cfg('sitewideWarnings', function_exists('_t') ? _t($msg) : $msg);
    }
}
/**
 * Sanitize input values from GET
 * @param  mixed $get The value or The array of values being sanitized.
 * @return mixed The cleaned value
 */
function _get($get) {
    if (is_array($get)) {
        foreach ($get as $name=>$value) {
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
 * @param  mixed $post The value or The array of values being sanitized.
 * @return mixed the cleaned value
 */
function _post($post) {
    if (is_array($post)) {
        foreach ($post as $name=>$value) {
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
 * Strips javascript tags in the value to prevent from XSS attack
 * @param mixed $value The value or The array of values being stripped.
 * @return mixed the cleaned value
 */
function _xss($value) {
    if (is_object($value)) return $value;
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
function _sanitize($input) {
    return htmlspecialchars(trim($input), ENT_NOQUOTES);
}
/**
 * @internal
 *
 * Strips javascript tags in the value to prevent from XSS attack
 * @param mixed $value The value being stripped.
 * @return mixed the cleaned value
 */
function __xss($value) {
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
