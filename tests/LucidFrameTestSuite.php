<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.13.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

class LucidFrameTestSuite extends \TestSuite
{
    protected $files;
    protected $target = array();

    public function __construct($target = null)
    {
        parent::__construct();

        switch($target) {
            case 'lib':
                $this->target[] = dirname(__FILE__) . '/lib/';
                break;

            case 'app':
                $this->target[] = dirname(__FILE__) . '/app/';
                break;

            default:
                $this->target[] = dirname(__FILE__) . '/lib/';
                $this->target[] = dirname(__FILE__) . '/app/';
                break;
        }
    }

    public function execute() {
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

        $this->files = array_map('trim', $files);
        if (count($files)) {
            foreach ($files as $fileName) {
                $fileName = $fileName.'.test.php';
                foreach ($this->target as $dir) {
                    $this->collect($dir, new \SimplePatternCollector('/'.$fileName.'/'));
                }
            }
        } else {
            foreach ($this->target as $dir) {
                $this->collect($dir, new \SimplePatternCollector('/.test.php/'));
            }
        }
    }
}
