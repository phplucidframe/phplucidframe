<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * @package     LC\tests
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

if (PHP_SAPI == 'cli') {
    require_once 'bootstrap.php';
}

require_once VENDOR . 'simpletest/autorun.php';

class AllFileTests extends TestSuite
{
    protected $coreDir;
    protected $appDir;

    public function __construct()
    {
        parent::__construct();

        $this->coreDir = dirname(__FILE__) . '/core/';
        $this->appDir  = dirname(__FILE__) . '/app/';

        $this->collect($this->coreDir, new SimplePatternCollector('/.test.php/'));
        $this->collect($this->appDir, new SimplePatternCollector('/.test.php/'));
    }
}
