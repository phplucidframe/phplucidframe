<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Run all tests or a particular test for core libraries
 * Command line syntax:
 *
 *  php tests/lib.php [options]
 *
 *  [options]
 *      -f, --file  Individual file name or group of file names separated by comma, for example
 *
 *      php tests/lib.php
 *      php tests/lib.php -f utility_helper
 *      php tests/lib.php --file=utility_helper
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

require_once 'test_bootstrap.php';

class AllFileTests extends \LucidFrame\Test\LucidFrameTestSuite
{

    public function __construct()
    {
        parent::__construct('lib');

        $this->execute();
    }
}
