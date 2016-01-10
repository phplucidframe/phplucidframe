<?php
/**
 * This file is part of the PHPLucidFrame library.
 * The script executes the command `php lucidframe secret:generate`
 *
 * @package     LucidFrame\Console
 * @since       PHPLucidFrame v 1.11.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

_consoleCommand('secret:generate')
    ->setDescription('Generate a secret hash key')
    ->addOption('method', 'm', 'The hashing algorithm method (e.g. "md5", "sha256", etc..)', 'md5')
    ->addOption('data', 'd', 'Secret text to be hashed.')
    ->setDefinition(function(\LucidFrame\Console\Command $cmd) {
        $data = $cmd->getOption('data');
        if (!$data) {
            $data = time();
        }

        $secret = hash($cmd->getOption('method'), $data) . "\n";
        $file = INC . '.secret';
        file_put_contents($file, $secret);
    })
    ->register();
