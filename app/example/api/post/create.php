<?php
/**
 * POST /api/posts
 */

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
    _json(array(
        'errors' => validation_get('errors'),
        'data' => $data,
    ), 400);
}

# Database operations here for insert
//db_insert('post', [
//    'title' => $data['title'],
//    'body'  => $data['body']
//]);

_json(array(
    'errors' => null,
    'data' => $data,
));
