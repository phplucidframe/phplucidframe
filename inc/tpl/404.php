<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * The template file for 404 Not Found
 * You can copy this to /app/inc/tpl/404.php and update it according to your need.
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
    <title>404 <?php echo _cfg('siteName') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="<?php echo _img('favicon.ico'); ?>" type="image/x-icon" />
    <?php _css('base.css'); ?>
</head>
<body class="mini-page">
    <div class="container-box exception-box">
        <div class="box">
            <div class="error-stacktrace">
                <div class="block-exception">
                    <p>HTTP/1.0 404 Not Found</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

