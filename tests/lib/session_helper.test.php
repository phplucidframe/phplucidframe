<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for session_helper.php
 */
class SessionHelperTestCase extends LucidFrameTestCase
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // clear session
        $_SESSION = array();
    }
    /**
     * Test cases for _g()
     */
    public function testSessionSetterGetter()
    {
        // 1.
        session_set('name.first', 'Sithu');
        session_set('name.last', 'Kyaw');
        $this->assertEqual(session_get('name'), array(
            'first' => 'Sithu',
            'last' => 'Kyaw'
        ));
        // 2.
        session_set('name.first', 'Kyaw');
        $this->assertEqual(session_get('name'), array(
            'first' => 'Kyaw',
            'last' => 'Kyaw'
        ));
        // 3.
        session_set('foo', 'bar');
        $this->assertEqual(session_get('foo'), 'bar');
        // 4.
        $animals = array('dog', 'cat', 'tiger');
        session_set('animals', $animals);
        $this->assertEqual(session_get('animals'), array('dog', 'cat', 'tiger'));
        // 5.
        session_set('user', array(
            'fullName' => 'Sithu Kyaw',
            'firstName' => 'Sithu',
            'lastName' => 'Kyaw',
            'age' => 31,
            'phone' => array('123456', '987654'),
            'address' => array(
                'street' => array(
                    'no' => 1,
                    'room' => 2,
                    'street' => 'Main Street',
                ),
                'city' => 'Yangon',
                'country' => 'Myanmar',
                'zip' => '11001'
            )
        ));
        $this->assertEqual(session_get('user'), array(
            'fullName' => 'Sithu Kyaw',
            'firstName' => 'Sithu',
            'lastName' => 'Kyaw',
            'age' => 31,
            'phone' => array('123456', '987654'),
            'address' => array(
                'street' => array(
                    'no' => 1,
                    'room' => 2,
                    'street' => 'Main Street',
                ),
                'city' => 'Yangon',
                'country' => 'Myanmar',
                'zip' => '11001'
            )
        ));
        // 6.
        session_set('user.phone', '123456');
        session_set('user.address.zip', '11111');
        $this->assertEqual(session_get('user'), array(
            'fullName' => 'Sithu Kyaw',
            'firstName' => 'Sithu',
            'lastName' => 'Kyaw',
            'age' => 31,
            'phone' => '123456',
            'address' => array(
                'street' => array(
                    'no' => 1,
                    'room' => 2,
                    'street' => 'Main Street',
                ),
                'city' => 'Yangon',
                'country' => 'Myanmar',
                'zip' => '11111'
            )
        ));
        // 7.
        $auth = array(
            'name' => 'tetete',
            'email' => 'tetete@localhost.com'
        );
        session_set('auth', $auth, true);
        $this->assertEqual(session_get('auth', true), $auth);
    }

    public function testForSessionDelete()
    {
        // 1.
        session_delete('name');
        $this->assertNull(session_get('name'));
        // 2.
        session_delete('foo');
        $this->assertNull(session_get('foo'));
        // 3.
        session_delete('animals');
        $this->assertNull(session_get('animals'));
        // 4.
        session_delete('user.fullName');
        session_delete('user.address.street.room');
        session_delete('user.address.zip');
        $this->assertEqual(session_get('user'), array(
            'firstName' => 'Sithu',
            'lastName' => 'Kyaw',
            'age' => 31,
            'phone' => '123456',
            'address' => array(
                'street' => array(
                    'no' => 1,
                    'street' => 'Main Street',
                ),
                'city' => 'Yangon',
                'country' => 'Myanmar'
            )
        ));
        // 5.
        session_delete('user');
        $this->assertNull(session_get('user'));
    }

    public function testForFlashSetterGetter()
    {
        // 1.
        $msg = 'This is success flash message.';
        flash_set($msg);
        $msg = '<span class="success">'.$msg.'</span>';
        $expectedOutput = '<div class="message success" style="display:block;"><ul><li>'.$msg.'</li></ul></div>';
        $this->assertEqual(flash_get(), $expectedOutput);

        // 2.
        $msg = 'This is error flash message.';
        flash_set($msg, null, 'error');
        $msg = '<span class="error">'.$msg.'</span>';
        $expectedOutput = '<div class="message error" style="display:block;"><ul><li>'.$msg.'</li></ul></div>';
        $this->assertEqual(flash_get(), $expectedOutput);

        // 3.
        $msg = array(
            '(1) This is array of flash messages.',
            '(2) This is array of flash messages.'
        );
        flash_set($msg, null, 'error');
        $expectedOutput  = '<div class="message error" style="display:block;"><ul>';
        $expectedOutput .= '<li><span class="error">(1) This is array of flash messages.</span></li>';
        $expectedOutput .= '<li><span class="error">(2) This is array of flash messages.</span></li>';
        $expectedOutput .= '</ul></div>';
        $this->assertEqual(flash_get(), $expectedOutput);
    }
}
