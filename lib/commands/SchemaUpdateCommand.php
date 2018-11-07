<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe schema:update [<db>]`
 *
 * Usage:
 *      schema:update [<db>]
 *
 * Arguments:
 *      db      The database namespace defined in $lc_databases of config.php [default: "default"]
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.17.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://www.phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\SchemaManager;

_consoleCommand('schema:update')
    ->setDescription('Generates and executes the SQL to synchronize the database schema with the current mapping metadata.')
    ->addArgument('db', 'The database namespace defined in $lc_databases of config.php; if not provided $lc_defaultDbSource will be used.')
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $db = $cmd->getArgument('db');
        if (empty($db)) {
            $db = _cfg('defaultDbSource');
        }

        $schema = _schema($db);
        if ($schema === null) {
            _writeln('Failed to load schema.');
        } elseif ($schema === false) {
            _writeln('Unable to find the schema file "%s".', DB.'schema.'.$db.'.php');
        } else {
            if ($cmd->confirm('IMPORTANT! Backup your database before executing this command.'."\n".'Some of your data may be lost. Type "y" or "yes" to continue:')) {
                $sm = new SchemaManager($schema, $db);
                if ($sm->update($cmd, $db)) {
                    _writeln('Done.');
                } else {
                    _writeln();
                    _writeln('Nothing to update.');
                }
            } else {
                _writeln('Aborted.');
            }
        }
    })
    ->register();
