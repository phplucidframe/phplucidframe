<?php

/**
 * This is an example middleware running before page request
 * This will be executed on all pages which URI starts with "api"
 */
_middleware(function () {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? strtolower($_SERVER['HTTP_ORIGIN']) : '';
    $allowedDomains = array( // allowed domains added here
        'http://localhost',
        'http://localhost:3000',
    );

    if ($origin && in_array($origin, $allowedDomains)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }

    header('Access-Control-Allow-Headers: Origin, Content-Type, Cache-Control, Accept, X-Requested-With, X-Api-Key');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, PATCH, HEAD, OPTIONS');

    // if (_requestHeader('X-Api-Key') != _env('apiKey')) { # Validate API KEY or JWT token, etc.
    //     _page401();
    // }
}, 'before') // 'before' is optional and default
->on('startWith', 'api');
