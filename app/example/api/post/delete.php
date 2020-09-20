<?php
/**
 * DELETE /api/posts/{id}
 */

$id = _arg('id');

# Validation example here
//$post = db_select('post', 'p')
//    ->where()
//    ->condition('id', $id)
//    ->getSingleResult();
//if (!$post) {
//    _json(array(
//        'id' => $id
//    ), 404);
//}

# Database operations here for delete
//db_delete('post', array('id' => $id));

_json(array(
    'id' => $id
));
