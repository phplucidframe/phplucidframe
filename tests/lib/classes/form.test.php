<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Form;
use LucidFrame\Core\Validation;

/**
 * Unit Test for Form class (lib/classes/Form.php)
 * Covers init/set/get, CSRF token generation/restore, validate (token +
 * referer + rules), value/htmlValue/selected/checked helpers.
 */
class FormClassTest extends LucidFrameTestCase
{
    /** @var array Saved $_POST, restored after each test */
    private $savedPost;
    /** @var string|null Saved HTTP_REFERER, restored after each test */
    private $savedReferer;
    /** @var string Saved siteDomain config, restored after each test */
    private $savedSiteDomain;

    public function setUp()
    {
        parent::setUp();

        $this->savedPost = $_POST;
        $this->savedReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        $this->savedSiteDomain = _cfg('siteDomain');
        $_POST = array();
        Form::$formToken = '';
        Form::init();
        Validation::$errors = array();
    }

    public function tearDown()
    {
        $_POST = $this->savedPost;
        if ($this->savedReferer === null) {
            unset($_SERVER['HTTP_REFERER']);
        } else {
            $_SERVER['HTTP_REFERER'] = $this->savedReferer;
        }
        _cfg('siteDomain', $this->savedSiteDomain);
        Form::init();

        parent::tearDown();
    }

    /**
     * Set up a valid CSRF token submission: generate the token, post its
     * encrypted value and set the referer to the bare host of siteDomain.
     */
    private function prepareValidFormToken()
    {
        $host = preg_replace('#^https?://#i', '', _cfg('siteDomain'));
        _cfg('siteDomain', $host);

        Form::generateToken();
        $_POST[Form::getCsrfTokenName()] = _encrypt(Form::$formToken);
        $_SERVER['HTTP_REFERER'] = 'http://' . $host . '/form';
    }

    public function testForFormInitResetsState()
    {
        Form::set('success', true);
        Form::set('redirect', '/x');

        Form::init();

        $this->assertFalse(Form::get('success'));
        $this->assertEqual(Form::get('redirect'), '');
    }

    public function testForFormSetAndGet()
    {
        Form::set('id', 'contact-form');
        $this->assertEqual(Form::get('id'), 'contact-form');

        Form::set('message', 'Saved.');
        $this->assertEqual(Form::get('message'), 'Saved.');

        // Unknown key returns the default
        $this->assertNull(Form::get('unknown'));
    }

    public function testForGenerateAndRestoreToken()
    {
        Form::generateToken();
        $token = Form::$formToken;

        $this->assertPattern('/^[0-9a-f]{32}$/', $token);
        $this->assertEqual(Form::$formToken, $token);

        // On CLI there is no session, so restoreToken() yields no token
        Form::restoreToken();
        $this->assertNull(Form::$formToken);
    }

    public function testForTokenNameUsesConfig()
    {
        $this->assertEqual(Form::getCsrfTokenName(), 'lc_formToken_' . _cfg('formTokenName'));
    }

    public function testForValidateWithValidToken()
    {
        $this->prepareValidFormToken();

        $this->assertTrue(Form::validate());
    }

    public function testForValidateWithNoToken()
    {
        // No CSRF token posted
        $this->assertFalse(Form::validate());

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Invalid form token.', $errors[0]['message']);
    }
public function testForValidateWithWrongToken()
    {
        $host = preg_replace('#^https?://#i', '', _cfg('siteDomain'));
        _cfg('siteDomain', $host);

        Form::generateToken();
        $_POST[Form::getCsrfTokenName()] = _encrypt('wrong-token');
        $_SERVER['HTTP_REFERER'] = 'http://' . $host . '/form';

        $this->assertFalse(Form::validate());

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors, 'wrong-token validate should add exactly one error');
        $this->assertStringContainsString('form submission', $errors[0]['message']);
    }

    public function testForValidateWithWrongReferer()
    {
        $host = preg_replace('#^https?://#i', '', _cfg('siteDomain'));
        _cfg('siteDomain', $host);

        Form::generateToken();
        $_POST[Form::getCsrfTokenName()] = _encrypt(Form::$formToken);
        $_SERVER['HTTP_REFERER'] = 'http://evil.example.com/form';

        $this->assertFalse(Form::validate());
    }

    public function testForValidateWithRules()
    {
        $this->prepareValidFormToken();

        $validations = array(
            'name' => array(
                'caption' => 'Name',
                'value'   => 'John',
                'rules'   => array('mandatory'),
            ),
        );

        $this->assertTrue(Form::validate($validations));
    }

    public function testForValidateWithFailingRules()
    {
        $this->prepareValidFormToken();

        $validations = array(
            'name' => array(
                'caption' => 'Name',
                'value'   => '',
                'rules'   => array('mandatory'),
            ),
        );

        $this->assertFalse(Form::validate($validations));

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertEqual($errors[0]['field'], 'name');
    }

    public function testForInputSelectionWithPost()
    {
        $_POST = array('status' => 2);

        $this->assertTrue(Form::inputSelection('status', 2));
        $this->assertFalse(Form::inputSelection('status', 1));
        $this->assertEqual(Form::selected('status', 2), 'selected="selected"');
        $this->assertEqual(Form::checked('status', 2), 'checked="checked"');
    }

    public function testForInputSelectionWithDefault()
    {
        $this->assertTrue(Form::inputSelection('status', 2, 2));
        $this->assertFalse(Form::inputSelection('status', 2, 1));
    }

    public function testForInputSelectionWithArrayValue()
    {
        $_POST = array('group' => array('a', 'b'));

        $this->assertTrue(Form::inputSelection('group[]', 'a'));
        $this->assertFalse(Form::inputSelection('group[]', 'z'));
    }

    public function testForValueHelper()
    {
        $this->assertEqual(Form::value('name', 'Default'), _h('Default'));

        $_POST = array('name' => '<b>x</b>');
        $this->assertEqual(Form::value('name', 'Default'), _h('<b>x</b>'));
    }
}