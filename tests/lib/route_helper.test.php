<?php

use LucidFrame\Core\Router;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for session_helper.php
 */
class RouteHelperTestCase extends LucidFrameTestCase
{
    public function setUp()
    {
        if (PHP_SAPI == 'cli') {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        Router::clean();

        route('lc_home')->map('/', 'top');
        route('lc_post_new')->map('/post/new', '/post/new');
        route('lc_post_edit')->map('/post/{id}/edit', '/post/edit');
        route('lc_post_show')->map('/post/{id}/show', '/post/show');
        route('lc_post_create')->map('/post/create', '/post/create', 'POST');
        route('lc_post_update')->map('/post/{id}/update', '/post/update', 'POST|PUT');
        route('lc_post_comment')->map('/post/{id}/comment{cid}', '/post/comment');
        route('lc_blog_show')->map('/blog/{id}/{slug}', '/blog/show', 'GET', array(
            'id' => '\d+'
        ));
        route('lc_area')->map('/area/{city}/{type}', '/area', 'GET', array(
            'city' => '[a-zA-Z\-_]+',
            'type' => 'list|(poah|prin)\d+'
        ));
    }

    public function testRouteMatch()
    {
        $_GET[ROUTE] = '';
        $this->assertEqual(Router::match(), 'top');

        $_GET[ROUTE] = 'post/new';
        $this->assertEqual(Router::match(), 'post/new');

        $_GET[ROUTE] = 'post/2/edit';
        $this->assertEqual(Router::match(), 'post/edit');
        $this->assertEqual($_GET['id'], 2);

        $_GET[ROUTE] = 'post/3/show';
        $this->assertEqual(Router::match(), 'post/show');
        $this->assertEqual($_GET['id'], 3);

        $_GET[ROUTE] = 'post/1/comment4';
        $this->assertEqual(Router::match(), 'post/comment');
        $this->assertEqual($_GET['id'], 1);
        $this->assertEqual($_GET['cid'], 4);

        $_GET[ROUTE] = 'blog/1/url-rewrite-to-a-lucid-page-including-a-form-example';
        $this->assertEqual(Router::match(), 'blog/show');
        $this->assertEqual($_GET['id'], 1);
        $this->assertEqual($_GET['slug'], 'url-rewrite-to-a-lucid-page-including-a-form-example');

        $_GET[ROUTE] = 'area/yangon/list';
        $this->assertEqual(Router::match(), 'area');
        $this->assertEqual($_GET['city'], 'yangon');
        $this->assertEqual($_GET['type'], 'list');

        $_GET[ROUTE] = 'area/yangon/poah101';
        $this->assertEqual(Router::match(), 'area');
        $this->assertEqual($_GET['city'], 'yangon');
        $this->assertEqual($_GET['type'], 'poah101');

        $_GET[ROUTE] = 'area/yangon/prin201';
        $this->assertEqual(Router::match(), 'area');
        $this->assertEqual($_GET['city'], 'yangon');
        $this->assertEqual($_GET['type'], 'prin201');
    }

    public function testRouteMatchArgumentError()
    {
        try {
            $_GET[ROUTE] = 'blog/1a/url-rewrite-to-a-lucid-page-including-a-form-example';
            Router::match();
        } catch (\Exception $e) {
            $this->assertEqual(get_class($e), 'InvalidArgumentException');
            $this->assertEqual($e->getMessage(), 'The URL does not satisfy the argument value "1a" for "\d+".');
        }

        try {
            $_GET[ROUTE] = 'area/yangon./list';
            Router::match();
        } catch (\Exception $e) {
            $this->assertEqual(get_class($e), 'InvalidArgumentException');
            $this->assertEqual($e->getMessage(), 'The URL does not satisfy the argument value "yangon." for "[a-zA-Z\-_]+".');
        }

        try {
            $_GET[ROUTE] = 'area/yangon/poah';
            Router::match();
        } catch (\Exception $e) {
            $this->assertEqual(get_class($e), 'InvalidArgumentException');
            $this->assertEqual($e->getMessage(), 'The URL does not satisfy the argument value "poah" for "list|(poah|prin)\d+".');
        }
    }

    public function testRouteMatchMethodError()
    {
        try {
            $_GET[ROUTE] = 'post/create';
            Router::match();
        } catch (\Exception $e) {
            $this->assertEqual(get_class($e), 'RuntimeException');
            $this->assertEqual($e->getMessage(), 'The URL does not allow the method "GET" for "lc_post_create".');
        }

        try {
            $_GET[ROUTE] = 'post/1/update';
            Router::match();
        } catch (\Exception $e) {
            $this->assertEqual(get_class($e), 'RuntimeException');
            $this->assertEqual($e->getMessage(), 'The URL does not allow the method "GET" for "lc_post_update".');
        }
    }

    public function testRouteUrl()
    {
        $this->assertEqual(_url('lc_home', null, 'en'), WEB_ROOT.'en');
        $this->assertEqual(_url('lc_post_edit', array('id' => 1)), WEB_ROOT.'post/1/edit');
        $this->assertEqual(_url('lc_post_show', array('id' => 1), 'my'), WEB_ROOT.'my/post/1/show');
        $this->assertEqual(_url('lc_post_comment', array('id' => 1, 'cid' => 2)), WEB_ROOT.'post/1/comment2');
        $this->assertEqual(_url('lc_area', array('city' => 'yangon', 'type' => 'prin201')), WEB_ROOT.'area/yangon/prin201');

        $this->assertEqual(_url('example/blog', array(1, 'this-is-slug')), WEB_ROOT.'example/blog/1/this-is-slug');
        $this->assertEqual(_url('example/articles', array('page' => 2)), WEB_ROOT.'example/articles/-page/2');
    }
}
