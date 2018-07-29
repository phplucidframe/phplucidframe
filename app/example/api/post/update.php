<?php

$id     = _arg('id');
$data   = _patch();

# Validation example here
//$post = db_select('post', 'p')
//    ->where()
//    ->condition('postId', $id)
//    ->getSingleResult();
//if (!$post) {
//    _json(array(
//        'errors' => array(
//            'msg' => 'Post not found.',
//        ),
//        'data' => $data,
//    ), 404);
//}

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

# Database operations here for update
//$postData = array(
//    'postTitle' => $data['title'],
//    'postBody' => $data['body']
//);
//
//db_update('post', [
//    'postId'    => $id,
//    'postTitle' => $data['title'],
//    'postBody'  => $data['body']
//]);

_json(array(
    'errors' => null,
    'data' => $data,
));
