<?php

use LucidFrame\Test\LucidFrameDatabaseTestCase;

/**
 * Unit Test for auth_helper.php
 * Covers auth_get/set/clear, auth_isLoggedIn, auth_isAnonymous,
 * auth_namespace, auth_permissions, auth_prerequisite and auth_getUserInfo.
 *
 * Note: PHP session storage is not active on CLI, so auth_get() (which reads
 * from the session) always returns NULL in the test environment. The session
 * layer of auth_set()/auth_clear() is exercised through the `_app('auth')`
 * object layer, which is the framework's in-memory mirror of the auth session.
 */
class AuthHelperTestCase extends LucidFrameDatabaseTestCase
{
    /** @var array Saved auth config, restored after each test */
    private $savedAuthConfig;

    public function setUp()
    {
        parent::setUp();

        $this->savedAuthConfig = _cfg('auth');

        // Make sure no auth session leaks between tests
        auth_clear();
    }

    public function tearDown()
    {
        _cfg('auth', $this->savedAuthConfig);
        auth_clear();

        parent::tearDown();
    }

    public function testForAuthPrerequisite()
    {
        $auth = auth_prerequisite();

        $this->assertIsArray($auth);
        $this->assertEqual($auth, _cfg('auth'));
    }

    public function testForAuthNamespace()
    {
        // LC_NAMESPACE is empty in the test environment
        $this->assertEqual(auth_namespace(), 'AuthUser.default');
    }

    public function testForAuthSet()
    {
        $session = new stdClass();
        $session->id = 1;
        $session->role = 'admin';

        auth_set($session);

        // The authenticated user object is mirrored into the App registry
        $this->assertIdentical(_app('auth'), $session);
    }

    public function testForAuthClear()
    {
        $session = new stdClass();
        $session->id = 1;

        auth_set($session);
        $this->assertIdentical(_app('auth'), $session);

        auth_clear();
        $this->assertNull(_app('auth'));
    }

    public function testForAuthIsAnonymousByDefault()
    {
        $this->assertTrue(auth_isAnonymous());
        $this->assertFalse(auth_isLoggedIn());
    }

    public function testForAuthRoleAsAnonymous()
    {
        // No authenticated user; role checks must always fail
        $this->assertFalse(auth_role('admin'));
        $this->assertFalse(auth_roles('admin'));
        $this->assertFalse(auth_roles(array('admin', 'editor')));
        $this->assertFalse(auth_can('post-add'));
    }

    public function testForAuthPermissions()
    {
        // Unknown role has no permissions
        $this->assertNull(auth_permissions('nonexistent-role'));

        $permissions = array(
            'admin'  => array('post-list', 'post-add', 'post-edit', 'post-delete'),
            'editor' => array('post-list', 'post-add'),
        );
        _cfg('auth.permissions', $permissions);

        $this->assertEqual(auth_permissions('admin'), $permissions['admin']);
        $this->assertEqual(auth_permissions('editor'), $permissions['editor']);
        $this->assertNull(auth_permissions('unknown'));
    }

    public function testForAuthGetUserInfo()
    {
        // The test database contains the default admin user
        $admin = db_select('user')->where()->condition('username', 'admin')->getSingleResult();
        $this->assertNotNull($admin);

        $userInfo = auth_getUserInfo($admin->id);
        $this->assertNotNull($userInfo);
        $this->assertEqual($userInfo->id, $admin->id);
        $this->assertEqual($userInfo->username, 'admin');
        $this->assertEqual($userInfo->role, 'admin');
    }

    public function testForAuthGetUserInfoWithUnknownId()
    {
        $this->assertNull(auth_getUserInfo(0));
    }
}
