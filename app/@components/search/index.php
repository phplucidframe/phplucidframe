<?php

$users = [
    [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com'
    ],
    [
        'id' => 2,
        'name' => 'Jane Smith',
        'email' => 'jane.smith@example.com'
    ],
    [
        'id' => 3,
        'name' => 'Bob Johnson',
        'email' => 'bob.johnson@example.com'
    ],
    [
        'id' => 4,
        'name' => 'Alice Brown',
        'email' => 'alice.brown@example.com'
    ],
    [
        'id' => 5,
        'name' => 'Charlie Wilson',
        'email' => 'charlie.wilson@example.com'
    ],
    [
        'id' => 6,
        'name' => 'Jane Doe',
        'email' => 'jane.doe@example.com'
    ],
];

# Define stateful data using default values
$component->useData('users', $users);

/**
 * Register a live action "search" to filter users based on query
 */
$component->action('search', static function($component) use ($users) {
    $query = strtolower($component->getData('query'));
    $result = $users;

    if ($query) {
        $result = array_filter($users, function ($user) use ($query) {
            return stripos(strtolower($user['name']), $query) !== false ||
                stripos(strtolower($user['email']), $query) !== false;
        });
    }

    $component->setData('users', $result);
});
