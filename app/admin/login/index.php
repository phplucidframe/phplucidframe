<?php
auth_prerequisite();
$pageTitle = _t('Sign In');
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
    <title><?php echo _title($pageTitle); ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
</head>
<body class="mini-page">
    <?php include('view.php'); ?>
</body>
</html>
