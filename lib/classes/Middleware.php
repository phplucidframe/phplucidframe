<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for pagination
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 2.0.0
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
 * Middleware Class
 */
class Middleware
{
    const BEFORE = 'before';
    const AFTER = 'after';

    const FILTER_START_WITH = 'startWith';
    const FILTER_CONTAIN    = 'contain';
    const FILTER_EQUAL      = 'equal';
    const FILTER_EXCEPT     = 'except';

    /** @var array Array of registered middlewares (before) */
    private static $before = array();
    /** @var array Array of registered middlewares (after) */
    private static $after = array();
    /** @var string Unique id */
    private static $id;
    /** @var array */
    private static $routeFilters = array();

    /**
     * Register a middleware
     * @param \Closure $closure Anonymous function
     * @param string $event before or after
     * @return $this
     */
    public function register(\Closure $closure, $event = self::BEFORE)
    {
        self::$id = uniqid();

        if (in_array($event, array(self::BEFORE, self::AFTER))) {
            self::${$event}[self::$id] = $closure;
        }

        return $this;
    }

    public function on($key, $value)
    {
        if (self::$id) {
            self::$routeFilters[self::$id][$key][] = $value;
        }

        return $this;
    }

    /**
     * Run all registered middlewares (before)
     */
    public static function runBefore()
    {
        self::invoke(self::$before);
    }

    /**
     * Run all registered middlewares (after)
     */
    public static function runAfter()
    {
        self::invoke(self::$after);
    }

    /**
     * Run the registered middlewares
     * @param array $middlewares List of middlewares
     */
    private static function invoke(array $middlewares)
    {
        foreach ($middlewares as $id => $closure) {
            if (isset(self::$routeFilters[$id])) {
                $except = array();
                if (isset(self::$routeFilters[$id][self::FILTER_EXCEPT])) {
                    foreach (self::$routeFilters[$id][self::FILTER_EXCEPT] as $exp) {
                        $exp = is_array($exp) ? $exp : array($exp);
                        $except = array_merge($except, $exp);
                    }
                    unset(self::$routeFilters[$id][self::FILTER_EXCEPT]);
                }

                if (count(self::$routeFilters[$id])) {
                    foreach (self::$routeFilters[$id] as $filter => $value) {
                        foreach ($value as $val) {
                            switch($filter) {
                                case self::FILTER_START_WITH:
                                    if (route_start($val, $except)) {
                                        $closure();
                                    }
                                    break;

                                case self::FILTER_CONTAIN:
                                    $val = is_array($val) ? $val : array($val);
                                    if (call_user_func_array('route_contain', $val)) {
                                        $closure();
                                    }
                                    break;

                                case self::FILTER_EQUAL:
                                    if (route_equal($val)) {
                                        $closure();
                                    }
                                    break;
                            }
                        }
                    }
                } else {
                    if (count($except) && call_user_func_array('route_except', $except)) {
                        $closure();
                    }
                }
            } else {
                $closure();
            }
        }
    }
}
