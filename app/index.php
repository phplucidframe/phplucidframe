<?php
/**
 * PHPLucidFrame : Simple, Lightweight & yet Powerfull PHP Application Framework
 * The request collector
 *
 * @package     PHPLucidFrame\App
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\Middleware;

require_once '../lib/bootstrap.php';

Middleware::runBefore();

ob_start('_flush');

$basename = basename(_app('page'), '.php');

if (in_array($basename, array('401', '403', '404'))) {
    _cfg('layoutMode', false);
}

if ($basename != 'view') {
    require _app('page');
}

if (_cfg('layoutMode') && _isAjax() === false) {
    if (_isHttpPost()) {
        $action = _ds(APP_ROOT, _cr(), 'action.php');
        if (is_file($action) && file_exists($action)) {
            require_once $action;
        }
    }

    $layout = _i(_ds('inc', 'tpl', _app('view')->layout . '.php'));
    if (is_file($layout) && file_exists($layout)) {
        $viewData = _app('view')->data;
        extract($viewData);
        require_once $layout;
    } else {
        _header(500);
        throw new \RuntimeException(sprintf('Layout file is missing: %s', _app('view')->layout . '.php'));
    }
}

ob_end_flush();

Middleware::runAfter();
