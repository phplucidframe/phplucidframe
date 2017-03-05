<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe schema:build [options] [<db>]`
 *
 * Usage:
 *      schema:build [options] [<db>]
 *
 * Arguments:
 *      db      The database namespace defined in $lc_databases of config.php [default: "default"]
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.16.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\SchemaManager;

_consoleCommand('schema:build')
    ->setDescription('Build the schema in /db/build/')
    ->addArgument('db', 'The database namespace defined in $lc_databases of config.php', 'default')
    ->addOption('backup', null, 'Create a backup file', null, LC_CONSOLE_OPTION_NOVALUE)
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $db = $cmd->getArgument('db');
        $backupOption = (bool) $cmd->getOption('backup');

        $schema = _schema($db);
        if ($schema === null) {
            _writeln('Failed to load schema.');
        } elseif ($schema === false) {
            _writeln('Unable to find the schema file "%s".', DB.'schema.'.$db.'.php');
        } else {
            $sm = new SchemaManager($schema);
            if ($sm->build($db, $backupOption)) {
                _writeln('The schema has been build at "db/build/schema.%s.inc".', $db);
            } else {
                _writeln('No schema is loaded.');
            }
        }
    })
    ->register();
