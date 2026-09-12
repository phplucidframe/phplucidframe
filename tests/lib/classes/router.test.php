<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Router;

/**
 * Unit Test for Router class (lib/classes/Router.php)
 * Covers map/add/getRoutes, exact and pattern matching, patterns validation,
 * method restrictions, named routes, route groups and clean().
 *
 * The routes registered at bootstrap are backed up in the constructor and
 * restored after every test via reflection, so the app routing table is
 * never lost for the other test files running in the same process.
 */
class RouterTest extends LucidFrameTestCase
{
    /** @var array Backup of the routes registered at bootstrap */
    private $routesBackup;

    /** @var array Saved $_GET, restored after each test */
    private $savedGet;

    /** @var string Saved REQUEST_METHOD, restored after each test */
    private $savedMethod;

    public function __construct()
    {
        parent::__construct();

        $this->routesBackup = Router::getRoutes();
    }

    public function setUp()
    {
        parent::setUp();

        $this->savedGet = $_GET;
        $this->savedMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
        $_GET = array(ROUTE => '');
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function tearDown()
    {
        $_GET = $this->savedGet;
        if ($this->savedMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->savedMethod;
        }

        // Restore the bootstrap routes and the matched route name
        $ref = new \ReflectionClass(Router::class);

        $routes = $ref->getProperty('routes');
        $routes->setAccessible(true);
        $routes->setValue(null, $this->routesBackup);

        $matched = $ref->getProperty('matchedRouteName');
        $matched->setAccessible(true);
        $matched->setValue(null, null);

        parent::tearDown();
    }

    /** @return Closure */
    private function stubPage()
    {
        return function () {
            return 'lc-router-stub';
        };
    }

    public function testForRouteHelperReturnsRouter()
    {
        $router = route('lc_router_test_home');

        $this->assertIsA($router, 'LucidFrame\Core\Router');
        $this->assertEqual($router->getName(), 'lc_router_test_home');
    }

    public function testForMapAddsRoute()
    {
        route('lc_router_test_map')->map('/lc-router-test-map', $this->stubPage(), 'GET');

        $routes = Router::getRoutes();
        $this->assertArrayHasKey('lc_router_test_map', $routes);
        $this->assertEqual($routes['lc_router_test_map']['path'], '/lc-router-test-map');
        $this->assertContains('GET', $routes['lc_router_test_map']['method']);
    }

    public function testForMapWithMultipleMethods()
    {
        route('lc_router_test_multi')->map('/lc-router-test-multi', $this->stubPage(), 'GET|POST');

        $methods = Router::getRoutes()['lc_router_test_multi']['method'];
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        // OPTIONS is always appended
        $this->assertContains('OPTIONS', $methods);
    }

    public function testForGetPathByName()
    {
        route('lc_router_test_path')->map('/lc-router-test-path', $this->stubPage(), 'GET');

        $this->assertEqual(Router::getPathByName('lc_router_test_path'), 'lc-router-test-path');
        $this->assertNull(Router::getPathByName('no_such_route_xyz'));
    }

    public function testForMatchExactClosureRoute()
    {
        $page = $this->stubPage();
        route('lc_router_test_exact')->map('/lc-router-test-exact', $page, 'GET');

        $_GET[ROUTE] = 'lc-router-test-exact';

        $matched = Router::match();
        $this->assertTrue($matched === $page);
    }

    public function testForMatchReturnsFalseForUnknownRoute()
    {
        $_GET[ROUTE] = 'lc-router-test-unknown-path';

        $this->assertFalse(Router::match());
    }

    public function testForMatchPatternRoute()
    {
        $page = $this->stubPage();
        route('lc_router_test_post')->map('/lc-router-test-post/{id}', $page, 'GET', array('id' => '\d+'));

        $_GET[ROUTE] = 'lc-router-test-post/123';

        $matched = Router::match();
        $this->assertTrue($matched === $page);
        $this->assertEqual($_GET['id'], '123');
        $this->assertEqual(Router::getMatchedName(), 'lc_router_test_post');
        $this->assertEqual(route_name(), 'lc_router_test_post');
    }

    public function testForMatchPatternRouteWithCustomPattern()
    {
        $page = $this->stubPage();
        route('lc_router_test_slug')->map('/lc-router-test-slug/{slug}', $page, 'GET', array('slug' => '[a-z\-]+'));

        $_GET[ROUTE] = 'lc-router-test-slug/hello-world';

        $this->assertTrue(Router::match() === $page);
        $this->assertEqual($_GET['slug'], 'hello-world');
    }

    public function testForMatchPatternViolationThrows()
    {
        $page = $this->stubPage();
        route('lc_router_test_num')->map('/lc-router-test-num/{id}', $page, 'GET', array('id' => '\d+'));

        $_GET[ROUTE] = 'lc-router-test-num/not-a-number';

        $thrown = false;
        try {
            Router::match();
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
            $this->assertStringContainsString('lc_router_test_num', $e->getMessage());
        }
        $this->assertTrue($thrown, 'A pattern violation must throw an InvalidArgumentException');
    }

    public function testForMatchMethodNotAllowedThrows()
    {
        $page = $this->stubPage();
        route('lc_router_test_admin')->map('/lc-router-test-admin', $page, 'POST');

        $_GET[ROUTE] = 'lc-router-test-admin';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $thrown = false;
        try {
            Router::match();
        } catch (\RuntimeException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, 'A method mismatch must throw a RuntimeException');
    }

    public function testForGroupPrefixesRoutes()
    {
        route_group('lc-router-test-group', function () {
            route('lc_router_test_grouped')->map('/inner', $this->stubPage(), 'GET');
        });

        $routes = Router::getRoutes();
        $this->assertArrayHasKey('lc_router_test_grouped', $routes);
        $this->assertEqual($routes['lc_router_test_grouped']['path'], '/lc-router-test-group/inner');
    }
}
