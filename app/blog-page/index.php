<?php
/**
 * The index.php (required) serves as the front controller for the requested page, 
 * initializing the base resources needed to run the page
 */
$get 	= _get($_GET);
$id 	= $get['id'];
$slug 	= $get['slug'];

include('query.php');

$pageTitle = $blog->title;
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php _title($pageTitle); ?></title>
	<?php include( _i('inc/head.php') ); ?>
</head>
<body>
	<?php include('view.php'); ?>
</body>
</html>