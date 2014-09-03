<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
	<title><?php echo _title('Site Error'); ?></title>
	<?php include( _i('inc/head.php') ); ?>
</head>
<body>
	<?php _msg($error->message, isset($error->type) ? $error->type  : 'error'); ?>
</body>
</html>