<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for utility_helper.php
 */
class UtilityHelperTestCase extends LucidFrameTestCase
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // reset to defaults
        global $lc_autoload;
        global $lc_sitewideWarnings;
        global $lc_minifyHTML;
        global $lc_translationEnabled;
        $lc_autoload = array();
        $lc_sitewideWarnings = array();
        $lc_minifyHTML = true;
        $lc_translationEnabled = true;
    }
    /**
     * Test cases for _g()
     */
    public function testForFunctionUnderscoreG()
    {
        _g('foo', array('bar'));
        // 1.
        $this->assertEqual(_g('foo'), array(0 => 'bar'));
        // 2.
        _g('name.first', 'This is first name.');
        _g('name.last', 'This is last name.');
        $this->assertEqual(_g('name'), array(
            'first' => 'This is first name.',
            'last' => 'This is last name.'
        ));
        // 3.
        $this->assertEqual(_g('name.first'), 'This is first name.');
        // 4.
        $this->assertEqual(_g('name.last'), 'This is last name.');
        // 5.
        _g('foo.bar.test', 'This is first value.');
        _g('foo.bar.test', 'This is second value.');
        _g('foo.bar.test', 'This is third value.');
        $this->assertEqual(_g('foo.bar'), array('test' => 'This is third value.'));
        $this->assertEqual(_g('foo.bar.test'), 'This is third value.');
        $this->assertNotEqual(_g('foo.bar'), array(
            'test' => array(
                0 => 'This is first value.',
                1 => 'This is second value.',
                2 => 'This is third value.'
            )
        ));
    }
    /**
     * Test cases for _cfg()
     */
    public function testForFunctionUnderscoreCfg()
    {
        // 1.
        _cfg('sitewideWarnings', _t('Change your own security salt hash in the file "/inc/.secret".'));
        $this->assertEqual(_cfg('sitewideWarnings'), 'Change your own security salt hash in the file "/inc/.secret".');
        // 2.
        _cfg('minifyHTML', false);
        $this->assertFalse(_cfg('minifyHTML'));
        // 3.
        _cfg('translationEnabled', false);
        $this->assertFalse(_cfg('translationEnabled'));
    }
    /**
     * Test cases for _loader() and _unloader()
     */
    public function testForFunctionsLoaderAndUnloader()
    {
        global $lc_autoload;

        // 1.
        _loader('i18n_helper');
        _loader('pager_helper');
        $this->assertEqual($lc_autoload, array(
            HELPER . 'i18n_helper.php',
            HELPER . 'pager_helper.php',
        ));
        // 2.
        _unloader('session_helper');
        $this->assertEqual($lc_autoload, array(
            HELPER . 'i18n_helper.php',
            HELPER . 'pager_helper.php',
        ));
        // 3.
        _loader('session_helper');
        _loader('validation_helper');
        _loader('auth_helper');
        _loader('security_helper');
        _loader('form_helper');
        _loader('file_helper');
        $this->assertEqual($lc_autoload, array(
            HELPER . 'i18n_helper.php',
            HELPER . 'pager_helper.php',
            HELPER . 'session_helper.php',
            HELPER . 'validation_helper.php',
            HELPER . 'auth_helper.php',
            HELPER . 'security_helper.php',
            HELPER . 'form_helper.php',
            HELPER . 'file_helper.php',
        ));
        // 4.
        _unloader('file_helper');
        _unloader('pager_helper');
        $this->assertEqual($lc_autoload, array(
            HELPER . 'i18n_helper.php',
            HELPER . 'session_helper.php',
            HELPER . 'validation_helper.php',
            HELPER . 'auth_helper.php',
            HELPER . 'security_helper.php',
            HELPER . 'form_helper.php',
        ));
    }
    /**
     * Test cases for _isBot()
     */
    public function testForFunctionUnderscoreIsBot()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        // default user agent
        $this->assertFalse(_isBot());
        // Googlebot
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        $this->assertTrue(_isBot());
        // Yahoo! Slurp
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)';
        $this->assertTrue(_isBot());
        // Msnbot 1.1
        $_SERVER['HTTP_USER_AGENT'] = 'msnbot/1.1 (+http://search.msn.com/msnbot.htm)';
        $this->assertTrue(_isBot());

        $_SERVER['HTTP_USER_AGENT'] = $userAgent;
    }
    /**
     * Test cases for _url()
     */
    public function testForFunctionUnderscoreUrl()
    {
        $this->assertEqual(_url(), _self());
        $this->assertEqual(_url('home'), rtrim(WEB_ROOT, '/'));
        $this->assertEqual(_url('blog'), WEB_ROOT.'blog');
        $this->assertEqual(_url('blog', array('this-is-blog-title')), WEB_ROOT.'blog/this-is-blog-title');
        $this->assertEqual(_url('http://example.com'), 'http://example.com');
        $this->assertEqual(_url('https://example.com'), 'https://example.com');
        $this->assertEqual(_url('https://fb.com/cithu83/posts/12345678'), 'https://fb.com/cithu83/posts/12345678');
    }
    /**
     * Test cases for _host()
     */
    public function testForFunctionUnderscoreHost()
    {
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $this->assertEqual(_host(), '127.0.0.1');

        unset($_SERVER['HTTP_HOST']);
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $this->assertEqual(_host(), '127.0.0.1');

        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['SERVER_NAME']);
        $this->assertEqual(_host(), php_uname('n'));
    }
    /**
     * Test cases for _p()
     */
    public function testForFunctionUnderscoreP()
    {
        $this->assertEqual(_p('baseURL'), 'phplucidframe');
        $this->assertEqual(_p('siteDomain'), _host());
        $this->assertEqual(_p('db.default.host'), 'localhost');
        $this->assertEqual(_p('db.default.collation'), 'utf8_unicode_ci');
    }
    /**
     * Test cases for _img()
     */
    public function testForFunctionUnderscoreImg()
    {
        $this->assertEqual(_img('logo.png'), WEB_ROOT . 'assets/images/logo.png');
    }
}
