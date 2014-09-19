<?php
/**
 * The view.php (required) is a visual output representation to user using data provided by query.php.
 * It generally should contain HTML between <body> and </body>.
 */
?>
<?php include( _i('inc/header.php') ); ?>

<h3><?php echo $pageTitle; ?></h3>
<div id="list"></div>

<?php include( _i('inc/footer.php') ); ?>