<?php

return array(
    'order' => 1,
    'user-1' => array(
        'full_name' => 'Administrator',
        'username'  => 'admin',
        'password'  => password_hash('Password123', PASSWORD_DEFAULT),
        'email'     => 'admin@localhost.com',
        'role'      => 'admin',
        'is_master' => 1,
    ),
);
