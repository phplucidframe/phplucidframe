<?php

$data = _post();

# Validation example here
$validations['title'] = array(
    'caption'   => _t('Title'),
    'value'     => $data['title'],
    'rules'     => array('mandatory'),
);

$validations['body'] = array(
    'caption'   => _t('Body'),
    'value'     => $data['body'],
    'rules'     => array('mandatory'),
);

if (!validation_check($validations)) {
    _json([
        'errors' => validation_get('errors'),
        'data' => $data,
    ], 400);
}

# Database operations here for insert
//$postData = array(
//    'postTitle' => $data['title'],
//    'postBody' => $data['body']
//);
//
//db_insert('post', [
//    'postTitle' => $data['title'],
//    'postBody'  => $data['body']
//]);

_json([
    'errors' => $errors,
    'data' => $data,
]);
