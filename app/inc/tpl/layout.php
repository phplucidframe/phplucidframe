<!DOCTYPE html>
<html>
<head>
    <title><?php echo _title(); ?></title>
    <?php include _i('inc/tpl/head.php'); ?>
</head>
<body>
    <?php include _i('inc/tpl/header.php'); ?>
    <?php _app('view')->load() ?>
    <?php include _i('inc/tpl/footer.php'); ?>
</body>
</html>
