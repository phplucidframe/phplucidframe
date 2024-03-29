<?php
/*
 * Infrastructure related configuration
 * Set parameters here that is related to test environment
 */
return array(
    # No trailing slash (only if it is located in a sub-directory of the document root)
    # Leave blank if it is located in the document root
    'baseURL' => 'phplucidframe',
    # Site Domain Name
    'siteDomain' => _host(),
    # SSL enabled or not
    'ssl' => false,
    # Database connection information
    'db' => array(
        'default' => array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '',
            'database'  => '',
            'username'  => '',
            'password'  => '',
            'prefix'    => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'engine'    => 'InnoDB',
        ),
        'sample' => array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '',
            'database'  => 'lucid_blog_test',
            'username'  => 'root',
            'password'  => 'root',
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
