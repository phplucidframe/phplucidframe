<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe list [options]`
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.12.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

_consoleCommand('list')
    ->setDescription('List all available commands')
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $cmd->showHelp();
        _writeln();

        _writeln('Available commands:');
        $table      = _consoleTable();
        $commands   = _consoleCommands();
        foreach ($commands as $name => $command) {
            $table->addRow()
                ->addColumn($name)
                ->addColumn($command->getDescription());
        }
        $table->hideBorder();
        $table->setPadding(2);
        $table->display();
    })
    ->register();
