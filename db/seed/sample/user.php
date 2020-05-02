<?php

return array(
    'order' => 1,
    'user-1' => array(
        'slug'      => 'administrator',
        'full_name' => 'Administrator',
        'username'  => 'admin',
        'password'  => _encrypt('admin'),
        'email'     => 'admin@localhost.com',
        'role'      => 'admin',
        'is_master' => true,
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
