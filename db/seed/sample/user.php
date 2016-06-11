<?php

return array(
    'order' => 1,
    'user-1' => array(
        'slug'      => 'administrator',
        'fullName'  => 'Administrator',
        'username'  => 'admin',
        'password'  => _encrypt('admin'),
        'email'     => 'admin@localhost.com',
        'role'      => 'admin',
        'isMaster'  => 1,
        'phone'     => array(
            '09123456789',
            '09987654321',
        ),
        'social'    => array(
            'facebook'  => 'http://fb.com/lucidframe.myanmar',
            'twitter'   => 'http://twitter.com/phplucidframe',
        ),
    ),
);
