<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe schema:export [options] [<db>]`
 *
 * Usage:
 *      schema:export [options] [<db>]
 *
 * Arguments:
 *      db      The database namespace defined in $lc_databases of config.php [default: "default"]
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\SchemaManager;

_consoleCommand('schema:export')
    ->setDescription('Process the schema and export the database sql dump file')
    ->addArgument('db', 'The database namespace defined in $lc_databases of config.php', 'default')
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $db = $cmd->getArgument('db');

        if ($db === 'default') {
            $files = array(DB."schema.{$db}.php", DB."schema.php");
        } else {
            $files = array(DB."schema.{$db}.php");
        }

        $file = null;
        foreach ($files as $f) {
            if (is_file($f) && file_exists($f)) {
                $file = $f;
                break;
            }
        }

        if (!$file) {
            _writeln('Failed to load schema.');
            _writeln('Unable to find the schema file "%s".', $f);
        } else {
            $schema = include_once($file);
            $sm = new SchemaManager($schema);
            if ($sm->export($db)) {
                _writeln('"%s" has been exported.', str_replace('.php', '.sql', $file));
            } else {
                _writeln('No sql file is exported.');
            }
        }
    })
    ->register();
