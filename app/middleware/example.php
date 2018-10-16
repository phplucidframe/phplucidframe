<?php

/**
 * This is an example middleware running before page starts
 * This will be executed on every page
 */
_middleware(function () {
    // Do something before page process is not started
    // for example,
    // redirect to the log in page if user is anonymous
    // or response 403
});

if (route_start('api')) {
    /**
     * This is an example middleware running before page starts
     * This will be executed on all pages which URI starts with "api"
     */
    _middleware(function () {
        // Do something before page process is not started
    }, 'before'); // 'before' is optional and default
}

if (route_start('api/posts')) {
    /**
     * This is an example middleware running before page starts
     * This will be executed on all pages which URI starts with "api"
     */
    _middleware(function () {
        // Do something before page process is not started
    }, 'after');
}

if (route_equal('lc_blog_show')) {
    /**
     * This is an example middleware running after page ends
     * This will be executed only on the route `lc_blog_show`
     * this is defined in route.config.php
     */
    _middleware(function () {
        // Do something at the end of the page process
    }, 'after');
}

if (route_contain('file-uploader')) {
    /**
     * This is an example middleware running after page ends
     * This will be executed on all pages which URI contains "file-upload"
     */
    _middleware(function () {
        // Do something at the end of the page process
    }, 'after');
}
