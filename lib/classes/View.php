<?php
/**
 * This file is part of the PHPLucidFrame library.
 * SchemaManager manages your database schema.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 3.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

class View
{
    /**
     * @var string Layout file name
     */
    private $layout = 'layout';
    /**
     * @var string View name to append to the file name such as view_{$name}.php
     */
    private $name;
    /**
     * @var array Array of data passed into view
     */
    private $data = array();

    /**
     * Setter
     *
     * @param string $name The property name
     * @param mixed $value The property value
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Getter
     *
     * @param string $name The property name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Add data into view
     *
     * @param string $key The variable name to be accessible in view
     * @param mixed $value The value of the variable
     */

    public function addData($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Display view
     *
     * @param string $name Optional view name to append to the file name such as view_{$name}.php
     * @return void
     */
    public function load($name = '')
    {
        $name = $name ?: $this->name;

        if ($name) {
            $viewName = 'view_' . $name;
        } else {
            $viewName = 'view';
        }

        $view = _i(_ds(_cr(), $viewName . '.php'));
        if ($view) {
            extract($this->data);
            include $view;
        } else {
            throw new \RuntimeException('View file is missing.');
        }
    }
}
