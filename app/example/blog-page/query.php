<?php
/**
 * The query.php (optional) should retrieve and process data from database and make it available to view.php.
 */

/*
 //// You could query the blog post here using db_fetchResult() or db_query()
 //// db_fetchResult() would return the std object
 //// Here is an example.

 $sql = 'SELECT * FROM '.db_prefix().'post WHERE id = :id'
 $blog = db_fetchResult($sql, array(':id' => $id));
 if ($blog) {
	if ($slug && strcasecmp($slug, $blog->slug) !== 0) { # 301 redirect to the correct URL
		header( "HTTP/1.1 301 Moved Permanently" );
		header( "Location: "._url('blog', array($id, $blog->slug)));
		exit;
	}
 }
*/

$blog = new stdClass();
$blog->title = 'URL Rewrite to A Page Including a Form Example';
$blog->body = 'This would be from the database.';
