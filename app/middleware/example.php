<?php

/**
 * This is an example middleware running before page starts
 * This will be executed on every page except login
 */
_middleware(function () {
    // Do something before page process is not started
    // for example,
    // redirect to the log in page if user is anonymous
    // or response 403
})->on('except', 'login');

/**
 * This is an example middleware running before page request
 * This will be executed on all pages which URI starts with "api"
 */
_middleware(function () {
    // Do something before page process is not started
    // for example, checking bearer token from HTTP Authorization
    // $authorization = _requestHeader('Authorization')
}, 'before') // 'before' is optional and default
    ->on('startWith', 'api');

/**
 * This is an example middleware running before page request
 * This will be executed on all pages which URI starts with "api/posts"
 */
_middleware(function () {
    // Do something before the page request
}, 'before')->on('startWith', 'api/posts');

/**
 * This is an example middleware running after page ends
 * This will be executed only on the route `lc_blog_show`
 * this is defined in route.config.php
 */
_middleware(function () {
    // Do something at the end of the page request
}, 'after')->on('equal', 'lc_blog_show');

/**
 * This is an example middleware running after page ends
 * This will be executed on all pages which URI contains "file-uploader"
 */
_middleware(function () {
    // Do something at the end of the page request
}, 'after')->on('contain', 'file-uploader');
