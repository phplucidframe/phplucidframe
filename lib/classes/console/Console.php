<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Command-line code generation utility to automate programmer tasks.
 *
 * @package     LucidFrame\Console
 * @since       PHPLucidFrame v 1.11.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

namespace LucidFrame\Console;

class Console
{
    /** @var LucidFrame\Console\Command The command being run **/
    protected $command;
    /** @var string The command name **/
    private $commandName;
    /** @var integer No of arguments passed to script **/
    protected $argc;
    /** @var array Array of arguments passed to script **/
    protected $argv;
    /** @var array list of registered commands **/
    protected static $commands = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        global $argv;

        $this->argv = array_slice($argv, 1);
        $this->argc = count($this->argv) - 1;
        $this->commandName  = array_shift($this->argv);
        $this->command      = $this->getCommand($this->commandName);

        $this->execute();
    }

    /**
     * Register a command
     * @param LucidFrame\Console\Command $command
     * @return void
     */
    public static function registerCommand(\LucidFrame\Console\Command $command)
    {
        self::$commands[$command->getName()] = $command;
    }

    /**
     * Get all registered commands
     * @return array Array of LucidFrame\Console\Command
     */
    public static function getCommands()
    {
        return self::$commands;
    }

    /**
     * Check a command name is already registered
     * @param string $name The command name
     * @return boolean
     */
    public function hasCommand($name)
    {
        return array_key_exists($name, self::$commands);
    }

    /**
     * Get the command by name
     * @param string $name The command name
     * @return object|null LucidFrame\Console\Command
     */
    public function getCommand($name)
    {
        return $this->hasCommand($name) ? self::$commands[$name] : null;
    }

    /**
     * Execute the current command
     */
    private function execute()
    {
        _writeln('PHPLucidFrame %s by Sithu K.', _version());
        _writeln();

        if ($this->command instanceof \LucidFrame\Console\Command) {
            $this->command->run($this->argv);
        } else {
            if (!$this->command && $this->commandName && !in_array($this->commandName, array('-V', '--version'))) {
                _writeln('Command "%s" not found.', $this->commandName);
            } else {
                if (empty($this->command) || in_array($this->command, array('-V', '--version'))) {
                    _writeln(_version());
                    _writeln('PHP Version: %s', phpversion());
                    _writeln('The MIT License');
                    _writeln('Simple, lightweight & yet powerful PHP Application Framework');
                    _writeln('Copyright (c) 2014-%d, PHPLucidFrame.', date('Y'));
                } else {
                    _writeln('Command "%s" not found.', $this->commandName);
                }
            }
        }
    }
}
