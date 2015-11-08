<?php
/*
 * Infrastructure related configuration
 * Set parameters here that is related to production environment
 */
return array(
    # No trailing slash (only if it is located in a sub-directory of the document root)
    # Leave blank if it is located in the document root
    'baseURL' => '',
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
    ),
    # The site contact email address - This address used as "To" for all incoming mails
    'siteReceiverEmail' => 'admin@example.com',
    # The site sender email address - This address used as "From" for all outgoing mails
    'siteSenderEmail'   => 'noreply@example.com',
);
