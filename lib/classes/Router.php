<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Simple router for named routes that can be used with RegExp
 * Pretty familiar to anyone who's used Symfony
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.10.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

/**
 * Simple router for named routes that can be used with RegExp
 */
class Router
{
    /** @var array The custom routes defined */
    static protected $routes = array();
    /** @var string The route name matched */
    static protected $matchedRouteName;
    /** @var string The route name that is unique to the mapped path */
    protected $name;

    /**
     * Constructor
     *
     * @param string $name The route name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Getter for $routes
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * Getter for $matchedRouteName
     */
    public static function getMatchedName()
    {
        return self::$matchedRouteName;
    }

    /**
     * Getter for $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Initialize URL routing
     */
    public static function init()
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
        $_GET[ROUTE] = Router::request();
        _cfg('cleanRoute', $_GET[ROUTE]);

        $languages = _cfg('languages');
        if (count($languages) <= 1) {
            _cfg('translationEnabled', false);
        }
    }

    /**
     * Returns the requested URL path of the page being viewed.
     * Examples:
     * - http://example.com/foo/bar returns "foo/bar".
     *
     * @return string The requested URL path.
     */
    public static function request()
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
        # assigned to $_GET[ROUTE] with a slash. Moreover, we can always have a trailing
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
     * Define the custom routing path
     *
     * @param string $name Any unique route name to the mapped $path
     * @param string $path URL path with optional dynamic variables such as `/post/{id}/edit`
     * @param string $to The real path to a directory or file in /app
     * @param string $method GET, POST, PUT or DELETE or any combination with `|` such as GET|POST
     * @param array|null $patterns array of the regex patterns for variables in $path such s `array('id' => '\d+')`
     * @return Router
     */
    public function add($name, $path, $to, $method = 'GET', $patterns = null)
    {
        $this->name = $name;

        $method = explode('|', strtoupper($method));
        $methods = array_filter($method, function ($value) {
            return in_array($value, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'CONNECT', 'TRACE'));
        });

        if (count($methods) == 0) {
            $methods = array('GET');
        }

        $methods[] = 'OPTIONS';
        $methods = array_unique($methods);

        self::$routes[$this->name] = array(
            'path'      => $path,
            'to'        => $to,
            'method'    => $methods,
            'patterns'  => $patterns
        );

        return $this;
    }

    /**
     * Define the custom routing path
     *
     * @param string $path URL path with optional dynamic variables such as `/post/{id}/edit`
     * @param string $to The real path to a directory or file in `/app`
     * @param string $method GET, POST, PUT or DELETE or any combination with `|` such as GET|POST
     * @param array|null $patterns array of the regex patterns for variables in $path such s `array('id' => '\d+')`
     * @return Router
     */
    public function map($path, $to, $method = 'GET', $patterns = null)
    {
        return $this->add($this->name, $path, $to, $method, $patterns);
    }

    /**
     * Matching the current route to the defined custom routes
     *
     * @return string|boolean The matched route or false if no matched route is found
     */
    public static function match()
    {
        if (PHP_SAPI === 'cli' && _cfg('env') != ENV_TEST) {
            return false;
        }

        $realPath = explode('/', route_path());
        $routes   = self::$routes;

        if (!(is_array($routes) && count($routes))) {
            return false;
        }

        $matchedRoute = array_filter($routes, function ($array) use ($realPath) {
            $last = array_pop($realPath);
            $path = '/' . implode('/', $realPath);
            if ($array['path'] == $path && in_array($_SERVER['REQUEST_METHOD'], $array['method'])
                && file_exists(APP_ROOT . $array['to'] . _DS_ . $last . '.php')) {
                return true;
            }

            return false;
        });

        if (count($matchedRoute)) {
            return false;
        }

        $found = false;
        foreach ($routes as $key => $value) {
            $patternPath = explode('/', trim($value['path'], '/'));
            if (count($realPath) !== count($patternPath)) {
                continue;
            }

            $vars = array();
            $matchedPath = array();
            foreach ($patternPath as $i => $segment) {
                if ($segment === $realPath[$i]) {
                    $matchedPath[$i] = $segment;
                } else {
                    if (preg_match('/([a-z0-9\-_\.]*)?{([a-z0-9\_]+)}([a-z0-9\-_\.]*)?/i', $segment, $matches)) {
                        $name = $matches[2];
                        $var = $realPath[$i];

                        if ($matches[1]) {
                            $var = ltrim($var, $matches[1] . '{');
                        }

                        if ($matches[3]) {
                            $var = rtrim($var, '}' . $matches[3]);
                        }

                        if (isset($value['patterns'][$name]) && $value['patterns'][$name]) {
                            $regex = $value['patterns'][$name];
                            if (!preg_match('/^' . $regex . '$/', $var)) {
                                _header(400);
                                throw new \InvalidArgumentException(sprintf('The URL does not satisfy the argument value "%s" for "%s".', $var, $regex));
                            }
                        }

                        $vars[$name] = $var;
                        $matchedPath[$i] = $realPath[$i];

                        continue;
                    }
                    break;
                }
            }

            if (route_path() === implode('/', $matchedPath)) {
                # Find all routes that have same route paths and are valid for the current request method
                $matchedRoute = array_filter($routes, function ($array) use ($value) {
                    return $array['path'] == $value['path'] && in_array($_SERVER['REQUEST_METHOD'], $array['method']);
                });

                if (count($matchedRoute)) {
                    $key = array_keys($matchedRoute)[0];
                    $value = $matchedRoute[$key];
                    $found = true;
                    break;
                } else {
                    if (!in_array($_SERVER['REQUEST_METHOD'], $value['method'])) {
                        _header(405);
                        throw new \RuntimeException(sprintf('The URL does not allow the method "%s" for "%s".', $_SERVER['REQUEST_METHOD'], $key));
                    }
                }
            }
        }

        if ($found) {
            self::$matchedRouteName = $key;
            $toRoute     = trim($value['to'], '/');
            $_GET[ROUTE] = $toRoute;
            $_GET        = array_merge($_GET, $vars);
            return $toRoute;
        }

        return false;
    }

    /**
     * Get the path from the given name
     *
     * @param string $name The route name that is unique to the mapped path
     * @return string|null
     */
    public static function getPathByName($name)
    {
        return isset(self::$routes[$name]) ? trim(self::$routes[$name]['path'], '/') : null;
    }

    /**
     * Delete all defined named routes
     *
     * @return void
     */
    public static function clean()
    {
        self::$routes = array();
    }

    /**
     * Define route group
     *
     * @param string $prefix A prefix for the group of the routes
     * @param callable $callback The callback function that defines each route in the group
     */
    public static function group($prefix, $callback)
    {
        $before = self::$routes;

        $callback();

        $groupRoutes = array_splice(self::$routes, count($before));
        foreach ($groupRoutes as $name => $route) {
            $route['path'] = '/' . ltrim($prefix, '/') . '/' . trim($route['path'], '/');
            $groupRoutes[$name] = $route;
        }

        self::$routes += $groupRoutes;
    }

    /**
     * Get the absolute path from root of the given route
     *
     * @param string $q
     * @return string
     */
    public static function getAbsolutePathToRoot($q)
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
            foreach (array('index', 'view') as $pg) {
                $page = $_page . '/' . $pg . '.php';
                if (is_file($page) && file_exists($page)) {
                    $_page = $page;
                    break;
                }
            }
        } else {
            $pathInfo = pathinfo($_page);
            if (!isset($pathInfo['extension'])) {
                $_page .= '.php';
            }
        }

        return $_page;
    }
}
