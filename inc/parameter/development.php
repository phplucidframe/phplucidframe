<?php
/*
 * Infrastructure related configuration
 * Set parameters here that is related to development environment
 */
return array(
    # No trailing slash (only if it is located in a sub-directory of the document root)
    # Leave blank if it is located in the document root
    'baseURL' => 'phplucidframe',
    # Site Domain Name
    'siteDomain' => _host(),
    # SSL enabled or not
    'ssl' => false,
    # The debug level: 0 ~ 3 or custom debug level
    'debugLevel' => 3,
    # Database connection information
    'db' => array(
        'default' => array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '',
            'database'  => '', # or use _env('dev.db.default.database') here
            'username'  => '', # or use _env('dev.db.default.username') here
            'password'  => '', # or use _env('dev.db.default.password') here
            'prefix'    => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'engine'    => 'InnoDB',
        ),
        'sample' => array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '',
            'database'  => 'lucid_blog',
            'username'  => '',
            'password'  => '',
            'prefix'    => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'engine'    => 'InnoDB',
        )
    ),
    # The site contact email address - This address used as "To" for all incoming mails
    'siteReceiverEmail' => 'admin@localhost.com',
    # The site sender email address - This address used as "From" for all outgoing mails
    'siteSenderEmail'   => 'noreply@localhost.com',
);
