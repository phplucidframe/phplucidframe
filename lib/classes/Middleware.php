<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for pagination
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 2.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
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
    /** @var array Array of route filters by each middleware */
    private static $routeFilters = array();
    /** @var array Array of order by each middleware */
    private static $orders = array();

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
            $this->order(count(self::${$event}), $event);
        }

        return $this;
    }

    /**
     * Register route filter for the middleware
     * @param string $key One of the values - startWith, contain, equal, except
     * @param string $value URI or a part of URI
     * @return $this
     */
    public function on($key, $value)
    {
        if (self::$id) {
            self::$routeFilters[self::$id][$key][] = $value;
        }

        return $this;
    }

    /**
     * Register precedence of the middleware
     * @param int $sort Ascending order (smaller value runs first)
     * @param string $event before (default) or after
     * @return $this
     */
    public function order($sort, $event = self::BEFORE)
    {
        if (self::$id) {
            self::$orders[$event][self::$id] = $sort;
        }

        return $this;
    }

    /**
     * Run all registered middlewares (before)
     */
    public static function runBefore()
    {
        asort(self::$orders[self::BEFORE]);
        self::invoke(self::BEFORE);
    }

    /**
     * Run all registered middlewares (after)
     */
    public static function runAfter()
    {
        asort(self::$orders[self::AFTER]);
        self::invoke(self::AFTER);
    }

    /**
     * Run the registered middlewares
     * @param string $event before or after
     */
    private static function invoke($event)
    {
        $middlewares = $event == self::AFTER ? self::$after : self::$before;

        foreach (self::$orders[$event] as $id => $order) {
            $closure = $middlewares[$id];

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
                                    if (route_contain($val, $except)) {
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
