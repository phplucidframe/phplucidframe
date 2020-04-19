<?php

use LucidFrame\Console\Console;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for Console & Command
 */
class ConsoleCommandTestCase extends LucidFrameTestCase
{
    public function testCommands()
    {
        $list = array(
            'db:seed',
            'env',
            'list',
            'schema:build',
            'schema:diff',
            'schema:export',
            'schema:load',
            'schema:update',
            'secret:generate',
        );

        $commands = Console::getCommands();

        foreach ($list as $name) {
            $this->assertTrue(isset($commands[$name]));
        }
    }
}
