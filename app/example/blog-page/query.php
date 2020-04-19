<?php
/**
 * The query.php (optional) should retrieve and process data from database and make it available to view.php.
 */

$blog = new stdClass();

$blog->title = 'Custom Routing to a Page Including a Form Example';
$blog->body  = 'This would be from the database.';
$blog->slug  = 'custom-routing-to-a-page-including-a-form-example';

/*
 //// You can retrieve a single blog post here using `db_select()` and `getSingleResult()`

 $blog = db_select('post')->where('id', $id)->getSingleResult();

 //// OR
 //// You can also use `db_fetchResult()` with raw SQL and it will return std object

 $sql = 'SELECT *, postTitle title FROM ' . db_table('post') . ' WHERE postId = :id';
 $blog = db_fetchResult($sql, array(':id' => $id));
*/

if ($blog) {
    // Routing system make $slug available according to the route definition in inc/route.config.php
    if ($slug && strcasecmp($slug, $blog->slug) !== 0) {
        # 301 redirect to the correct URL
        _redirect301(_url('blog', array($id, $blog->slug)));
    }
}
