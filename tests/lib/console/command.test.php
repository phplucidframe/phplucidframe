<?php

namespace LucidFrame\Test;

use LucidFrame\Console\Command;
use LucidFrame\Console\Console;
use LucidFrame\Console\CommandInterface;

/**
 * Unit Test for Command class (lib/classes/console/Command.php)
 * Covers the fluent setter API, options/arguments, argument parsing,
 * default resets, the run/dispatch and the help output.
 * CommandInterface is exercised through a minimal stub implementation.
 */
class CommandTest extends LucidFrameTestCase
{
    public function testForConstructorAddsHelpOption()
    {
        $cmd = new Command('lc-test-command');

        $this->assertEqual($cmd->getName(), 'lc-test-command');

        $options = $cmd->getOptions();
        $this->assertArrayHasKey('help', $options);
        $this->assertEqual($options['help'], null);
    }

    public function testForSetNameAndDescription()
    {
        $cmd = (new Command('orig'))
            ->setName('renamed')
            ->setDescription('A test command')
            ->setHelp('Run me.');

        $this->assertEqual($cmd->getName(), 'renamed');
        $this->assertEqual($cmd->getDescription(), 'A test command');
        $this->assertEqual($cmd->getHelp(), 'Run me.');
    }

    public function testForAddOption()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('force', 'f', 'Force it', false, LC_CONSOLE_OPTION_NOVALUE);

        $options = $cmd->getOptions();
        $this->assertArrayHasKey('force', $options);
        $this->assertEqual($options['force'], false);

        $cmd->addOption('name', 'n', 'Name', 'default-name');
        $options = $cmd->getOptions();
        $this->assertEqual($options['name'], 'default-name');
    }

    public function testForAddArgument()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addArgument('env', 'The environment');
        $cmd->addArgument('db', 'The database namespace', 'lc-test-db');
        $cmd->resetToDefaults();

        $args = $cmd->getArguments();
        $this->assertEqual($args['env'], null);
        $this->assertEqual($args['db'], 'lc-test-db');
    }

    public function testForResetToDefaults()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('verbose', 'v', '', false);
        $cmd->addArgument('target', 'The target', 'all');

        list($args, $options) = $cmd->parseArguments(array('--verbose=1', 'sometarget'));

        $this->assertEqual($options['verbose'], '1');
        $this->assertEqual($args['target'], 'sometarget');

        $cmd->resetToDefaults();

        $this->assertEqual($cmd->getOption('verbose'), false);
        $this->assertEqual($cmd->getArgument('target'), 'all');
    }

    public function testForParseArgumentsWithLongOption()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('name', 'n', 'Name');
        $cmd->addOption('force', 'f', 'Force', false, LC_CONSOLE_OPTION_NOVALUE);

        list($args, $options) = $cmd->parseArguments(array('--name=John', '--force'));

        $this->assertEqual($options['name'], 'John');
        $this->assertEqual($options['force'], true);
    }

    public function testForParseArgumentsWithShortOption()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('name', 'n', 'Name');

        list($args, $options) = $cmd->parseArguments(array('-n', 'Jane'));

        $this->assertEqual($options['name'], 'Jane');
    }

    public function testForParseArgumentsWithPositionalValues()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addArgument('first', 'First argument');
        $cmd->addArgument('second', 'Second argument');

        list($args, $options) = $cmd->parseArguments(array('one', 'two'));

        $this->assertEqual($args['first'], 'one');
        $this->assertEqual($args['second'], 'two');
    }

    public function testForRunWithClosureDefinition()
    {
        $cmd = new Command('lc-test-command');
        $received = null;
        $cmd->setDefinition(function (Command $c) use (&$received) {
            $received = $c;
        });

        $result = $cmd->run(array());

        $this->assertTrue($received === $cmd);
        $this->assertNull($result);
    }

    public function testForRunWithStringDefinition()
    {
        StubExecutableCommand::$executed = false;
        $cmd = new Command('lc-test-command');
        $cmd->setDefinition('LucidFrame\Test\StubExecutableCommand');

        $result = $cmd->run(array());

        $this->assertEqual($result, true);
        $this->assertTrue(StubExecutableCommand::$executed, 'The stub command must have been executed');
    }

    public function testForRunShowsHelpOnHelpOption()
    {
        $cmd = new Command('lc-test-command');
        $cmd->setDefinition(function () {
            // must not run
        });

        ob_start();
        $result = $cmd->run(array('--help'));
        $output = ob_get_clean();

        $this->assertEqual($result, true);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('lc-test-command', $output);
    }

    public function testForGetOptionRequiredWritesMessage()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('mode', 'm', 'Mode', null, LC_CONSOLE_OPTION_REQUIRED);

        ob_start();
        $value = $cmd->getOption('mode');
        $output = ob_get_clean();

        $this->assertNull($value);
        $this->assertStringContainsString('is required', $output);
    }

    public function testForGetParsedGetters()
    {
        $cmd = new Command('lc-test-command');
        $cmd->addOption('verbose', 'v', '', false);
        $cmd->addArgument('target', 'The target', 'all');

        list($args, $options) = $cmd->parseArguments(array('--verbose=1'));

        $this->assertEqual($cmd->getParsedOptions(), $options);
        $this->assertEqual($cmd->getParsedArguments(), $args);
    }

    public function testForRegister()
    {
        $cmd = new Command('lc_test_registered_command_xyz');
        $cmd->setDescription('Registered during a unit test');

        $cmd->register();

        $registered = Console::getCommands();
        $this->assertArrayHasKey('lc_test_registered_command_xyz', $registered);
        $this->assertTrue($registered['lc_test_registered_command_xyz'] === $cmd);

        ob_start();
        $console = new Console();
        ob_end_clean();
        $this->assertTrue($console->hasCommand('lc_test_registered_command_xyz'));
        $this->assertTrue($console->getCommand('lc_test_registered_command_xyz') === $cmd);
        $this->assertFalse($console->hasCommand('no_such_command_xyz'));
    }

    public function testForCommandInterfaceImplementedByStub()
    {
        $this->assertTrue(is_subclass_of('LucidFrame\Test\StubExecutableCommand', CommandInterface::class));
        $this->assertTrue(method_exists('LucidFrame\Test\StubExecutableCommand', 'execute'));
    }
}

/**
 * Minimal CommandInterface implementation used by CommandTest.
 */
class StubExecutableCommand implements CommandInterface
{
    /** @var bool */
    public static $executed = false;

    public function execute(Command $cmd)
    {
        self::$executed = true;
    }
}