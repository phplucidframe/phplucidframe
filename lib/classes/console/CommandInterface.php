<?php

/**
 * This file is part of the PHPLucidFrame library.
 * This class manages the process of a Command
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 2.2.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Console;

interface CommandInterface
{
    /**
     * Execute the command
     * @param Command $cmd
     * @return void
     */
    public function execute(Command $cmd);
}
