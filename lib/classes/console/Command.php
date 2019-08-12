<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class manages the process of a Command
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.11.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Console;

/**
 * This class manages the process of a Command
 */
class Command
{
    /** @var string The command name */
    protected $name;
    /** @var string The description for the command */
    protected $description;
    /** @var string The help tip for the command */
    protected $help;
    /** @var array The options for the command such as --help etc. */
    protected $options = array();
    /** @var array The short options of the long options defined for the command such as -h for --help, etc. */
    protected $shortcuts = array();
    /** @var array The arguments for the command */
    protected $arguments = array();
    /** @var array Array of the argument names */
    protected $argumentNames = array();
    /** @var closure Anonymous function that performs the job of the command */
    protected $definition;
    /** @var array Array of arguments passed to script */
    private $argv;
    /** @var array The parsed options from the command running */
    private $parsedOptions = array();
    /** @var array The parsed arguments from the command running */
    private $parsedArguments = array();
    /** @var string The longest option name */
    private $longestArgument = '';
    /** @var string The longest argument name */
    private $longestOption = '';

    /**
     * Constructor
     * @param string $name The command name
     */
    public function __construct($name)
    {
        $this->setName($name);
        $this->addOption('help', 'h', 'Display the help message', null, LC_CONSOLE_OPTION_NOVALUE);
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
     * @param string $name The option name without the prefix `--`, i.e,. `help` for `--help`
     * @param string $shortcut The short option name without the prefix `-`, i.e, `h` for `-h`
     * @param string $description A short description for the option
     * @param mixed $default The default value for the option
     * @param int $type A constant: LC_CONSOLE_OPTION_REQUIRED, LC_CONSOLE_OPTION_OPTIONAL, LC_CONSOLE_OPTION_NOVALUE
     *
     * @return object LucidFrame\Console\Command
     */
    public function addOption($name, $shortcut = null, $description = '', $default = null, $type = LC_CONSOLE_OPTION_OPTIONAL)
    {
        $name = ltrim($name, '--');
        $shortcut = ltrim($shortcut, '-');

        $this->options[$name] = array(
            'name'          => $name,
            'shortcut'      => $shortcut,
            'description'   => $description,
            'default'       => $default,
            'type'          => $type
        );

        $this->shortcuts[$shortcut] = $name;
        $this->parsedOptions[$name] = $default;

        $key = ($shortcut ? "-{$shortcut}, " : _indent(4)) . "--{$name}";
        $this->options[$name]['key'] = $key;
        if (strlen($key) > strlen($this->longestOption)) {
            $this->longestOption = $key;
        }

        return $this;
    }

    /**
     * Add an argument for the command
     *
     * @param string $name The argument name
     * @param string $description A short description for the argument
     * @param mixed $default The default value for the option
     *
     * @return object LucidFrame\Console\Command
     */
    public function addArgument($name, $description = '', $default = null)
    {
        $this->arguments[] = array(
            'name'          => $name,
            'description'   => $description,
            'default'       => $default,
        );
        $this->argumentNames[] = $name;

        if (strlen($name) > strlen($this->longestArgument)) {
            $this->longestArgument = $name;
        }

        return $this;
    }

    /**
     * Getter for $parsedArguments
     */
    public function getArguments()
    {
        return $this->parsedArguments;
    }

    /**
     * Getter for $parsedOptions
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
     * Get an option from the command
     *
     * @param string $name The option name without the prefix `--`, i.e,. `help` for `--help`
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->parsedOptions[$name]) ? $this->parsedOptions[$name] : null;
    }

    /**
     * Get an argument from the command
     *
     * @param string $name The argument name
     * @return mixed
     */
    public function getArgument($name)
    {
        return isset($this->parsedArguments[$name]) ? $this->parsedArguments[$name] : null;
    }

    /**
     * Getter for $parsedOptions
     */
    public function getParsedOptions()
    {
        return $this->parsedOptions;
    }

    /**
     * Getter for $parsedArguments
     */
    public function getParsedArguments()
    {
        return $this->parsedArguments;
    }

    /**
     * Reset default values to arguments and options
     */
    public function resetToDefaults()
    {
        foreach ($this->options as $name => $opt) {
            $this->parsedOptions[$name] = $opt['default'];
        }

        foreach ($this->arguments as $arg) {
            $this->parsedArguments[$arg['name']] = $arg['default'];
        }
    }

    /**
     * Run the command
     * @param array $argv Array of arguments passed to script
     * @return mixed
     */
    public function run($argv = array())
    {
        $this->parseArguments($argv);

        if ($this->getOption('help')) {
            $this->showHelp();
            return;
        }

        return call_user_func_array($this->definition, array($this));
    }

    /**
     * Display the help message
     * @return void
     */
    public function showHelp()
    {
        $options = $this->getOptions();

        if (count($options)) {
            _writeln('Usage:');
            $usage = _indent() . $this->name . ' [options]';

            if (count($this->arguments)) {
                $usage .= ' [<' . implode('>] [<', $this->argumentNames) . '>]';
            }

            _writeln($usage);

            # Arguments
            if (count($this->arguments)) {
                _writeln();
                _writeln('Arguments:');

                $table = new ConsoleTable();
                $table->hideBorder()->setPadding(2);
                foreach ($this->arguments as $arg) {
                    $table->addRow();
                    $table->addColumn($arg['name']);
                    $desc = $arg['description'];
                    if ($arg['default']) {
                        $desc .= ' [default: "' . $arg['default'] . '"]';
                    }
                    $table->addColumn($desc);
                }
                $table->display();
            }

            # Options
            if (count($options)) {
                _writeln();
                _writeln('Options:');

                $table = new ConsoleTable();
                $table->hideBorder()->setPadding(2);
                foreach ($this->options as $name => $opt) {
                    $table->addRow();
                    $table->addColumn($opt['key']);
                    $desc = $opt['description'];
                    if ($opt['default']) {
                        $desc .= ' [default: "' . $opt['default'] . '"]';
                    }
                    $table->addColumn($desc);
                }
                $table->display();
            }

            if ($this->description) {
                _writeln();
                _writeln('Help:');
                _writeln(_indent() . $this->description);
            }
        }
    }

    /**
     * Validate the option
     * @param string $name
     * @param string $type
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
     * @param array $argv Array of arguments passed to script
     * @return array
     */
    public function parseArguments($argv = array())
    {
        $this->argv = $argv;
        $this->resetToDefaults();
        $parsedArguments = array();

        foreach ($argv as $pos => $arg) {
            list($type, $name) = $this->getArgTypeAndName($pos);
            list($lastType, $lastName) = $this->getArgTypeAndName($pos - 1);

            $name = $this->validateOption($name, $type);
            if (!$name) {
                continue;
            }

            $a = explode('=', $arg);
            if (count($a) === 2) {
                // when there is '=' in the option
                $value = $a[1];
                if ($type === 'value') {
                    $parsedArguments[] = $value;
                } else {
                    $this->parsedOptions[$name] = $value;
                }
            } else {
                $value = $a[0];
                if ($type === 'value') {
                    if (in_array($lastType, array('shortopt', 'longopt')) && $lastName = $this->validateOption($lastName, $lastType)) {
                        if ($this->options[$lastName]['type'] === LC_CONSOLE_OPTION_NOVALUE) {
                            $parsedArguments[] = $value;
                        } elseif ($this->parsedOptions[$lastName] === true) {
                            $this->parsedOptions[$lastName] = $value;
                        } else {
                            $parsedArguments[] = $value;
                        }
                    } else {
                        $parsedArguments[] = $value;
                    }
                } else {
                    $this->parsedOptions[$name] = true;
                }
            }
        }

        foreach ($parsedArguments as $key => $value) {
            if (isset($this->arguments[$key])) {
                $name = $this->arguments[$key]['name'];
                $this->parsedArguments[$name] = $value;
            }
        }

        return array($this->parsedArguments, $this->parsedOptions);
    }

    /**
     * Console confirmation prompt
     * @param string $message The confirmation message
     * @param string|array $input The input to be allowed or to be checked against
     * @return boolean TRUE if it is passed; otherwise FALSE
     */
    public function confirm($message = 'Are you sure? Type "yes" or "y" to continue:', $input = array('yes', 'y'))
    {
        _write(trim($message) . ' ');

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $line = strtolower(trim($line));

        if (is_string($input) && $line == $input) {
            fclose($handle);
            return true;
        }

        if (is_array($input) && in_array($line, $input)) {
            fclose($handle);
            return true;
        }

        fclose($handle);
        return false;
    }
}
