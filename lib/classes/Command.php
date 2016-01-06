<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class manages the process of a Command
 *
 * @package     LC\Helpers\Console
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

class Command
{
    /** @var string The command name **/
    protected $name;
    /** @var string The description for the command **/
    protected $description;
    /** @var string The help tip for the command **/
    protected $help;
    /** @var array The options for the command such as --help etc. **/
    protected $options = array();
    /** @var array The short options of the long options defined for the command such as -h for --help, etc. **/
    protected $shortcuts = array();
    /** @var closure Anonymous function that performs the job of the command **/
    protected $definition;
    /** @var array Array of arguments passed to script **/
    private $argv;
    /** @var array The parsed options from the command running **/
    private $parsedOptions = array();
    /** @var array The parsed arguments from the command running **/
    private $parsedArguments = array();

    /**
     * Constructor
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Setter for $name
     * @param string $name The command name
     * @return object LucidFrame\Console\Command
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Getter for $name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for $description
     * @param string $description The description for the command
     * @return object LucidFrame\Console\Command
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Setter for $description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Setter for $help
     * @param string $help The help tip for the command
     * @return object LucidFrame\Console\Command`
     */
    public function setHelp($help = null)
    {
        $this->help = $help;

        return $this;
    }

    /**
     * Setter for $help
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * Add an option for the command
     *
     * @param string $name          The option name without the prefix `--`, i.e,. `help` for `--help`
     * @param string $shortcut      The short option name without the prefix `-`, i.e, `h` for `-h`
     * @param string $description   A short description for the option
     * @param mixed  $default       The default value for the option
     * @param int    $type          A constant: LC_CONSOLE_OPTION_REQUIRED, LC_CONSOLE_OPTION_OPTIONAL, LC_CONSOLE_OPTION_NOVALUE
     *
     * @return object LucidFrame\Console\Command
     */
    public function addOption($name, $shortcut = null, $description = '', $default = null, $type = LC_CONSOLE_OPTION_NOVALUE)
    {
        $name = ltrim($name, '--');

        $this->options[$name] = array(
            'name'      => $name,
            'shortcut'  => $shortcut,
            'default'   => $default,
            'type'      => $type
        );

        $this->shortcuts[$shortcut] = $name;
        $this->parsedOptions[$name] = $default;

        return $this;
    }

    /**
     * Getter for $options
     */
    public function getOptions()
    {
        return $this->parsedOptions;
    }

    /**
     * Setter for $definition
     * @param closure $function Anonymous function that performs the job of the command
     * @return object LucidFrame\Console\Command`
     */
    public function setDefinition($function)
    {
        $this->definition = $function;

        return $this;
    }

    /**
     * Register a command
     * @return object LucidFrame\Console\Command`
     */
    public function register()
    {
        Console::registerCommand($this);

        return $this;
    }

    /**
     * Get the option from the command
     *
     * @param string $name The option name without the prefix `--`, i.e,. `help` for `--help`
     * @return object LucidFrame\Console\Command
     */
    public function getOption($name)
    {
        return isset($this->parsedOptions[$name]) ? $this->parsedOptions[$name] : null;
    }

    /**
     * Run the command
     * @param  array $argv Array of arguments passed to script
     * @return mixed
     */
    public function run($argv = array())
    {
        $this->parseArguments($argv);
        return call_user_func_array($this->definition, array($this));
    }

    /**
     * Validate the option
     * @param  string $name
     * @param  string $type
     * @return string|boolean
     */
    private function validateOption($name, $type)
    {
        if (!in_array($type, array('shortopt', 'longopt'))) {
            return $name;
        }

        if ($type === 'longopt') {
            return isset($this->options[$name]) ? $name : false;
        }

        if ($type === 'shortopt') {
            return isset($this->shortcuts[$name]) ? $this->shortcuts[$name] : false;
        }
    }

    /**
     * Get the argument type and name
     * @param integer $pos The position of argument
     * @return array
     *
     *      array(
     *          $type,  // longopt, shortopt or value
     *          $name   // the name without prefix `--` or `-`
     *      )
     */
    private function getArgTypeAndName($pos)
    {
        if (isset($this->argv[$pos])) {
            $arg = $this->argv[$pos];
        } else {
            return array(null, null);
        }

        $a = explode('=', $arg);

        if (substr($a[0], 0, 2) === '--') {
            $type = 'longopt';
            $name = ltrim($a[0], '--');
        } elseif (substr($a[0], 0, 1) === '-') {
            $type = 'shortopt';
            $name = ltrim($a[0], '-');
        } else {
            $type = 'value';
            $name = $a[0];
        }

        return array($type, $name);
    }

    /**
     * Parse the arguments for the command
     * @param  array $argv Array of arguments passed to script
     * @return array
     */
    private function parseArguments($argv = array())
    {
        $this->argv = $argv;

        foreach ($argv as $pos => $arg) {
            list($type, $name) = $this->getArgTypeAndName($pos);
            list($lastType, $lastName) = $this->getArgTypeAndName($pos-1);

            $name = $this->validateOption($name, $type);
            if (!$name) {
                continue;
            }

            $a = explode('=', $arg);
            if (count($a) === 2) {
                // when there is '=' in the option
                if ($type === 'value') {
                    $this->parsedArguments[] = $a[1];
                } else {
                    $this->parsedOptions[$name] = $a[1];
                }
            } else {
                if ($type === 'value') {
                    if (in_array($lastType, array('shortopt', 'longopt')) && $lastName = $this->validateOption($lastName, $lastType)) {
                        $this->parsedOptions[$lastName] = $a[0];
                    } elseif ($lastType === 'value') {
                        $this->parsedArguments[] = $a[0];
                    } else {
                        continue;
                    }
                } else {
                    $this->parsedOptions[$name] = true;
                }
            }
        }

        return array(
            'options'   => $this->parsedOptions,
            'arguments' => $this->parsedArguments
        );
    }
}
