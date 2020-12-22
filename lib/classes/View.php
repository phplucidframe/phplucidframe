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
     * @var array Array of css file names
     */
    private $headStyles = array();
    /**
     * @var array Array of js file names
     */
    private $headScripts = array();

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
     * Add CSS file to be included in head section
     * @param string $file An absolute file path or file name only.
     *  The file name only will be prepended the folder name css/ and it will be looked in every sub-sites "css" folder
     */
    public function addHeadStyle($file)
    {
        $this->headStyles[] = $file;
        $this->headStyles = array_unique($this->headStyles);
    }

    /**
     * Add JS file to be included in head section
     * @param string $file An absolute file path or file name only.
     *  The file name only will be prepended the folder name js/ and it will be looked in every sub-sites "js" folder
     */
    public function addHeadScript($file)
    {
        $this->headScripts[] = $file;
        $this->headScripts = array_unique($this->headScripts);
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

    /**
     * Display block view
     *
     * @param string $name Block view name to the file name such as block_{$name}.php
     * @param array $data
     */
    public function block($name, array $data = array())
    {
        $blockName = 'block_' . $name;
        $this->data = array_merge($this->data, $data);

        $block = _i(_ds(_cr(), $blockName . '.php'));
        if ($block) {
            extract($this->data);
            include $block;
        } else {
            throw new \RuntimeException('Block view file is missing.');
        }
    }

    /**
     * Include CSS files in head section. Make sure calling this method in head
     */
    public function headStyle()
    {
        foreach ($this->headStyles as $file) {
            _css($file);
        }
    }

    /**
     * Include JS files in head section. Make sure calling this method in head
     */
    public function headScript()
    {
        foreach ($this->headScripts as $file) {
            _js($file);
        }
    }
}
