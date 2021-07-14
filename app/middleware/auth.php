<?php

_middleware(function () {
    if (auth_isAnonymous()) {
        flash_set('You are not authenticated. Please log in.', '', 'error');
        _redirect(_cfg('baseDir') . '/login');
    }
})->on('startWith', _cfg('baseDir'))
    ->on('except', array(_cfg('baseDir') . '/login', _cfg('baseDir') . '/logout'));
