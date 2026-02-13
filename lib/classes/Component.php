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
     * Render a UI component located under the base @components directory.
     * Resolves the component by name under "@components" using one of these layouts:
     *  - "@components/{name}.php" + "@components/{name}.view.php"
     *  - "@components/{name}/index.php" + "@components/{name}/view.php"
     *  The lookup also tries an underscore variant of the name (hyphens replaced with underscores).
     * - Merges auto-generated HTML attributes into the provided `$data` as `$data['attributes']`.
     *
     * @param string $name   The component name (with or without ".php").
     * @param array  $data   Variables to be extracted and available in the component
     * @param bool   $return When true, returns the rendered HTML as a string; otherwise echoes it.
     *
     * @return false|string|void Returns the HTML string when `$return` is true; otherwise echoes the content
     *
     * @throws \RuntimeException When the component files cannot be found.
     */
    public static function render(string $name, array $data = array(), bool $return = false)
    {
        $name = str_replace('.php', '', $name);
        $paths = [
            // _ds(_cr(), self::BASE_DIR, $name), # TODO: search in the current directory
            _ds(self::BASE_DIR, $name),
            _ds(self::BASE_DIR, str_replace('-', '_', $name)),
        ];

        foreach ($paths as $path) {
            $filePath = _i($path . '.php'); # /path/to/@components/{$name}.php
            $file = $view = '';
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
                $data['attributes'] = self::getAttributes($name);
                self::saveData($name, $data);

                extract($data);

                ob_start();
                include $file;
                echo '<div id="lc_component_' . $name . '">';
                include $view;
                echo '</div>';
                $content = ob_get_clean();
                if ($return) {
                    return $content;
                }

                echo $content;
                return;
            }
        }

        throw new \RuntimeException('The view component "' . $name . '" is missing.');
    }

    /**
     * Retrieve the previously saved data for a component.
     * The data is kept in the session and automatically cleared when the
     * current request is not a component refresh (i.e. when the URL does not
     * contain the base component directory).
     *
     * @param string $name The component name
     * @return array The saved component data or an empty array when none
     */
    public static function getData(string $name): array
    {
        if (!self::refreshed()) {
            session_delete(self::BASE_DIR . '_' . $name);
            return [];
        }

        return session_get(self::BASE_DIR . '_' . $name, true);
    }

    /**
     * Persist arbitrary data for a component into session storage.
     * The data will be available to the component on its subsequent render
     * (typically during an AJAX refresh triggered by the component route).
     *
     * @param string $name The component name
     * @param array $data The data to store in session
     * @return void
     */
    public static function saveData(string $name, array $data = []): void
    {
        session_set(self::BASE_DIR . '_' . $name, $data, true);
    }

    /**
     * Determine whether the current request is for a component refresh.
     * It checks if the current route contains the component base directory
     * segment ("@components").
     *
     * @return bool True if the request targets a component, false otherwise
     */
    public static function refreshed(): bool
    {
        return str_contains(_r(), self::BASE_DIR);
    }

    /**
     * Build default HTML attributes for the root element of a component.
     * Produces a string like: id="name" data-name="name".
     *
     * @param string $name The component name
     * @return string Space-separated HTML attributes
     */
    protected static function getAttributes(string $name): string
    {
        $attributes = ['id' => $name, 'data-name' => $name];
        $attributes = array_map(static function ($k, $v) { return "$k=\"$v\""; }, array_keys($attributes), array_values($attributes));

        return implode(' ', $attributes);
    }
}
