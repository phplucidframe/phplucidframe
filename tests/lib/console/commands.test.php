<?php

namespace LucidFrame\Test;

use LucidFrame\Console\Command;
use LucidFrame\Console\Console;

/**
 * Unit Test for the built-in commands under lib/commands/
 * Checks the registry completeness against Console::getCommands() and the
 * safe argument/option definitions of each command. The command definitions
 * are NOT executed (they interact with the database / file system); only
 * their registration metadata and parsing rules are verified here.
 */
class CommandsTest extends LucidFrameTestCase
{
    /** @var array The names of all 9 built-in commands */
    private $builtInCommands = array(
        'list',
        'env',
        'db:seed',
        'schema:build',
        'schema:diff',
        'schema:export',
        'schema:load',
        'schema:update',
        'secret:generate',
    );

    public function testForRegistryContainsAllBuiltInCommands()
    {
        $commands = Console::getCommands();

        foreach ($this->builtInCommands as $name) {
            $this->assertArrayHasKey($name, $commands, "Command '{$name}' must be registered");
            $this->assertIsA($commands[$name], Command::class);
        }
    }

    public function testForEveryCommandHasADescription()
    {
        $commands = Console::getCommands();

        foreach ($this->builtInCommands as $name) {
            $description = $commands[$name]->getDescription();

            $this->assertIsString($description, "Command '{$name}' must have a description");
            $this->assertNotEqual(trim($description), '', "Command '{$name}' must have a non-empty description");
        }
    }

    public function testForEveryCommandHasHelpOption()
    {
        $commands = Console::getCommands();

        foreach ($this->builtInCommands as $name) {
            $options = $commands[$name]->getOptions();
            $this->assertArrayHasKey('help', $options, "Command '{$name}' must have the --help option");
        }
    }

    public function testForEnvCommandDefinition()
    {
        $cmd = Console::getCommands()['env'];

        // Safe option/argument validation without executing the definition
        $cmd->resetToDefaults();
        list($args, $options) = $cmd->parseArguments(array('--show'));

        $this->assertEqual($options['show'], true);
        // The 'env' argument keeps its (null) default
        $this->assertNull($args['env']);
    }

    public function testForSchemaBuildCommandDefinition()
    {
        $cmd = Console::getCommands()['schema:build'];

        $cmd->resetToDefaults();
        list($args, $options) = $cmd->parseArguments(array('sample', '--backup'));

        $this->assertEqual($args['db'], 'sample');
        $this->assertEqual($options['backup'], true);
    }

    public function testForDbSeedCommandDefinition()
    {
        $cmd = Console::getCommands()['db:seed'];

        $cmd->resetToDefaults();
        list($args, $options) = $cmd->parseArguments(array('sample', '--entity=post,user'));

        $this->assertEqual($args['db'], 'sample');
        $this->assertEqual($options['entity'], 'post,user');
    }

    public function testForSchemaLoadCommandDefinition()
    {
        $cmd = Console::getCommands()['schema:load'];

        $cmd->resetToDefaults();
        list($args, $options) = $cmd->parseArguments(array('sample'));

        $this->assertEqual($args['db'], 'sample');
    }

    public function testForSecretGenerateCommandIsRegistered()
    {
        $cmd = Console::getCommands()['secret:generate'];

        $this->assertIsA($cmd, Command::class);
        $this->assertStringContainsString('secret', strtolower($cmd->getDescription()));
    }
}