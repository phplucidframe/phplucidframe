<?php
$post = new stdClass();

$post->postBody     = '';
$post->postTitle    = '';
$post->slug         = '';

if ($id) {
    $post = db_select('post', 'p')
        ->where()
        ->condition('postId', $id)
        ->getSingleResult();
    if ($post) {
        $post = _getTranslationStrings($post, array('postTitle', 'postBody'), $lang);
    } else {
        _redirect('admin/property/list');
    }
}

$condition = array('deleted' => null);
if ($id) {
    $condition[] = db_and(array(
        'catId' => $post->catId,
        'deleted !=' => null
    ));
}

$categories = db_select('category')
    ->orWhere($condition)
    ->orderBy('catName')
    ->getResult();
