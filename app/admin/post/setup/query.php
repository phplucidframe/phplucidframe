<?php
$post = new stdClass();

$post->postBody     = '';
$post->postTitle    = '';
$post->slug         = '';

if ($id) {
    $sql = 'SELECT p.slug, p.postTitle, p.postTitle_'.$lang.' postTitle_i18n,
                p.postBody, p.postBody_'.$lang.' postBody_i18n, p.catId
            FROM '.db_prefix().'post as p
            WHERE p.postId = :id LIMIT 1';
    if ($result = db_query($sql, array(':id' => $id))) {
        if (db_numRows($result) == 0) {
            _redirect('admin/property/list');
        }
        $post = db_fetchObject($result);

        $post->postTitle = ($post->postTitle_i18n) ? $post->postTitle_i18n : $post->postTitle;
        $post->postBody = ($post->postBody_i18n) ? $post->postBody_i18n : $post->postBody;
    }
}

$arg = array();
$sql = 'SELECT * FROM '.db_prefix().'category
        WHERE deleted IS NULL';
if ($id) {
    $sql .= ' OR (catId = :catId AND deleted IS NOT NULL)';
    $arg = array(':catId'=>$post->catId);
}
$sql .= ' ORDER BY catName';
$resultCat = db_query($sql, $arg);
