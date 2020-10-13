<?php
/*
 * Copy and rename this file to parameter.env.inc and it should not be committed to your application's source control.
 * This array structure could reflect to the one of development.php or production.php
 * Set parameters that would be configuration of your deployment target of the app, e.g. development, production,
 * especially for login credentials. These values can be retrieved by using _env().
 */
return array(
    'prod' => array( # either prod or production as you like
        'db' => array(
            'default' => array(
                'database'  => '', // accessible by _env('prod.db.default.database')
                'username'  => '', // accessible by _env('prod.db.default.username')
                'password'  => '', // accessible by _env('prod.db.default.password')
                'prefix'    => '', // accessible by _env('prod.db.default.prefix')
            )
        )
    )
);
