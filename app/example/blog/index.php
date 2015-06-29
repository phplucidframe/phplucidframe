<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */
$pageTitle = _t('Blog (AJAX List & Pagination)');

_meta('description', $pageTitle.': '._cfg('metaDescription'));
?>
<!DOCTYPE html>
<html>
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
    LC.Page.Blog.init();
});
</script>
