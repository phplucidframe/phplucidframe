<?php
include( _i('inc/authenticate.php') );

$lang       = _getLang();
$pageTitle  = _t('Add New Post');
$id         = 0;
$id         = _arg(3);
if ($id) {
    $pageTitle = _t('Edit Post');
} else {
    if ($lang != _defaultLang()) {
        _redirect('admin/post/setup/', null, _defaultLang());
    }
}

include('query.php');
?>
<!DOCTYPE html>
<html lang="<?php echo _lang(); ?>">
<head>
    <title><?php echo _title($pageTitle); ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
    <?php _css('base.'._getLang().'.css'); ?>
</head>
<body>
    <?php include('view.php'); ?>
</body>
</html>
<script type="text/javascript">
    LC.Page.Post.Setup.init();
</script>
