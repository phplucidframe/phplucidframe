<?php
/*
 * Copy and rename this file to env.inc, and it should not be committed to your application's source control.
 * This array structure could reflect to the one of development.php or production.php
 * Set parameters that would be configuration of your deployment target of the app, e.g. development, production,
 * especially for login credentials. These values can be retrieved by using _env().
 */
return array(
    'host' => 'http://localhost', # change this by environment and accessible by _env('host')
    'dev' => array( # either dev or development as you like
        'db' => array(
            'default' => array(
                'database'  => '', // accessible by _env('dev.db.default.database')
                'username'  => '', // accessible by _env('dev.db.default.username')
                'password'  => '', // accessible by _env('dev.db.default.password')
                'prefix'    => '', // accessible by _env('dev.db.default.prefix')
            )
        ),
    ),
    'prod' => array( # either prod or production as you like
        'db' => array(
            'default' => array(
                'database'  => '', // accessible by _env('prod.db.default.database')
                'username'  => '', // accessible by _env('prod.db.default.username')
                'password'  => '', // accessible by _env('prod.db.default.password')
                'prefix'    => '', // accessible by _env('prod.db.default.prefix')
            )
        ),
    )
);
