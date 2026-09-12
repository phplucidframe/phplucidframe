<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Validation;

/**
 * Unit Test for the Validation class (lib/classes/Validation.php)
 * Covers rule checking, error collection, custom messages, batch rules for
 * arrays of values, single/multi error types and set/get accessors.
 * (The `validate_*` helper functions themselves are covered by
 * helpers/validation_helper.test.php.)
 */
class ValidationClassTest extends LucidFrameTestCase
{
    public function setUp()
    {
        parent::setUp();

        Validation::$errors = array();
    }

    public function tearDown()
    {
        Validation::$errors = array();

        parent::tearDown();
    }

    public function testForSetAndGet()
    {
        Validation::set('errors', array(array('field' => 'x', 'message' => 'y')));

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertEqual($errors[0]['field'], 'x');
    }

    public function testForAddError()
    {
        Validation::addError('name-field', 'The name is invalid.');

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertEqual($errors[0]['field'], 'name-field');
        $this->assertEqual($errors[0]['message'], 'The name is invalid.');
    }

    public function testForMessagesAreInitialized()
    {
        // __validation_init() ran at bootstrap
        $messages = Validation::get('messages');

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('mandatory', $messages);
        $this->assertArrayHasKey('email', $messages);
    }

    public function testForCheckSuccess()
    {
        $validations = array(
            'name'  => array(
                'caption' => 'Name',
                'value'   => 'John Doe',
                'rules'   => array('mandatory', 'alphaNumericSpace'),
            ),
            'email' => array(
                'caption' => 'Email',
                'value'   => 'john@example.com',
                'rules'   => array('email'),
            ),
        );

        $this->assertTrue(Validation::check($validations, array()));
        $this->assertCount(0, Validation::get('errors'));
    }

    public function testForCheckLooksUpValuesInData()
    {
        $validations = array(
            'email' => array(
                'caption' => 'Email',
                'rules'   => array('mandatory', 'email'),
            ),
        );
        $data = array('email' => 'john@example.com');

        $this->assertTrue(Validation::check($validations, $data));
    }

    public function testForCheckFailureUsesDefaultMessage()
    {
        $validations = array(
            'name' => array(
                'caption' => 'Name',
                'value'   => '',
                'rules'   => array('mandatory'),
            ),
        );

        $this->assertFalse(Validation::check($validations, array()));

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertEqual($errors[0]['field'], 'name');
        $this->assertPattern("/'Name' is required\./", $errors[0]['message']);
    }

    public function testForCheckWithCustomMessage()
    {
        $validations = array(
            'name' => array(
                'caption'  => 'Name',
                'value'    => '',
                'rules'    => array('mandatory'),
                'messages' => array('mandatory' => 'Your name is missing.'),
            ),
        );

        $this->assertFalse(Validation::check($validations, array()));

        $errors = Validation::get('errors');
        $this->assertCount(1, $errors);
        $this->assertEqual($errors[0]['message'], 'Your name is missing.');
    }

    public function testForCheckLengthRules()
    {
        $validations = array(
            'code' => array(
                'caption' => 'Code',
                'value'   => 'ABC',
                'rules'   => array('exactLength'),
                'length'  => 4,
            ),
        );

        $this->assertFalse(Validation::check($validations, array()));
        $errors = Validation::get('errors');
        $this->assertPattern("/exact length of 4/", $errors[0]['message']);

        $validations['code']['length'] = 3;
        $this->assertTrue(Validation::check($validations, array()));
    }

    public function testForCheckMinMaxLengthRules()
    {
        $validations = array(
            'code' => array(
                'caption' => 'Code',
                'value'   => 'AB',
                'rules'   => array('minLength'),
                'min'     => 4,
            ),
        );

        $this->assertFalse(Validation::check($validations, array()));

        $validations['code']['value'] = 'ABCD';
        $this->assertTrue(Validation::check($validations, array()));

        $validations['code']['rules'] = array('maxLength');
        $validations['code']['max'] = 4;
        $this->assertTrue(Validation::check($validations, array()));

        $validations['code']['value'] = 'ABCDE';
        $this->assertFalse(Validation::check($validations, array()));
    }

    public function testForCheckMinMaxNumberRules()
    {
        $validations = array(
            'age' => array(
                'caption' => 'Age',
                'value'   => 15,
                'rules'   => array('min'),
                'min'     => 18,
            ),
        );

        $this->assertFalse(Validation::check($validations, array()));

        $validations['age']['value'] = 20;
        $this->assertTrue(Validation::check($validations, array()));

        $validations['age']['rules'] = array('max');
        $validations['age']['max'] = 30;
        $this->assertTrue(Validation::check($validations, array()));
    }

    public function testForCheckSingleTypeStopsAtFirstError()
    {
        $validations = array(
            'name'  => array(
                'caption' => 'Name',
                'value'   => '',
                'rules'   => array('mandatory'),
            ),
            'email' => array(
                'caption' => 'Email',
                'value'   => 'invalid',
                'rules'   => array('email'),
            ),
        );

        $this->assertFalse(Validation::check($validations, array(), Validation::TYPE_SINGLE));
        $this->assertCount(1, Validation::get('errors'));
    }

    public function testForCheckMultiTypeCollectsAllErrors()
    {
        $validations = array(
            'name'  => array(
                'caption' => 'Name',
                'value'   => '',
                'rules'   => array('mandatory'),
            ),
            'email' => array(
                'caption' => 'Email',
                'value'   => 'invalid',
                'rules'   => array('email'),
            ),
        );

        $this->assertFalse(Validation::check($validations, array(), Validation::TYPE_MULTI));
        $this->assertCount(2, Validation::get('errors'));
    }

    public function testForCheckUnknownTypeFallsBackToMulti()
    {
        $validations = array(
            'name'  => array(
                'caption' => 'Name',
                'value'   => '',
                'rules'   => array('mandatory'),
            ),
            'email' => array(
                'caption' => 'Email',
                'value'   => 'invalid',
                'rules'   => array('email'),
            ),
        );

        $this->assertFalse(Validation::check($validations, array(), 'bogus-type'));
        $this->assertCount(2, Validation::get('errors'));
    }

    public function testForCheckBatchRulesOnArrayOfValues()
    {
        // mandatoryOne: at least one value must be present
        $validations = array(
            'choice' => array(
                'caption' => 'Choice',
                'value'   => array('', ''),
                'rules'   => array('mandatoryOne'),
            ),
        );
        $this->assertFalse(Validation::check($validations, array()));

        $validations['choice']['value'] = array('', 'picked');
        $this->assertTrue(Validation::check($validations, array()));

        // mandatoryAll: all values must be free of whitespace
        $validations['choice']['rules'] = array('mandatoryAll');
        $validations['choice']['value'] = array('a', ' ');
        $this->assertFalse(Validation::check($validations, array()));

        $validations['choice']['value'] = array('a', 'b');
        $this->assertTrue(Validation::check($validations, array()));
    }

    public function testForCheckUnknownRuleIsIgnored()
    {
        $validations = array(
            'name' => array(
                'caption' => 'Name',
                'value'   => 'John',
                'rules'   => array('noSuchRule'),
            ),
        );

        $this->assertTrue(Validation::check($validations, array()));
    }

    public function testForCheckCustomRuleWithParameters()
    {
        $validations = array(
            'code' => array(
                'caption' => 'Code',
                'value'   => 'A1',
                'rules'   => array('validate_alphaNumeric'),
            ),
        );

        $this->assertTrue(Validation::check($validations, array()));

        $validations['code']['value'] = 'A 1';
        $this->assertFalse(Validation::check($validations, array()));
    }
}