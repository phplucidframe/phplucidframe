<?php
include( _i('inc/authenticate.php') );

$pageTitle = _t('Categories');
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
    <title><?php echo _title($pageTitle); ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
    <?php _css('base.my.css'); ?>
</head>
<body>
    <?php include('view.php'); ?>
</body>
</html>
<script language="javascript">
$(function() {
    LC.Page.Category.init();
});
</script>
