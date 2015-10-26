<?php
/**
 * The query.php (optional) should retrieve and process data from database and make it available to view.php.
 */

$blog = new stdClass();

$blog->title = 'URL Rewrite to A Page Including a Form Example';
$blog->body  = 'This would be from the database.';
$blog->slug  = 'url-rewrite-to-a-page-including-a-form-example';

/*
 //// You can retrieve a single blog post here using `db_select()` and `getSingleResult()`

 $blog = db_select('post')->where('id', $id)->getSingleResult();

 //// OR
 //// You can also use `db_fetchResult()` with SQL which returns the std object

 $sql = 'SELECT * FROM '.db_prefix().'post WHERE id = :id'
 $blog = db_fetchResult($sql, array(':id' => $id));
*/

if ($blog) {
    if ($slug && strcasecmp($slug, $blog->slug) !== 0) {
        # 301 redirect to the correct URL
        _redirect301(_url('blog', array($id, $blog->slug)));
    }
}
