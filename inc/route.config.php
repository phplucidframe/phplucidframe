<?php
/**
 * This file is used to define any custom named routes
 * This is a recommended place to define routes if necessary
 * Here you can define named routes instead of writing RewriteRule in .htaccess
 *
 * Syntax:
 *
 *      route($name)->map($path, $to, $method = 'GET', $patterns = null)
 *
 *      @param string     $name      Any unique route name to the mapped $path
 *      @param string     $path      URL path with optional dynamic variables such as /post/{id}/edit
 *      @param string     $to        The real path to a directory or file in /app
 *      @param string     $method    GET, POST, PUT or DELETE or any combination with `|` such as GET|POST
 *      @param array|null $patterns  array of the regex patterns for variables in $path such s array('id' => '\d+')
 */

/**
 * The named route example `lc_home`
 */
route('lc_home')->map('/', '/home');

/**
 * The named route example `lc_blog_show`
 * This is an example routed to the directory `/app/example/blog-page` that was formerly configured
 * in .htaccess.
 */
route('lc_blog_show')->map('/blog/{id}/{slug}', '/example/blog-page', 'GET', array(
    'id'    => '\d+', # {id} must be digits
    'slug'  => '[a-zA-Z\-_]+' # {slug} must only contain alphabets, dashes and underscores
));

route_group('/api/posts', function () {
    route('lc_post')->map('/', '/example/api/post', 'GET');
    route('lc_post_create')->map('/', '/example/api/post/create', 'POST');
    route('lc_post_update')->map('/{id}', '/example/api/post/update', 'PUT', array('id' => '\d+'));
    route('lc_post_delete')->map('/{id}', '/example/api/post/delete', 'DELETE', array('id' => '\d+'));
});
