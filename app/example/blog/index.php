<?php
/**
 * The index.php serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */

$pageTitle = _t('Blog') . ' ('. _t('AJAX List & Pagination') . ')';

_meta('description', $pageTitle . ': ' . _cfg('metaDescription'));

_app('title', $pageTitle);
_app('view')->addData('pageTitle', $pageTitle);
