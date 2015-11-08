<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */
$pageTitle = _t('AsynFileUploader Example');
$id = _arg(2);

include('query.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo _title($pageTitle); ?></title>
    <?php include( _i('inc/tpl/head.php') ); ?>
    <style type="text/css">
        .asynfileuploader-button {
            width: 106px;
        }
        .table .col {
            margin-right: 20px;
        }
        .thumbnail a {
            color: #8c8c8c;
        }
    </style>
</head>
<body>
    <?php include('view.php'); ?>
</body>
</html>
<script type="application/javascript">
    // This function shows the PHP POSTed data array in JSON format
    // when clicked the Submit button
    function postOutput($post) {
        console.log($post);
        alert(JSON.stringify($post));
        alert('Check your developer console');
    }

    // The following are example hooks defined for the last AsynFileUploader named "sheet"
    LC.AsynFileUploader.addHook('sheet', 'afterUpload', function(name, data) {
        console.log('afterUpload');
        console.log(name);
        console.log(data);
    });

    LC.AsynFileUploader.addHook('sheet', 'afterDelete', function(name, data) {
        console.log('afterDelete');
        console.log(name);
        console.log(data);
    });

    LC.AsynFileUploader.addHook('sheet', 'onError', function(name, error) {
        console.log('onError');
        console.log(name);
        console.log(error);
        alert(error.plain);
    });

</script>
