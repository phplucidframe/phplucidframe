<?php
$user = new stdClass();
$user->fullName = '';
$user->username = '';
$user->email    = '';
$user->role     = '';

if ($id) {
    $user = db_select('user')
        ->where()->condition('uid', $id)
        ->getSingleResult();
}
