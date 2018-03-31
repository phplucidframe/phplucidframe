<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe env [options]`
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.19.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

_consoleCommand('env')
    ->setDescription('Change environment setting')
    ->addArgument('env', 'The envirnoment name: development or production')
    ->addOption('show', null, 'Display the current active environment setting', null, LC_CONSOLE_OPTION_NOVALUE)
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        if ($cmd->getOption('show')) {
            _writeln('%s environment is currently active.', ucfirst(__env()));
            exit;
        }

        $env = $cmd->getArgument('env');
        if (!in_array($env, array(ENV_DEV, ENV_PROD, 'dev', 'prod'))) {
            _writeln('Wrong environment configuration. Use "dev", "prod", "%s", or "%s".', ENV_DEV, ENV_PROD);
            exit;
        }

        if ($env == 'dev') {
            $env = ENV_DEV;
        } elseif ($env == 'prod') {
            $env = ENV_PROD;
        }

        if (file_put_contents(ROOT . '.env', $env)) {
            _writeln('%s environment is now active.', ucfirst($env));
        } else {
            _writeln('failed to change env setting.');
        }
    })
    ->register();
