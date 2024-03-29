<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe db:seed [options] [<db>]`
 *
 * Usage:
 *      db:seed [options] [<db>]
 *
 * Arguments:
 *      db      The database namespace defined in $lc_databases of config.php [default: "default"]
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\Seeder;

_consoleCommand('db:seed')
    ->setDescription('Initial seeding of your database with default data or sample data')
    ->addArgument('db', 'The database namespace defined in $lc_databases of config.php; if not provided $lc_defaultDbSource will be used.')
    ->addOption('entity', null, 'Optional comma-separated list of entity names to be executed', null, LC_CONSOLE_OPTION_OPTIONAL)
    ->setDefinition(function (\LucidFrame\Console\Command $cmd) {
        $db = $cmd->getArgument('db');
        if (empty($db)) {
            $db = _cfg('defaultDbSource');
        }

        if ($cmd->confirm('The seeding tables will be purged. Type "y" or "yes" to continue:')) {
            $names = $cmd->getOption('entity');
            $entities = $names ? explode(',', $names) : array();

            $seeder = new Seeder($db);
            if ($seeder->run($entities)) {
                _writeln('Seeded for "%s".', $db);
            } else {
                _writeln('Not seeded.');
            }
        } else {
            _writeln('Aborted.');
        }

    })
    ->register();
