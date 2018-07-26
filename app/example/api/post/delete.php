<?php

$id = _arg('id');

# Validation example here
//$post = db_select('post', 'p')
//    ->where()
//    ->condition('postId', $id)
//    ->getSingleResult();
//if (!$post) {
//    _json([
//        'id' => $id
//    ], 404);
//}

# Database operations here for delete
//db_delete('post', array('postId' => $id));

_json([
    'id' => $id
]);
