<?php

namespace LucidFrame\Test;

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for lib/bootstrap.php
 * Smoke-level checks: the path constants, environment constants and the web
 * root constants defined during bootstrap, plus the loaded environment.
 */
class BootstrapTest extends LucidFrameTestCase
{
    public function testForPathConstants()
    {
        $constants = array('ROOT', 'APP_ROOT', 'LIB', 'HELPER', 'CLASSES', 'INC', 'DB', 'I18N', 'VENDOR', 'FILE', 'LOG', 'CACHE', 'TEST_DIR');

        foreach ($constants as $constant) {
            $this->assertTrue(defined($constant), $constant . ' must be defined');
            $this->assertIsString(constant($constant));
        }

        // All paths end with a directory separator
        foreach ($constants as $constant) {
            $path = constant($constant);
            $this->assertEqual(substr($path, -1), _DS_, $constant . ' must end with a directory separator');
        }
    }

    public function testForAppRootIsNestedUnderRoot()
    {
        // APP_ROOT points to the app/ sub-directory of ROOT
        $this->assertEqual(APP_ROOT, ROOT . 'app' . _DS_);
    }

    public function testForEnvironmentConstants()
    {
        $this->assertEqual(ENV_DEV, 'development');
        $this->assertEqual(ENV_STAGING, 'staging');
        $this->assertEqual(ENV_PROD, 'production');
        $this->assertEqual(ENV_TEST, 'test');
        $this->assertEqual(FILE_ENV, '.lcenv');
    }

    public function testForWebRootConstants()
    {
        $this->assertTrue(defined('WEB_ROOT'));
        $this->assertTrue(defined('WEB_APP_ROOT'));
        $this->assertTrue(defined('HOME'));

        // The web root ends with a slash
        $this->assertEqual(substr(WEB_ROOT, -1), '/');
    }

    public function testForNamespaceConstants()
    {
        $this->assertTrue(defined('REQUEST_URI'));
        $this->assertTrue(defined('LC_NAMESPACE'));

        // The test CLI process has no site namespace
        $this->assertEqual(LC_NAMESPACE, '');
    }

    public function testForTestEnvironmentIsActive()
    {
        // The test runner forces the "test" environment
        $this->assertEqual(_p('env'), 'test');
    }

    public function testForSimpleTestVendorsAreInPlace()
    {
        // SimpleTest 1.3 moved its sources to src/
        $autorun = VENDOR . 'simpletest' . _DS_ . 'simpletest' . _DS_ . 'src' . _DS_ . 'autorun.php';

        $this->assertTrue(is_file($autorun), 'The SimpleTest 1.3 src/autorun.php must exist');
    }

    public function testForConfigFileWasCreated()
    {
        // inc/config.php is auto-created from config.default.php if missing
        $this->assertTrue(is_file(INC . 'config.php'));
    }
}