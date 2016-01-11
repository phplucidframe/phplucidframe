<?php
use LucidFrame\Console\Console;

/**
 * Unit Test for Console & Command
 */
class ConsoleCommandTestCase extends LucidFrameTestCase
{
    public function testSecretGenerateCommand()
    {
        $name = 'secret:generate';
        $commands = Console::getCommands();

        $this->assertTrue(isset($commands[$name]));

        $cmd = $commands[$name];

        list($args, $opts) = $cmd->parseArguments(array('--method', 'sha1', '--data', 'hello'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('--method=sha1', '--data=hello'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('-m', 'sha1', '--data=hello'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('-m', 'sha1', '-d', 'hello'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('-m=sha1', '-d=hello'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('-m=sha1', '--data=hello world'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => 'hello world'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('--data=hello world'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'md5',
            'data'   => 'hello world'
        ));
        $this->assertEqual($args, array());

        list($args, $opts) = $cmd->parseArguments(array('--method=sha1'));
        $this->assertEqual($opts, array(
            'help'   => null,
            'method' => 'sha1',
            'data'   => null
        ));
        $this->assertEqual($args, array());
    }
}
