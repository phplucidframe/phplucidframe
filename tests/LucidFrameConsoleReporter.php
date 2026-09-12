<?php
/**
 * This file is part of the PHPLucidFrame library.
 * A console reporter that displays "." for passing tests and "F" for failing ones.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

require_once VENDOR . 'simpletest/simpletest/src/reporter.php';

class LucidFrameConsoleReporter extends \TextReporter
{
    /**
     * Paints a passing test method.
     * Outputs a period character "." to indicate progress.
     *
     * @param string $message message is ignored
     *
     * @return void
     */
    public function paintPass($message)
    {
        parent::paintPass($message);
        echo '.';
        flush();
    }

    /**
     * Paints a failing test method.
     * Outputs an "F" to indicate failure.
     *
     * @param string $message failure message displayed in the context of the other tests
     *
     * @return void
     */
    public function paintFail($message)
    {
        parent::paintFail($message);
        echo 'F';
        flush();
    }

    /**
     * Paints the end of the test with a summary of the passes and failures.
     * Adds a linebreak before the summary for readability.
     *
     * @param string $test_name name class of test
     *
     * @return void
     */
    public function paintFooter($test_name)
    {
        echo "\n";
        parent::paintFooter($test_name);
    }
}
