<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Include this file to initialize and bootstrap LucidFrame environment
 * if you want a script to run standalone outside LucidFrame application root.
 * It expects the caller changed the directory to the LucidFrame root before including this script.
 *
 * @package     LC
 * @since       PHPLucidFrame v 1.3.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

define('APP_DIR', 'app'); # LC app root
$ROOT = rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; # LC root
chdir(APP_DIR); # change to the app root
require_once($ROOT . 'lib/bootstrap.php');
