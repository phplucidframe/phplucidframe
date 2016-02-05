<?php
/**
 * This file is part of the PHPLucidFrame library.
 * It initializes and bootstraps LucidFrame test environment
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.10.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

if (!defined('TEST')) {
    if (PHP_SAPI !== 'cli') {
        chdir('../');
    }
    require_once 'bootstrap.php';
}

require_once VENDOR . 'simpletest/simpletest/autorun.php';
require_once TEST_DIR . 'LucidFrameTestCase.php';
