<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe secret:generate [options]`
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

_consoleCommand('secret:generate')
    ->setDescription('Generate a secret key')
    ->addOption('show', 's', 'Display the generated secret key', null, LC_CONSOLE_OPTION_NOVALUE)
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $secret = _randomCode(32);
        $file = INC . '.secret';
        file_put_contents($file, $secret . PHP_EOL);
        if ($cmd->getOption('show')) {
            _writeln($secret);
        }
    })
    ->register();
