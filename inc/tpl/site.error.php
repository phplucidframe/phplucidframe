<?php
/**
 * This file is part of the PHPLucidFrame library.
 *
 * The template file for the critical site errors display.
 * You can copy this to /app/inc/tpl/404.php and update it according to your need.
 *
 * @package     LC
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
	<title><?php echo _title('Site Error'); ?></title>
	<?php include( _i('inc/tpl/head.php') ); ?>
</head>
<body>
	<?php _msg($error->message, isset($error->type) ? $error->type  : 'error'); ?>
</body>
</html>
