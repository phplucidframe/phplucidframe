<?php

if (route_start(_cfg('baseDir'), array(_cfg('baseDir') . '/login', _cfg('baseDir') . '/logout'))) {
    _middleware(function () {
        if (auth_isAnonymous()) {
            flash_set('You are not authenticated. Please log in.', '', 'error');
            _redirect(_cfg('baseDir') . '/login');
        }
    }, 'before');
}