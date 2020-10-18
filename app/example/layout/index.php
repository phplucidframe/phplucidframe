<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */

_cfg('layoutMode', true); # Normally this is not needed here if you configured it in /inc/config.php.

$view = _app('view');
// $view->name = 'mobile'; # this will include view_mobile.php in this directory, otherwise include view.php by default
// $view->layout = 'layout_mobile'; # particular layout file name can be given like this

# data to view template can be given like this
$view->data = array(
    'title' => 'This is layout mode example',
    'path' => '/' . APP_DIR . '/' . _r() . '/',
);

# data to view template can also be given by each variable like this
# $title and $path will be accessible in view
// $view->addData('title', 'This is layout mode example');
// $view->addData('path', '/' . APP_DIR . '/' . _r() . '/')

