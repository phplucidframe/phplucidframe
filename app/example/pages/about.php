<h1><?php echo _t('About PHPLucidFrame'); ?></h1>

<?php echo _tc('about'); ?>

<code>
// This page is rendered by ths route defined in inc/route.config.php

route('lc_about')->map('/about', function() {
    # /about maps to /app/example/pages/about.php
    $pageTitle = _t('About');
    _app('title', $pageTitle);

    return _app('view')->block('example/pages/about');
});
</code>
