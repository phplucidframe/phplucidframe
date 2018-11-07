<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe schema:load [options] [<db>]`
 *
 * Usage:
 *      schema:load [options] [<db>]
 *
 * Arguments:
 *      db      The database namespace defined in $lc_databases of config.php [default: "default"]
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\SchemaManager;

_consoleCommand('schema:load')
    ->setDescription('Process the schema and import the database')
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
            if ($cmd->confirm('The database will be purged. Type "y" or "yes" to continue:')) {
                $sm = new SchemaManager($schema, $db);
                if ($sm->import($db)) {
                    _writeln('Schema is successfully loaded. The database for "%s" has been imported.', $db);
                } else {
                    _writeln('No schema is loaded.');
                }
            } else {
                _writeln('Aborted.');
            }
        }
    })
    ->register();
