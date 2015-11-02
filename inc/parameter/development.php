<?php
/*
 * Infrastructure related configuration
 * Set parameters here that is related to development environment
 */
return array(
    # No trailing slash (only if it is located in a sub-directory of the document root)
    # Leave blank if it is located in the document root
    'baseURL' => 'LucidFrame',
    # Site Domain Name
    'siteDomain' => _host(),
    # Database connection information
    'db' => array(
        'default' => array(
            'engine'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '',
            'database'  => '',
            'username'  => '',
            'password'  => '',
            'prefix'    => '',
            'collation' => 'utf8_general_ci'
        )
    )
);
