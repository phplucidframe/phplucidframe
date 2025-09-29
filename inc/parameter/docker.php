<?php
/*
 * Infrastructure related configuration for Docker environment
 * Set parameters here that is related to Docker containerized environment
 */
return array(
    # No trailing slash (only if it is located in a sub-directory of the document root)
    # Leave blank if it is located in the document root
    'baseURL' => '',
    # Site Domain Name
    'siteDomain' => _host(),
    # SSL enabled or not
    'ssl' => false,
    # The debug level: 0 ~ 3 or custom debug level
    'debugLevel' => 2,
    # Database connection information
    'db' => array(
        'default' => array(
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'db',
            'port'      => '',
            'database'  => $_ENV['DB_DATABASE'] ?? 'lucidframe',
            'username'  => $_ENV['DB_USERNAME'] ?? 'lucidframe',
            'password'  => $_ENV['DB_PASSWORD'] ?? 'lucidframe_password',
            'prefix'    => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'engine'    => 'InnoDB',
        ),
        'sample' => array(
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'db',
            'port'      => '',
            'database'  => 'lucid_blog',
            'username'  => $_ENV['DB_USERNAME'] ?? 'lucidframe',
            'password'  => $_ENV['DB_PASSWORD'] ?? 'lucidframe_password',
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
