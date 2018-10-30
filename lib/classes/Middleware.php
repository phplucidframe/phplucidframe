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

    /** @var array Array of registered middlewares (before) */
    private static $before = array();
    /** @var array Array of registered middlewares (after) */
    private static $after = array();

    /**
     * Register a middleware
     * @param  Closure $closure Anonymous function
     * @param  string $event before or after
     * @return object \LucidFrame\Core\Middleware
     */
    public function register(\Closure $closure, $event = self::BEFORE)
    {
        if (in_array($event, array(self::BEFORE, self::AFTER))) {
            self::${$event}[] = $closure;
        }

        return $this;
    }

    /**
     * Run all registered middleware (before)
     */
    public static function runBefore()
    {
        foreach (self::$before as $closure) {
            $closure();
        }
    }

    /**
     * Run all registered middleware (after)
     */
    public static function runAfter()
    {
        foreach (self::$after as $closure) {
            $closure();
        }
    }
}
