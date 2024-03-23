<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for system routing
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\Router;

/**
 *
 * @internal
 * @ignore
 * Route to a page according to request
 * internally called by /app/index.php
 * @return string
 */
function router()
{
    if (PHP_SAPI === 'cli') {
        return '';
    }

    # Get a route from the defined custom routes (if any)
    $_page = Router::match();
    if ($_page) {
        $pathToPage = Router::getAbsolutePathToRoot($_page);
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
    $_page = Router::getAbsolutePathToRoot($q);
    if (!empty($_page) && is_file($_page) && file_exists($_page)) {
        return $_page;
    }

    if (preg_match('/(.*)(401|403|404) {1}$/', $_page, $matches)) {
        return _i('inc/tpl/' . $matches[2] . '.php');
    }

    # Search the physical directory according to the routing path
    $_page = route_search();
    if ($_page && is_file($_page) && file_exists($_page)) {
        return $_page;
    }

    if (in_array(_arg(0), array('401', '403'))) {
        _header(_arg(0));
        return _i('inc/tpl/' . _arg(0) . '.php');
    } else {
        _header(404);
        return _i('inc/tpl/404.php');
    }
}

/**
 * Search the physical directory according to the routing path
 *
 * @return mixed The path if found; otherwise return false
 */
function route_search()
{
    $q = route_path();
    $seg = explode('/', $q);
    $count = sizeof($seg);
    $sites = _cfg('sites');

    if ($seg[0] == LC_NAMESPACE && is_array($sites) && array_key_exists(LC_NAMESPACE, $sites)) {
        $seg[0] = $sites[LC_NAMESPACE];
    }

    $path = implode('/', $seg);
    if (is_file($path) && file_exists($path)) {
        if (count($seg) > 1) {
            _cfg('cleanRoute', implode('/', array_slice($seg, 0, count($seg) - 1)));
        } else {
            _cfg('cleanRoute', '');
        }
        return $path;
    }

    $append = array('/index.php', '/view.php', '.php');
    for ($i = $count; $i > 0; $i--) {
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
 * Alias `_r()`
 *
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
 * Alias `_url()`
 *
 * @param string $path Routing path such as "foo/bar"; Named route such as "fool_bar"; NULL for the current path
 * @param array $queryStr Query string as
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
 * @param string $lang Language code to be prepended to $path such as "en/foo/bar".
 *   It will be useful for site language switch redirect
 * @return string
 */
function route_url($path = null, $queryStr = array(), $lang = '')
{
    global $lc_cleanURL;
    global $lc_translationEnabled;
    global $lc_sites;
    global $lc_langInURI;

    $forceExcludeLangInURL = $lang === false;

    if ($path && stripos($path, 'http') === 0) {
        return $path;
    }

    $customRoute = Router::getPathByName($path);
    if ($customRoute !== null) {
        $path = $customRoute ? $customRoute : 'home';
        if ($queryStr && is_array($queryStr) && count($queryStr)) {
            foreach ($queryStr as $key => $value) {
                $path = str_replace('{' . $key . '}', urlencode($value), $path);
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
                    $q .= '&' . $value;
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
        $regex = str_replace('/', '\/', $lc_sites[LC_NAMESPACE]);
        $regex = '/\b^(' . $regex . ') {1}\b/i';
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
            $url .= $lang . '/';
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
    $url = preg_replace('/\?&/', '?', $url);
    $url = preg_replace('/&&/', '&', $url);

    return rtrim($url, '/');
}

/**
 * Update the route path with the given query string
 *
 * @param string $path The route path which may contain the query string
 * @param array $queryStr Query string as
 *     array(
 *       $value1, // no key here
 *       'key1' => $value2,
 *       'key3' => $value3 or array($value3, $value4)
 *     )
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
                        $regex = '/(\-' . $key . '\/)';
                        if (is_array($route)) {
                            $regex .= '(' . implode('\/', $route) . '+)';
                        } else {
                            $regex .= '(' . $route . '+)';
                        }
                        $regex .= '/i';
                    } elseif (is_numeric($key)) {
                        $regex = '/\b(' . $route . '){1}\b/i';
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
                        $path = preg_replace($regex, '-' . $key . '/' . $value, $path);
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
 *
 * @param string $prefix A prefix for the group of the routes
 * @param callable $callback The callback function that defines each route in the group
 */
function route_group($prefix, $callback)
{
    Router::group($prefix, $callback);
}

/**
 * Get the current route name
 *
 * @return string The route name defined in route.config.php
 */
function route_name()
{
    return Router::getMatchedName();
}

/**
 * Check if the current route is equal to the given uri or route name
 *
 * @param string $uri URI string or the route name defined in route.config.php
 * @return boolean true if it is matched, otherwise false
 */
function route_equal($uri)
{
    $uri = trim($uri, '/');

    return $uri == _rr() || $uri == route_name();
}

/**
 * Check if the current route uri is started with the given uri
 *
 * @param string $uri URI string
 * @param array $except Array of URI string to be excluded in check
 * @return boolean true/false
 */
function route_start($uri, array $except = array())
{
    if (call_user_func_array('route_except', $except) === false) {
        return false;
    }

    if ($uri) {
        $uri = trim($uri, '/');
    }

    return $uri && stripos(_rr(), $uri) === 0;
}

/**
 * Check if the current route uri contains the given URI or list of URIs
 *
 * @param array|string $uri URI string or array of URI strings
 * @param array $except Array of URI string to be excluded in check
 * @return boolean true/false
 */
function route_contain($uri, array $except = array())
{
    if (call_user_func_array('route_except', $except) === false) {
        return false;
    }

    $args = is_array($uri) ? $uri : array($uri);
    foreach ($args as $uri) {
        if ($uri) {
            $uri = trim($uri, '/');
        }

        if (stristr(_rr(), $uri)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if the current route uri is in th exception list
 *
 * @param string $args Variable list of URI strings
 * @return boolean true/false
 * @since PHPLucidFrame v 3.0.0
 */
function route_except()
{
    $except = func_get_args();
    if (count($except)) {
        foreach ($except as $string) {
            if ($string) {
                $string = trim($string, '/');
            }

            if (stripos(_rr(), $string) === 0 || route_name() == $string) {
                return false;
            }
        }
    }

    return true;
}
