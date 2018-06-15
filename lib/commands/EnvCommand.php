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
    ->addArgument('env', 'The envirnoment name: [dev, development, staging, prod, production]')
    ->addOption('show', null, 'Display the current active environment setting', null, LC_CONSOLE_OPTION_NOVALUE)
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        if ($cmd->getOption('show')) {
            _writeln('%s environment is currently active.', ucfirst(_p('env')));
            exit;
        }

        $env = $cmd->getArgument('env');
        if (!in_array($env, array(ENV_DEV, ENV_STAGING, ENV_PROD, 'dev', 'prod'))) {
            _writeln('Wrong environment configuration. Use "dev", "staging", "prod", "%s" or "%s".', ENV_DEV, ENV_PROD);
            exit;
        }

        if ($env == 'dev') {
            $env = ENV_DEV;
        } elseif ($env == 'staging') {
            $env = ENV_STAGING;
        } elseif ($env == 'prod') {
            $env = ENV_PROD;
        }

        if (file_put_contents(ROOT . FILE_ENV, $env)) {
            _writeln('%s environment is now active.', ucfirst($env));
        } else {
            _writeln('failed to change env setting.');
        }
    })
    ->register();
