<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Middleware;

/**
 * Unit Test for Middleware class (lib/classes/Middleware.php)
 * Covers register/run pipeline, before/after events, route filters and order.
 * Middleware statics accumulate within one PHP process; each test uses a
 * unique route + flag key so no cross-test interference occurs, and the
 * registered events are flushed via a fresh singletons reflection reset.
 */
class MiddlewareTest extends LucidFrameTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Reset the singleton's static state so tests start clean
        $this->resetMiddleware();
    }

    public function tearDown()
    {
        $this->resetMiddleware();
        parent::tearDown();
    }

    /** @return void */
    private function resetMiddleware()
    {
        $ref = new \ReflectionClass(Middleware::class);
        foreach (array('before', 'after', 'routeFilters', 'orders', 'id') as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            if ($prop === 'id') {
                $p->setValue(null, null);
            } else {
                $p->setValue(null, array());
            }
        }
    }

    public function testForSingleton()
    {
        $first = Middleware::getInstance();
        $second = Middleware::getInstance();

        $this->assertIdentical($first, $second);
    }

    public function testForRunBeforeCallsRegisteredMiddleware()
    {
        $called = 0;
        Middleware::getInstance()->register(function () use (&$called) {
            $called++;
        });

        Middleware::runBefore();

        $this->assertEqual($called, 1);
    }

    public function testForRunAfterCallsRegisteredMiddleware()
    {
        $called = 0;
        Middleware::getInstance()->register(function () use (&$called) {
            $called++;
        }, Middleware::AFTER);

        Middleware::runAfter();

        $this->assertEqual($called, 1);
    }

    public function testForBeforeAndAfterAreSeparateEvents()
    {
        $beforeCalls = 0;
        $afterCalls = 0;

        Middleware::getInstance()->register(function () use (&$beforeCalls) {
            $beforeCalls++;
        });
        Middleware::getInstance()->register(function () use (&$afterCalls) {
            $afterCalls++;
        }, Middleware::AFTER);

        Middleware::runBefore();
        Middleware::runAfter();

        $this->assertEqual($beforeCalls, 1);
        $this->assertEqual($afterCalls, 1);
    }

    public function testForOrderControlsExecutionSequence()
    {
        $trace = array();

        Middleware::getInstance()->register(function () use (&$trace) {
            $trace[] = 'first';
        })->order(2);

        Middleware::getInstance()->register(function () use (&$trace) {
            $trace[] = 'second';
        })->order(1);

        Middleware::runBefore();

        $this->assertEqual($trace, array('second', 'first'));
    }

    public function testForOnEqualRouteFilter()
    {
        // route_equal() compares against the CURRENT route; use _rr() so the
        // filter is guaranteed to match regardless of the environment
        $called = 0;
        Middleware::getInstance()
            ->register(function () use (&$called) {
                $called++;
            })
            ->on(Middleware::FILTER_EQUAL, _rr());

        Middleware::runBefore();

        $this->assertEqual($called, 1);
    }

    public function testForOnEqualRouteFilterNotMatched()
    {
        $called = 0;
        Middleware::getInstance()
            ->register(function () use (&$called) {
                $called++;
            })
            ->on(Middleware::FILTER_EQUAL, 'no-such-route-xyz');

        Middleware::runBefore();

        $this->assertEqual($called, 0);
    }

    public function testForOnStartWithRouteFilter()
    {
        // Take the first path segment of the current route
        $first = preg_replace('/(\\\\|\/).*$/', '', _rr());
        $called = 0;
        Middleware::getInstance()
            ->register(function () use (&$called) {
                $called++;
            })
            ->on(Middleware::FILTER_START_WITH, $first);

        Middleware::runBefore();

        $this->assertEqual($called, 1);
    }

    public function testForOnContainRouteFilter()
    {
        $called = 0;
        Middleware::getInstance()
            ->register(function () use (&$called) {
                $called++;
            })
            ->on(Middleware::FILTER_CONTAIN, 'bootstrap.php');

        Middleware::runBefore();

        $this->assertEqual($called, 1);
    }
}