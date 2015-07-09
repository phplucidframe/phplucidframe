<?php
include( _i('inc/authenticate.php') );

$id = _arg(3);

if ($id) {
    $pageTitle = 'Edit User';
} else {
    $id = 0;
    $pageTitle = 'Add New User';
}

include('query.php');
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
    <title><?php echo _title($pageTitle); ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
</head>
<body>
    <?php include('view.php'); ?>
</body>
</html>
<script type="text/javascript">
$(function() {
    LC.Page.User.Setup.init();
});
</script>
