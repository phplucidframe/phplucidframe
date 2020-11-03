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

final class App
{
    /**
     * @var Database
     */
    public static $db;
    /**
     * @var View
     */
    public static $view;
    /**
     * @var string
     */
    public static $page;
    /**
     * @var object
     */
    public static $auth;
}
