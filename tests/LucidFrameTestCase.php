<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class is a base class for all unit test cases.
 *
 * @package     LC\tests
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

if (!defined('VENDOR')) {
    require_once 'bootstrap.php';
}

require_once VENDOR . 'simpletest/autorun.php';

class LucidFrameTestCase extends UnitTestCase
{
    protected $dbConnection = false;

    public function setUp()
    {
        db_switch('test');

        db_insert('user', array(
            'fullName'  => 'Administrator',
            'uid'       => 1,
            'username'  => 'admin',
            'password'  => _encrypt('admin'),
            'email'     => 'admin@localhost.com',
            'role'      => 'admin',
            'isMaster'  => 1
        ));
    }

    public function tearDown()
    {
        db_delete('document');
        db_delete('post_image');
        db_delete('post');
        db_delete('category');
        db_delete('user');
        db_delete('lc_sessions');

        db_switch();
    }

    public static function oneline($string)
    {
        return preg_replace('/\s{2,}/u', ' ', trim($string));
    }
}
