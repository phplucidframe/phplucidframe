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
 * @package     LucidFrame\Test
 * @since       PHPLucidFrame v 1.10.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

require_once 'test_bootstrap.php';

class AllFileTests extends TestSuite
{
    protected $coreDir;
    protected $appDir;

    public function __construct()
    {
        parent::__construct();

        $this->coreDir = dirname(__FILE__) . '/core/';
        $this->appDir  = dirname(__FILE__) . '/app/';

        $files = array();
        $options = array();

        if (PHP_SAPI == 'cli') {
            $options = getopt('f:', array('file:'));
        } else {
            if (isset($_GET['f'])) {
                $options['f'] = $_GET['f'];
            }
            if (isset($_GET['file'])) {
                $options['file'] = $_GET['file'];
            }
        }

        if (isset($options['f'])) {
            $files += explode(',', $options['f']);
        }

        if (isset($options['file'])) {
            $files += explode(',', $options['file']);
        }

        $files = array_map('trim', $files);

        if (count($files)) {
            foreach ($files as $fileName) {
                $fileName = $fileName.'.test.php';

                if (file_exists($this->coreDir.$fileName)) {
                    $this->collect($this->coreDir, new SimplePatternCollector('/'.$fileName.'/'));
                }

                if (file_exists($this->appDir.$fileName)) {
                    $this->collect($this->appDir, new SimplePatternCollector('/'.$fileName.'/'));
                }
            }
        } else {
            $this->collect($this->coreDir, new SimplePatternCollector('/.test.php/'));
            $this->collect($this->appDir, new SimplePatternCollector('/.test.php/'));
        }
    }
}
