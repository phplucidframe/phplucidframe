<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 4.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace LucidFrame\Core;

class Component
{
    private const BASE_DIR = '@components';

    /**
     * @var string Component name
     */
    private $name;

    /**
     * @var array Props data to be passed to the component
     */
    private $props = [];

    /**
     * @var array Stateful data to be passed to the component
     */
    private $data;

    /**
     * @var array The component live action functions
     */
    private $actions = [];

    /**
     * @var array Arguments to each action function
     */
    private $actionParams = [];

    /**
     * @var string The resolved file path for the component logic
     */
    private $file;

    /**
     * @var string The resolved file path for the component view
     */
    private $view;

    /**
     * Component constructor.
     *
     * @param string $name The component name (with or without ".php")
     * @param array $data Variables to be extracted and available in the component
     */
    public function __construct(string $name, array $data = array())
    {
        $requestData = _request($name) ?: [];
        $action = _get('action');
        if ($action && !empty($requestData['action_params'])) {
            $this->actionParams[$action] = $requestData['action_params'];
        }

        $this->name = str_replace('.php', '', $name);
        $this->data = array_merge($data, $requestData);

        $this->resolveFiles();
    }

    /**
     * Add props data to the component
     *
     * @param string $key The data key
     * @param mixed $value The data value
     * @return mixed
     */
    public function setProps(string $key, $value = '')
    {
        $this->props[$key] = $value;

        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        return $this->props[$key];
    }

    /**
     * Get component props data
     * @param string $name
     * @return mixed
     */
    public function getProps(string $name = '')
    {
        if ($name) {
            return $this->props[$name];
        }

        return $this->props;
    }

    /**
     * Add stateful data to the component
     *
     * @param string $key The data key
     * @param mixed $value The data value
     * @return mixed
     */
    public function setData(string $key, $value = '')
    {
        $this->data[$key] = $value;

        return $this->data[$key];
    }

    /**
     * Get component stateful data
     * @param string $name
     * @return mixed
     */
    public function getData(string $name = '')
    {
        if ($name) {
            return $this->data[$name];
        }

        return $this->data;
    }

    /**
     * Create and get component stateful data with a default value if the key does not exist.
     *
     * @param string $name The data field name
     * @param mixed $default The default value if the key does not exist
     * @return mixed|null The data value or the default value
     */
    public function useData(string $name = '', $default = null)
    {
        if (!isset($this->data[$name])) {
            $this->data[$name] = $default;
        }

        return $this->data[$name];
    }

    /**
     * Register a live action function to the component.
     *
     * @param string $name The action (function) name
     * @param \Closure $closure The function closure
     * @return void
     */
    public function action(string $name, \Closure $closure): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Action name '$name' must follow function naming convention: starts with a letter or underscore, followed by letters, numbers, or underscores");
        }

        $this->actions[$name] = $closure;
    }

    /**
     * Render the component.
     * Resolves the component by name under "@components" using one of these layouts:
     *  - "@components/{name}.php" + "@components/{name}.view.php"
     *  - "@components/{name}/index.php" + "@components/{name}/view.php"
     *  The lookup also tries an underscore variant of the name (hyphens replaced with underscores).
     * - Merges auto-generated HTML attributes into the provided `$data` as `$data['attributes']`.
     *
     * @param bool $return When true, returns the rendered HTML as a string; otherwise echoes it.
     * @return false|string|void Returns the HTML string when `$return` is true; otherwise echoes the content
     */
    public function render(bool $return = false)
    {
        $component = $this;
        include $this->file;
        $data = array_merge($this->data, $this->props);

        [$action, $args] = $this->getAction();
        if ($action) {
            $args = $args ? array_merge([$this], $args) : [$this];
            call_user_func_array($action, $args);
            $data = array_merge($data, $this->data);
        }

        extract($data); // ensure the updated values are available to the view

        ob_start();
        echo '<div id="lc_component_' . $this->name . '">';
        echo '<style>[data-loading]{ display: none }</style>';
        include $this->view;
        echo '<script>$(function() { LC.Component.init("' . $this->name . '", ' . json_encode($data). ') });</script>';
        echo '</div>';

        $content = ob_get_clean();

        if ($return) {
            return $content;
        }

        echo $content;
    }

    /**
     * Retrieves the action to be called and its arguments.
     *
     * This method checks if an action is specified in the request and if it is defined in the component.
     * If both conditions are met, the action is returned as a callable array.
     * If the action requires arguments, they are also returned.
     *
     * @return array|null An array containing the callable action and its arguments, or null if no action is specified or not defined.
     */
    private function getAction(): ?array
    {
        $action = _get('action');
        if ($action && isset($this->actions[$action])) {
            return [
                $this->actions[$action],
                $this->actionParams[$action] ?? null
            ];
        }

        return null;
    }

    /**
     * Resolve the component file paths.
     * Looks for the component files in multiple locations and naming conventions.
     *
     * @throws \RuntimeException When the component files cannot be found
     */
    private function resolveFiles(): void
    {
        $paths = [
            // _ds(_cr(), self::BASE_DIR, $this->name), # TODO: search in the current directory
            _ds(self::BASE_DIR, $this->name),
            _ds(self::BASE_DIR, str_replace('-', '_', $this->name)),
        ];

        foreach ($paths as $path) {
            $filePath = _i($path . '.php'); # /path/to/@components/{$name}.php
            if (is_file($filePath) && file_exists($filePath)) {
                # /path/to/@components/{$name}.php
                # /path/to/@components/{$name}.view.php
                $file = $filePath;
                $view = _i($path . '.view.php');
            } else {
                # /path/to/@components/{$name}/index.php
                # /path/to/@components/{$name}/view.php
                $file = _i(_ds($path, 'index.php'));
                $view = _i(_ds($path, 'view.php'));
            }

            if ($file && $view) {
                $this->file = $file;
                $this->view = $view;
                return;
            }
        }

        throw new \RuntimeException('The view component "' . $this->name . '" is missing.');
    }
}
