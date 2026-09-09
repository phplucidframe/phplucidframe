<?php

namespace LucidFrame\Test;

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for lib/lc.php (framework kernel helpers)
 * Covers _app/_g, _i, _cfg/_cfgOption, _p, _env, __env/__envList,
 * _baseUrlWithProtocol, _baseDirs, _log, _schema and the kernel error-type
 * mapping used by __kernelErrorHandler.
 */
class LcTest extends LucidFrameTestCase
{
    /** @var array Log files written during the test, removed in tearDown */
    private $logFiles = array();

    public function tearDown()
    {
        foreach ($this->logFiles as $file) {
            @unlink($file);
        }
        $this->logFiles = array();

        parent::tearDown();
    }

    public function testForAppGetterAndSetter()
    {
        $originalTitle = _app('title');

        _app('title', 'LucidFrame Test');
        $this->assertEqual(_app('title'), 'LucidFrame Test');

        _app('title', $originalTitle);
    }

    public function testForGlobalGetterAndSetter()
    {
        _g('lc_kernel_test_flag', 'on');
        $this->assertEqual(_g('lc_kernel_test_flag'), 'on');

        // Dot notation creates nested arrays
        _g('lc_kernel_test.nested.key', 'value');
        $this->assertEqual(_g('lc_kernel_test.nested.key'), 'value');
    }

    public function testForCfgGetterAndSetter()
    {
        $original = _cfg('lc_kernel_test_cfg');
        _cfg('lc_kernel_test_cfg', 'custom-value');
        $this->assertEqual(_cfg('lc_kernel_test_cfg'), 'custom-value');
        _cfg('lc_kernel_test_cfg', $original);

        // The lc_ prefix is optional and stripped
        $this->assertEqual(_cfg('defaultDbSource'), 'sample');

        // Unknown keys give null
        $this->assertNull(_cfg('no_such_cfg_key'));
    }

    public function testForCfgOption()
    {
        // _cfgOption reads a key of an array config variable
        $this->assertEqual(_cfgOption('auth', 'table'), 'user');
        $this->assertNull(_cfgOption('auth', 'no_such_key'));
        $this->assertNull(_cfgOption('no_such_array_cfg', 'any_key'));
    }

    public function testForPReadsParameterFile()
    {
        // The test parameter file defines the sample DB host
        $this->assertEqual(_p('db.default.host'), 'localhost');
        $this->assertEqual(_p('db.sample.database'), 'lucid_blog_test');
    }

    public function testForPReturnsCurrentEnv()
    {
        $this->assertEqual(_p('env'), 'test');
    }

    public function testForEnvFallback()
    {
        // A missing key falls back to the provided default
        $this->assertEqual(_env('no.such.env.key', 'fallback-value'), 'fallback-value');
        $this->assertEqual(_env('no.such.env.key'), '');
    }

    public function testForEnvList()
    {
        $list = __envList();

        $this->assertIsArray($list);
        foreach (array(ENV_DEV, ENV_STAGING, ENV_PROD, ENV_TEST, 'dev', 'prod') as $env) {
            $this->assertContains($env, $list);
        }
    }

    public function testForEnvReadsLcEnvFile()
    {
        // The test runner forces the "test" environment, so __env() reports it
        // regardless of the .lcenv file content
        $this->assertEqual(__env(), 'test');
    }

    public function testForBaseUrlWithProtocol()
    {
        $baseUrl = _baseUrlWithProtocol();

        $this->assertIsString($baseUrl);

        // On CLI the site domain is empty and the config baseURL is used;
        // baseUrlWithProtocol() then resolves to /<baseURL> + a trailing slash
        $siteDomain = trim(_p('siteDomain'), '/');
        if ($siteDomain) {
            $this->assertStringContainsString($siteDomain, $baseUrl);
        } else {
            $this->assertStringContainsString(_cfg('baseURL'), $baseUrl);
        }
    }

    public function testForBaseDirs()
    {
        $dirs = _baseDirs('helpers');

        $this->assertIsArray($dirs);
        $this->assertTrue(count($dirs) >= 2);

        // The app and lib helper folders are always included
        $this->assertContains(APP_ROOT . 'helpers' . _DS_, $dirs);
        $this->assertContains(LIB . 'helpers' . _DS_, $dirs);
    }

    public function testForIIncludeHelper()
    {
        // An existing app file resolves to a real path
        $file = _i('inc' . _DS_ . 'route.config.php');
        $this->assertTrue(is_file($file));

        // A lib helper is resolved through the special helpers lookup
        $helper = _i('helpers' . _DS_ . 'session_helper.php');
        $this->assertTrue(is_file($helper));

        // A missing file yields an empty string
        $this->assertEqual(_i('no' . _DS_ . 'such' . _DS_ . 'file.php', false), '');
    }

    public function testForSchemaLoading()
    {
        $schema = _schema('sample');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('post', $schema);
        $this->assertArrayHasKey('user', $schema);
        $this->assertArrayHasKey('_options', $schema);

        // An unknown namespace falls back to the default /db/schema.php
        // (which only holds the global _options in this checkout)
        $fallback = _schema('no_such_schema_namespace');
        $this->assertIsArray($fallback);
        $this->assertArrayHasKey('_options', $fallback);
    }

    public function testForLog()
    {
        $written = _log('This is a kernel test log entry.', 'lc-kernel-test');

        $this->assertTrue($written);

        // The log file name carries the prefix, the date and the environment
        $pattern = LOG . 'lc-kernel-test-' . date('Ymd') . '-test.cli.log';
        $this->assertTrue(is_file($pattern), 'The log file must be written to ' . $pattern);

        $content = file_get_contents($pattern);
        $this->assertStringContainsString('This is a kernel test log entry.', $content);
        // Log lines are timestamped
        $this->assertPattern('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/m', $content);

        $this->logFiles[] = $pattern;
    }

    public function testForKernelErrorTypes()
    {
        $cases = array(
            E_ERROR             => 'E_ERROR: Fatal error',
            E_WARNING           => 'E_WARNING: Warning',
            E_PARSE             => 'E_PARSE: Parse error',
            E_NOTICE            => 'E_NOTICE: Notice',
            E_CORE_ERROR        => 'E_CORE_ERROR: Fatal error',
            E_CORE_WARNING      => 'E_CORE_WARNING: Warning',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR: Fatal error',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING: Warning',
            E_USER_ERROR        => 'E_USER_ERROR: User-generated error',
            E_USER_WARNING      => 'E_USER_WARNING: User-generated warning',
            E_USER_NOTICE       => 'E_USER_NOTICE: User-generated notice',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR: Catchable fatal error',
            E_DEPRECATED        => 'E_DEPRECATED: Deprecated warning',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED: User-generated deprecated warning',
        );

        foreach ($cases as $code => $label) {
            $this->assertEqual(__kernelErrorTypes($code), $label);
        }

        // Unknown codes fall back to the generic error label
        $this->assertEqual(__kernelErrorTypes(999999), 'E_ERROR, Error');
    }
}