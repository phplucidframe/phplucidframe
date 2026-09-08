<?php

namespace LucidFrame\Test;

use LucidFrame\Core\App;

/**
 * Unit Test for App class (lib/classes/App.php)
 * App is a static holder of application-wide services (db, view, page, auth).
 * The _app() helper in utility_helper.php is the accessor for these statics.
 */
class AppTest extends LucidFrameTestCase
{
    public function testForAppStaticPlatform()
    {
        $this->assertIsA(App::$db, 'LucidFrame\Core\db\Database');
        $this->assertIsA(App::$view, 'LucidFrame\Core\View');
    }

    public function testForAppGetterAndSetterHelpers()
    {
        $originalPage = _app('page');

        // bootstrap assigns a page during routing; just save and restore
        _app('page', 'home');
        $this->assertEqual(_app('page'), 'home');
        $this->assertEqual(App::$page, 'home');

        // restore
        _app('page', $originalPage);
    }

    public function testForAppAuthStorage()
    {
        $originalAuth = App::$auth;
        $this->assertNull($originalAuth); // anonymous user

        $user = new \stdClass();
        $user->id = 1;
        $user->role = 'admin';

        App::$auth = $user;
        $this->assertIdentical(_app('auth'), $user);

        App::$auth = null;
    }
}