<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Simple router for named routes that can be used with RegExp
 * Pretty familiar to anyone who's used Symfony
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.10.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
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
     * Define the custom routing path
     *
     * @param string $name Any unique route name to the mapped $path
     * @param string $path URL path with optional dynamic variables such as `/post/{id}/edit`
     * @param string $to The real path to a directory or file in /app
     * @param string $method GET, POST, PUT or DELETE or any combination with `|` such as GET|POST
     * @param array|null $patterns array of the regex patterns for variables in $path such s `array('id' => '\d+')`
     *
     * @return Router
     */
    public function add($name, $path, $to, $method = 'GET', $patterns = null)
    {
        $this->name = $name;

        $method = explode('|', strtoupper($method));
        $methods = array_filter($method, function($value) {
            return in_array($value, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'));
        });

        if (count($methods) == 0) {
            $methods = array('GET');
        }

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
     *
     * @return Router
     */
    public function map($path, $to, $method = 'GET', $patterns = null)
    {
        return $this->add($this->name, $path, $to, $method, $patterns);
    }

    /**
     * Matching the current route to the defined custom routes
     * @return string|boolean The matched route or false if no matched route is found
     */
    public static function match()
    {
        if (PHP_SAPI === 'cli') {
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

            $vars        = array();
            $matchedPath = array();
            foreach ($patternPath as $i => $segment) {
                if ($segment === $realPath[$i]) {
                    $matchedPath[$i] = $segment;
                    continue;
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
                                throw new \InvalidArgumentException(sprintf('The Router does not satisfy the argument value "%s" for "%s".', $var, $regex));
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
                        throw new \RuntimeException(sprintf('The Router does not allow the method "%s" for "%s".', $_SERVER['REQUEST_METHOD'], $key));
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
     * @param string $name The route name that is unique to the mapped path
     * @return string|null
     */
    public static function getPathByName($name)
    {
        return isset(self::$routes[$name]) ? trim(self::$routes[$name]['path'], '/') : null;
    }

    /**
     * Delete all defined named routes
     * @return void
     */
    public static function clean()
    {
        self::$routes = array();
    }

    /**
     * Define route group
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
}
