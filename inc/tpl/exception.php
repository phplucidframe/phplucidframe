<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * The template file for the critical site errors display.
 * You can copy this to /app/inc/tpl/site.error.php and update it according to your need.
 *
 * @package     PHPLucidFrame\App
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
    <title><?php echo $type . ': ' . $message; ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
</head>
<body class="mini-page">
    <div class="container-box exception-box">
        <div class="box">
            <div class="logo"><img src="<?php echo _img('logo.png'); ?>" /></div>
                <div class="error-stacktrace">
                    <div class="block-exception">
                        <p><?php echo nl2br(ucfirst($message)); ?></p>
                        <strong><?php echo $type; ?></strong>
                        <div>HTTP status code: <?php echo _g('httpStatusCode'); ?></div>
                    </div>
                    <div class="block-stacktrace">
                        <h5>Stack Trace</h5>
                        <ol>
                            <li>in <code class="inline"><?php echo $file; ?></code> at line <?php echo $line; ?></li>
                            <?php foreach($trace as $item) { ?>
                            <li>
                                <?php if (isset($item['file'])) { ?>
                                    <code class="inline"><?php echo $item['file']; ?></code>
                                <?php } ?>
                                <?php if (isset($item['line'])) { ?>
                                    at line <?php echo $item['line']; ?>
                                <?php } ?>
                                calling <strong><code class="inline"><?php echo $item['function']; ?>()</code></strong>
                            </li>
                            <?php } ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
