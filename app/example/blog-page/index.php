<?php
/**
 * The index.php (required) serves as the front controller for the requested page,
 * initializing the base resources needed to run the page
 */
$get    = _get($_GET);
$view   = _app('view');
$id     = $get['id'];
$slug   = $get['slug'];

$blog = post_getMock($id);

/*
 //// You can retrieve a single blog post here in 3 ways:

 $blog = db_find('post', $id);

 //// OR

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

$pageTitle = $blog->title;
_app('title', $pageTitle);

$view->data = array(
    'pageTitle' => $blog->title,
    'id'        => $id,
    'blog'      => $blog,
);
