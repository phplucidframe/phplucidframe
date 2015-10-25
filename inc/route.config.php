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
