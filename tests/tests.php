<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Run all tests or a particular test. Renamed "tests.php" from "all_tests.php" as of 1.10.0
 * Command line syntax:
 *
 *  php tests/tests.php [options]
 *
 *  [options]
 *      -f, --file  Individual file name or group of file names separated by comma, for example
 *
 *      php tests/tests.php
 *      php tests/tests.php -f utility_helper
 *      php tests/tests.php --file=utility_helper
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.10.0
 * @copyright   Copyright (c), PHPLucidFrame.
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
        parent::__construct();

        $this->execute();
    }
}
