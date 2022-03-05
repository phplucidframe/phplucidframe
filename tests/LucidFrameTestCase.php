<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class is a base class for all unit test cases.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

class LucidFrameTestCase extends \UnitTestCase
{
    public function setUp()
    {
        $this->cleanup();
    }

    public function tearDown()
    {
    }

    public static function oneline($string)
    {
        return preg_replace('/\s{2,}/u', ' ', trim($string));
    }

    public static function toSql($clause, $values = array())
    {
        foreach ($values as $key => $value) {
            $clause = preg_replace('/' . $key . '\b/', $value, $clause);
        }

        return $clause;
    }

    protected function cleanup()
    {
        // Data cleanup by each test run
        // This is an example for the sample database
        db_setForeignKeyCheck(0);

        db_truncate('document');
        db_truncate('post_to_tag');
        db_truncate('post_image');
        db_truncate('post');
        db_truncate('tag');
        db_truncate('category');
        db_truncate('lc_sessions');
        db_truncate('social_profile');
        db_truncate('user');

        db_setForeignKeyCheck(1);

        db_insert('user', array(
            'full_name' => 'Administrator',
            'username'  => 'admin',
            'password'  => _encrypt('pwd@admin'),
            'email'     => 'admin@localhost.com',
            'role'      => 'admin',
            'is_master' => 1
        ));
    }
}
