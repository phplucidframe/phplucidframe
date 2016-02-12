<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class is a base class for all unit test cases.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

class LucidFrameTestCase extends UnitTestCase
{
    public function setUp()
    {
        db_delete_multi('document');
        db_delete_multi('post_image');
        db_delete_multi('post');
        db_delete_multi('category');
        db_delete_multi('lc_sessions');
        db_delete_multi('user');

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
    }

    public static function oneline($string)
    {
        return preg_replace('/\s{2,}/u', ' ', trim($string));
    }
}
