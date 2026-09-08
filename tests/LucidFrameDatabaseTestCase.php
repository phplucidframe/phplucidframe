<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Base class for database-backed unit test cases.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 3.5.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

/**
 * Base class for tests that need the sample database.
 * Truncates the sample tables and re-inserts the default admin user before each test.
 */
class LucidFrameDatabaseTestCase extends LucidFrameTestCase
{
    /** @var \LucidFrame\Core\db\Database|null Snapshot of the shared DB instance, restored after each test */
    protected $lcSavedDb;

    public function setUp()
    {
        parent::setUp();

        // Snapshot the shared DB instance so tests that create their own
        // Database instances (multi-driver tests etc.) cannot poison the
        // global state for subsequent tests
        $this->lcSavedDb = _app('db');

        $this->cleanup();
    }

    public function tearDown()
    {
        // Restore the shared DB instance
        if ($this->lcSavedDb) {
            _app('db', $this->lcSavedDb);
        }
        $this->lcSavedDb = null;

        parent::tearDown();
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
            'password'  => password_hash('pwd@admin', PASSWORD_DEFAULT),
            'email'     => 'admin@localhost.com',
            'role'      => 'admin',
            'is_master' => 1
        ));
    }

    /**
     * Build a DB connection configuration array for the given driver,
     * reading every value from inc/parameter/env.inc via _env().
     *
     * Supported drivers: "mysql" and "pgsql".
     *
     * @param string $driver "mysql" or "pgsql"
     * @return array
     */
    protected function getDbConfig($driver = 'mysql')
    {
        $driver = ($driver === 'pgsql') ? 'pgsql' : 'mysql';

        $config = array(
            'driver'    => $driver,
            'host'      => _env("test.db.sample.{$driver}.host"),
            'port'      => _env("test.db.sample.{$driver}.port"),
            'database'  => _env("test.db.sample.{$driver}.database"),
            'username'  => _env("test.db.sample.{$driver}.username"),
            'password'  => _env("test.db.sample.{$driver}.password"),
            'charset'   => _env("test.db.sample.{$driver}.charset"),
            'collation' => _env("test.db.sample.{$driver}.collation"),
        );

        if ($driver === 'pgsql') {
            $config['schema'] = _env('test.db.sample.pgsql.schema');
        }

        return $config;
    }
}
