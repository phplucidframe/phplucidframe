<?php
/**
 * The index.php serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */

$pageTitle = _t('Generic Form Example');

include('action.php');

_app('title', $pageTitle);
_app('view')->addData('pageTitle', $pageTitle);
