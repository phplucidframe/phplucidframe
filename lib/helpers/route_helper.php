<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for system routing
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

use LucidFrame\Core\Router;

/**
 * @internal
 * @ignore
 *
 * Initialize URL routing
 */
function __route_init()
{
    if (!isset($_SERVER['HTTP_REFERER'])) {
        $_SERVER['HTTP_REFERER'] = '';
    }
    if (!isset($_SERVER['SERVER_PROTOCOL']) ||
        ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')) {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
    }

    if (isset($_SERVER['HTTP_HOST'])) {
        # As HTTP_HOST is user input, ensure it only contains characters allowed
        # in hostnames. See RFC 952 (and RFC 2181).
        # $_SERVER['HTTP_HOST'] is lowercased here per specifications.
        $_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
        if (!_validHost($_SERVER['HTTP_HOST'])) {
            # HTTP_HOST is invalid, e.g. if containing slashes it may be an attack.
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            exit;
        }
    } else {
        # Some pre-HTTP/1.1 clients will not send a Host header. Ensure the key is
        # defined for E_ALL compliance.
        $_SERVER['HTTP_HOST'] = '';
    }
    # When clean URLs are enabled, emulate ?route=foo/bar using REQUEST_URI. It is
    # not possible to append the query string using mod_rewrite without the B
    # flag (this was added in Apache 2.2.8), because mod_rewrite unescapes the
    # path before passing it on to PHP. This is a problem when the path contains
    # e.g. "&" or "%" that have special meanings in URLs and must be encoded.
    $_GET[ROUTE] = route_request();
    _cfg('cleanRoute', $_GET[ROUTE]);

    $languages = _cfg('languages');
    if (count($languages) <= 1) {
        _cfg('translationEnabled', false);
    }
}

/**
 * @internal
 * @ignore
 *
 * Returns the requested URL path of the page being viewed.
 * Examples:
 * - http://example.com/foo/bar returns "foo/bar".
 *
 * @return string The requested URL path.
 */
function route_request()
{
    global $lc_baseURL;
    global $lc_languages;
    global $lc_lang;
    global $lc_langInURI;

    $lc_langInURI = _getLangInURI();
    if ($lc_langInURI === false) {
        $lc_lang = $lang = _cfg('defaultLang');
    } else {
        $lc_lang = $lang = $lc_langInURI;
    }

    if (isset($_GET[ROUTE]) && is_string($_GET[ROUTE])) {
        # This is a request with a ?route=foo/bar query string.
        $path = $_GET[ROUTE];
        if (isset($_GET['lang']) && $_GET['lang']) {
            $lang = strip_tags(urldecode($_GET['lang']));
            $lang = rtrim($lang, '/');
            if (array_key_exists($lang, $lc_languages)) {
                $lc_lang = $lang;
            }
        }
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        # This request is either a clean URL, or 'index.php', or nonsense.
        # Extract the path from REQUEST_URI.
        $requestPath = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));
        $requestPath = str_replace($lc_baseURL, '', ltrim($requestPath, '/'));
        $requestPath = ltrim($requestPath, '/');

        if ($lang) {
            $lc_lang = $lang;
            $path = trim($requestPath, '/');
            if (strpos($path, $lc_lang) === 0) {
                $path = substr($path, strlen($lang));
            }
        } else {
            $path = trim($requestPath);
        }

        # If the path equals the script filename, either because 'index.php' was
        # explicitly provided in the URL, or because the server added it to
        # $_SERVER['REQUEST_URI'] even when it wasn't provided in the URL (some
        # versions of Microsoft IIS do this), the front page should be served.
        if ($path == basename($_SERVER['PHP_SELF'])) {
            $path = '';
        }
    } else {
        # This is the front page.
        $path = '';
    }

    # Under certain conditions Apache's RewriteRule directive prepends the value
    # assigned to $_GET[ROUTE] with a slash. Moreover we can always have a trailing
    # slash in place, hence we need to normalize $_GET[ROUTE].
    $path = trim($path, '/');

    if (!defined('WEB_ROOT')) {
        $baseUrl = _baseUrlWithProtocol();
        if ($baseUrl) {
            # path to the web root
            define('WEB_ROOT', $baseUrl . '/');
            # path to the web app root
            define('WEB_APP_ROOT', WEB_ROOT . APP_DIR . '/');
            # path to the home page
            define('HOME', WEB_ROOT);
        }
    }

    session_set('lang', $lc_lang);

    return $path;
}

/**
 * Search the physical directory according to the routing path
 *
 * @return mixed The path if found; otherwise return false
 */
function route_search()
{
    $q     = route_path();
    $seg   = explode('/', $q);
    $count = sizeof($seg);
    $sites = _cfg('sites');

    if ($seg[0] == LC_NAMESPACE && is_array($sites) && array_key_exists(LC_NAMESPACE, $sites)) {
        $seg[0] = $sites[LC_NAMESPACE];
    }

    $path = implode('/', $seg);
    if (is_file($path) && file_exists($path)) {
        if (count($seg) > 1) {
            _cfg('cleanRoute', implode('/', array_slice($seg, 0, count($seg)-1)));
        } else {
            _cfg('cleanRoute', '');
        }
        return $path;
    }

    $append = array('/index.php', '.php');
    for ($i=$count; $i>0; $i--) {
        # try to look for
        # ~/path/to/the-given-name/index.php
        # ~/path/to/the-given-name.php
        foreach ($append as $a) {
            $cleanRoute = implode('/', array_slice($seg, 0, $i));
            $path = $cleanRoute . $a;
            if (is_file($path) && file_exists($path)) {
                _cfg('cleanRoute', rtrim($cleanRoute, '/'));

                $definedRoutes = Router::getRoutes();
                // Find matching routes for this clean route
                $routes = array_filter($definedRoutes, function ($route) use ($cleanRoute) {
                    return ltrim($route['to'], '/') == ltrim($cleanRoute, '/');
                });

                foreach ($routes as $key => $value) {
                    if (!in_array($_SERVER['REQUEST_METHOD'], $value['method'])) {
                        if ($key == Router::getMatchedName()) {
                            _header(405);
                            throw new \RuntimeException(sprintf('The Router does not allow the method "%s" for "%s".', $_SERVER['REQUEST_METHOD'], $key));
                        } else {
                            _header(404);
                            throw new \RuntimeException(sprintf('The Router is not found for "%s".', Router::getMatchedName()));
                        }
                    }
                }

                return $path;
            }
        }
    }

    return false;
}

/**
 * Get the routing path
 *
 * Alias `_r()`
 * @return string
 */
function route_path()
{
    $path = '';
    if (isset($_GET[ROUTE])) {
        $path = urldecode($_GET[ROUTE]);
    }
    return $path;
}

/**
 * Return the absolute URL path appended the query string if necessary
 *
 * Alias `_url()`
 *
 * @param string $path Routing path such as "foo/bar"; Named route such as "foo_bar"; null for the current path
 * @param array $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @param string $lang Language code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 *
 * @return string
 *
 */
function route_url($path = null, $queryStr = array(), $lang = '')
{
    global $lc_cleanURL;
    global $lc_translationEnabled;
    global $lc_sites;
    global $lc_langInURI;

    $forceExcludeLangInURL = ($lang === false) ? true : false;

    if (stripos($path, 'http') === 0) {
        return $path;
    }

    $customRoute = Router::getPathByName($path);
    if ($customRoute !== null) {
        $path = $customRoute ? $customRoute : 'home';
        if ($queryStr && is_array($queryStr) && count($queryStr)) {
            foreach ($queryStr as $key => $value) {
                $path = str_replace('{'.$key.'}', urlencode($value), $path);
            }
        }

        $queryStr = array(); // clean query strings to not be processed later
    }

    if ($path && is_string($path)) {
        $path = rtrim($path, '/');
    } else {
        $r = (_isRewriteRule()) ? REQUEST_URI : route_path();
        $path = route_updateQueryStr($r, $queryStr);
    }

    $q = '';
    if ($queryStr && is_array($queryStr) && count($queryStr)) {
        foreach ($queryStr as $key => $value) {
            if (is_array($value)) {
                $v = array_map('urlencode', $value);
                $value = implode('/', $v);
            } else {
                $value = urlencode($value);
            }
            if (is_numeric($key)) {
                if ($lc_cleanURL) {
                    $q .= '/' . $value;
                } else {
                    $q .= '&'.$value;
                }
            } else {
                if ($lc_cleanURL) {
                    $q .= '/-' . $key . '/' . $value;
                } else {
                    $q .= '&' . $key . '=' . $value;
                }
            }
        }
    }

    if (is_array($lc_sites) && array_key_exists(LC_NAMESPACE, $lc_sites)) {
        $regex = '/\b^('.$lc_sites[LC_NAMESPACE].') {1}\b/i';
        $path = preg_replace($regex, LC_NAMESPACE, $path);
    }

    # If URI contains the language code, force to include it in the URI
    if (is_null($lc_langInURI)) {
        $lc_langInURI = _getLangInURI();
    }

    if (empty($lang) && $lc_langInURI) {
        $lang = $lc_langInURI;
    }

    $url = WEB_ROOT;
    if ($lang && $lc_translationEnabled && !$forceExcludeLangInURL) {
        if ($lc_cleanURL) {
            $url .= $lang.'/';
        } else {
            $q .= '&lang=' . $lang;
        }
    }

    if (strtolower($path) == 'home') {
        $path = '';
        $q = ltrim($q, '/');
    }

    if ($lc_cleanURL) {
        $url .= $path . $q;
    } else {
        $url .= $path . '?' . ltrim($q, '&');
        $url = trim($url, '?');
    }

    $url = preg_replace('/(\s) {1,}/', '+', $url); # replace the space with "+"
    $url = rtrim($url, '/');

    return $url;
}

/**
 * Update the route path with the given query string
 *
 * @param string $path The route path which may contain the query string
 * @param array $queryStr Query string as
 *
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 *
 * @return string The updated route path
 */
function route_updateQueryStr($path, &$queryStr = array())
{
    global $lc_cleanURL;

    if (is_array($queryStr) && count($queryStr)) {
        if ($lc_cleanURL) {
            # For clean URLs like /path/query/str/-key/value
            foreach ($queryStr as $key => $value) {
                $route = _arg($key, $path);
                if ($route) {
                    if (is_string($key)) {
                        $regex = '/(\-'.$key.'\/)';
                        if (is_array($route)) {
                            $regex .= '('.implode('\/', $route).'+)';
                        } else {
                            $regex .= '('.$route.'+)';
                        }
                        $regex .= '/i';
                    } elseif (is_numeric($key)) {
                        $regex = '/\b('.$route.'){1}\b/i';
                    } else {
                        continue;
                    }
                } else {
                    # if the key could not be retrieved from URI, skip it
                    continue;
                }
                if (preg_match($regex, $path)) {
                    # find the key in URI
                    if (is_array($value)) {
                        $v = array_map('urlencode', $value);
                        $value = implode('/', $v);
                    } else {
                        $value = urlencode($value);
                    }
                    if (is_numeric($key)) {
                        $path = preg_replace($regex, $value, $path); # no key
                    } else {
                        $path = preg_replace($regex, '-'.$key.'/'.$value, $path);
                    }
                    unset($queryStr[$key]); # removed the replaced query string from the array
                }
            }
        } else {
            # For unclean URLs like /path/query/str?key=value
            parse_str($_SERVER['QUERY_STRING'], $serverQueryStr);
            $queryStr = array_merge($serverQueryStr, $queryStr);
        }
    }

    return $path;
}

/**
 * @internal
 * @ignore
 *
 * Get the absolute path from root of the given route
 * @param string $q
 * @return string
 */
function route_getAbsolutePathToRoot($q)
{
    # Get the complete path to root
    $_page = ROOT . $q;

    if (!(is_file($_page) && file_exists($_page))) {
        # Get the complete path with app/
        $_page = APP_ROOT . $q;
        # Find the clean route
        $_seg = explode('/', $q);
        if (is_dir($_page)) {
            _cfg('cleanRoute', $q);
        } else {
            array_pop($_seg); # remove the last element
            _cfg('cleanRoute', implode('/', $_seg));
        }
    }

    # if it is a directory, it should have index.php
    if (is_dir($_page)) {
        $_page .= '/index.php';
    } else {
        $pathInfo = pathinfo($_page);
        if (!isset($pathInfo['extension'])) {
            $_page .= '.php';
        }
    }

    return $_page;
}

/**
 * @internal
 * @ignore
 *
 * Matching the current route to the defined custom routes
 * @return string|boolean The matched route or false if no matched route is found
 */
function route_match()
{
    return Router::match();
}

/**
 * @internal
 * @ignore
 *
 * Route to a page according to request
 * interally called by /app/index.php
 *
 * @return string
 */
function router()
{
    # Get a route from the defined custom routes (if any)
    $_page = route_match();
    if ($_page) {
        $pathToPage = route_getAbsolutePathToRoot($_page);
        if (is_file($pathToPage) && file_exists($pathToPage)) {
            return $pathToPage;
        }
    }

    $q = route_path();

    # if it is still empty, set it to the system default
    if (empty($q)) {
        $q = 'home';
    }

    # Get the complete path to root
    $_page = route_getAbsolutePathToRoot($q);
    if (!empty($_page) && is_file($_page) && file_exists($_page)) {
        return $_page;
    }

    if (preg_match('/(.*)(401|403|404) {1}$/', $_page, $matches)) {
        return _i('inc/tpl/'.$matches[2].'.php');
    }

    # Search the physical directory according to the routing path
    $_page = route_search();
    if ($_page && is_file($_page) && file_exists($_page)) {
        return $_page;
    }

    if (in_array(_arg(0), array('401', '403'))) {
        _header(_arg(0));
        return _i('inc/tpl/'._arg(0).'.php');
    } else {
        _header(404);
        return _i('inc/tpl/404.php');
    }
}

/**
 * Initialize a route to define
 *
 * @param string $name The route name that is unique to the mapped path
 * @return object Router
 */
function route($name)
{
    return new Router($name);
}

/**
 * Define route group
 * @param string $prefix A prefix for the group of the routes
 * @param callable $callback The callback function that defines each route in the group
 */
function route_group($prefix, $callback)
{
    Router::group($prefix, $callback);
}

/**
 * Get the current route name
 * @return string The route name defined in route.config.php
 */
function route_name()
{
    return Router::getMatchedName();
}

/**
 * Check if the current route is equal to the given uri or route name
 * @param  string $uri URI string or the route name defined in route.config.php
 * @return boolean true if it is matched, otherwise false
 */
function route_equal($uri)
{
    $uri = trim($uri, '/');

    return $uri == _rr() || $uri == route_name();
}

/**
 * Check if the current route uri is started with the given uri
 * @param  string $uri URI string
 * @return boolean true/false
 */
function route_start($uri)
{
    return stripos(_rr(), trim($uri, '/')) === 0;
}

/**
 * Check if the current route uri contains the given uri
 * @param  string $uri URI string
 * @return boolean true/false
 */
function route_contain($uri)
{
    return stristr(_rr(), trim($uri, '/')) ? true : false;
}
