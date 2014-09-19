<?php
include( _i('inc/authenticate.php') );

$pageTitle = _t('Categories');
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
	<title><?php echo _title($pageTitle); ?></title>
	<?php include( _i('inc/head.php') ); ?>
</head>
<body>
	<?php include('view.php'); ?>
</body>
</html>
<script language="javascript">
$(function(){
	Page.Category.init();
});
</script>