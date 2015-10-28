<?php
/**
 * This file is used to define any custom named routes
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
 * This will be overridden to `$lc_homeRouting` in /inc/config.php
 * If this is not defined here, `$lc_homeRouting` will be used
 * However, `$lc_homeRouting` is deprecated in 1.10 and it will be removed in 2.0
 * This is a recommended place to define routings if necessary
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
