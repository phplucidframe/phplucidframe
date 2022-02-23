<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * The template file for the critical site errors display.
 * You can copy this to /app/inc/tpl/exception.php and update it according to your need.
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
    <title><?php echo $type; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="<?php echo _img('favicon.ico'); ?>" type="image/x-icon" />
    <?php _css('base.css'); ?>
    <link href="https://fonts.googleapis.com/css?family=Padauk:400,700" rel="stylesheet">
    <?php _css('base.' . _lang() . '.css'); ?>
    <?php _js('jquery'); ?>
</head>
<body class="mini-page">
    <div class="container-box exception-box">
        <div class="box">
            <div class="error-stacktrace">
                <div class="block-exception">
                    <p><?php echo nl2br(ucfirst($message)); ?></p>
                    <div class="type-status">
                        <strong><?php echo $type; ?></strong>
                        <div>HTTP status code: <?php echo _g('httpStatusCode'); ?></div>
                    </div>
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
    <script>
        $('html font').remove();
    </script>
</body>
</html>
