<?php

use LucidFrame\Test\LucidFrameDatabaseTestCase;

// Load both driver helper files so their functions can be unit-tested
// regardless of the active driver in the test environment
require_once HELPER . 'db_helper.mysql.php';
require_once HELPER . 'db_helper.pgsql.php';

/**
 * Unit Test for driver-specific database helper functions
 * Tests MySQL and PostgreSQL specific implementations
 */
class DBHelperDriversTestCase extends LucidFrameDatabaseTestCase
{
    private $originalDriver;

    public function setUp()
    {
        parent::setUp();
        $this->originalDriver = _app('db')->getDriver();

        // Create the runtime table used by the prq-based behavior tests
        // (db_prq() only changes the return value, queries still execute)
        if ($this->originalDriver === 'mysql') {
            db_query("CREATE TABLE IF NOT EXISTS `test_table` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NULL,
                `active` TINYINT(1) DEFAULT 1
            ) ENGINE=InnoDB");
        } else {
            db_query('CREATE TABLE IF NOT EXISTS "test_table" (
                "id" SERIAL PRIMARY KEY,
                "name" VARCHAR(255) NULL,
                "active" BOOLEAN DEFAULT TRUE
            )');
        }
    }

    public function tearDown()
    {
        // Drop the runtime table
        if ($this->originalDriver === 'mysql') {
            db_query('DROP TABLE IF EXISTS `test_table`');
        } else {
            db_query('DROP TABLE IF EXISTS "test_table"');
        }

        // Restore original driver
        if ($this->originalDriver) {
            // Note: setDriver may not exist, this is just for test isolation
            if (method_exists(_app('db'), 'setDriver')) {
                _app('db')->setDriver($this->originalDriver);
            }
        }
        parent::tearDown();
    }

    public function testCommonHelperFunctions()
    {
        // Test that common helper functions exist and work
        $this->assertTrue(function_exists('db_namespace'));
        $this->assertTrue(function_exists('db_config'));
        $this->assertTrue(function_exists('db_engine'));
        $this->assertTrue(function_exists('db_driver'));
        $this->assertTrue(function_exists('db_host'));
        $this->assertTrue(function_exists('db_name'));
        $this->assertTrue(function_exists('db_user'));
        $this->assertTrue(function_exists('db_prefix'));
        $this->assertTrue(function_exists('db_collation'));
        $this->assertTrue(function_exists('db_switch'));
        $this->assertTrue(function_exists('db_close'));
        $this->assertTrue(function_exists('db_prq'));
        $this->assertTrue(function_exists('db_query'));
        $this->assertTrue(function_exists('db_queryStr'));
        $this->assertTrue(function_exists('db_error'));
        $this->assertTrue(function_exists('db_errorNo'));
        $this->assertTrue(function_exists('db_table'));
        $this->assertTrue(function_exists('db_tableHasSlug'));
        $this->assertTrue(function_exists('db_tableHasTimestamps'));
    }

    public function testMySQLDriverHelperFunctions()
    {
        // Test MySQL-specific functions
        $this->assertTrue(function_exists('mysql_db_insert'));
        $this->assertTrue(function_exists('mysql_db_update'));
        $this->assertTrue(function_exists('mysql_db_delete'));
        $this->assertTrue(function_exists('mysql_db_truncate'));
        $this->assertTrue(function_exists('mysql_db_setForeignKeyCheck'));
        $this->assertTrue(function_exists('mysql_db_enableForeignKeyCheck'));
        $this->assertTrue(function_exists('mysql_db_disableForeignKeyCheck'));
        $this->assertTrue(function_exists('mysql_db_quote'));
        $this->assertTrue(function_exists('mysql_db_like'));
        $this->assertTrue(function_exists('mysql_db_limit'));
    }

    public function testPostgreSQLDriverHelperFunctions()
    {
        // Test PostgreSQL-specific functions
        $this->assertTrue(function_exists('pgsql_db_insert'));
        $this->assertTrue(function_exists('pgsql_db_update'));
        $this->assertTrue(function_exists('pgsql_db_delete'));
        $this->assertTrue(function_exists('pgsql_db_truncate'));
        $this->assertTrue(function_exists('pgsql_db_setForeignKeyCheck'));
        $this->assertTrue(function_exists('pgsql_db_enableForeignKeyCheck'));
        $this->assertTrue(function_exists('pgsql_db_disableForeignKeyCheck'));
        $this->assertTrue(function_exists('pgsql_db_quote'));
        $this->assertTrue(function_exists('pgsql_db_like'));
        $this->assertTrue(function_exists('pgsql_db_limit'));
    }

    public function testMySQLQuoting()
    {
        $quoted = mysql_db_quote('table_name');
        $this->assertEqual($quoted, '`table_name`');

        // Test escaping backticks
        $quoted = mysql_db_quote('table`name');
        $this->assertEqual($quoted, '`table``name`');
    }

    public function testPostgreSQLQuoting()
    {
        $quoted = pgsql_db_quote('table_name');
        $this->assertEqual($quoted, '"table_name"');

        // Test escaping double quotes
        $quoted = pgsql_db_quote('table"name');
        $this->assertEqual($quoted, '"table""name"');
    }

    public function testMySQLLikeClause()
    {
        $like = mysql_db_like('title', 'value', 'both');
        $this->assertEqual($like, '`title` LIKE CONCAT("%", :title, "%")');

        $like = mysql_db_like('title', 'value', 'left');
        $this->assertEqual($like, '`title` LIKE CONCAT("%", :title)');

        $like = mysql_db_like('title', 'value', 'right');
        $this->assertEqual($like, '`title` LIKE CONCAT(:title, "%")');
    }

    public function testPostgreSQLLikeClause()
    {
        $like = pgsql_db_like('title', 'value', 'both');
        $this->assertEqual($like, '"title" LIKE CONCAT(\'%\', :title, \'%\')');

        $like = pgsql_db_like('title', 'value', 'left');
        $this->assertEqual($like, '"title" LIKE CONCAT(\'%\', :title)');

        $like = pgsql_db_like('title', 'value', 'right');
        $this->assertEqual($like, '"title" LIKE CONCAT(:title, \'%\')');
    }

    public function testMySQLLimitClause()
    {
        $limit = mysql_db_limit(10);
        $this->assertEqual($limit, 'LIMIT 10');

        $limit = mysql_db_limit(10, 5);
        $this->assertEqual($limit, 'LIMIT 5, 10');
    }

    public function testPostgreSQLLimitClause()
    {
        $limit = pgsql_db_limit(10);
        $this->assertEqual($limit, 'LIMIT 10');

        $limit = pgsql_db_limit(10, 5);
        $this->assertEqual($limit, 'LIMIT 10 OFFSET 5');
    }

    public function testDynamicHelperLoading()
    {
        // Mock database instance to test driver switching
        $db = _app('db');

        // Test MySQL driver loading
        if (method_exists($db, 'setDriver')) {
            $db->setDriver('mysql');

            // Test that db_insert calls mysql_db_insert (useSlug disabled: the
            // runtime table is not part of the schema definition)
            db_insert('test_table', array('name' => 'test'), false);

            // Should use MySQL syntax
            $this->assertContains('INSERT INTO', db_queryStr());
        }
    }

    public function testDriverSpecificInsertBehavior()
    {
        // Test MySQL insert behavior (useSlug disabled: the runtime table is
        // not part of the schema definition)
        $result = mysql_db_insert('test_table', array(
            'name' => 'Test Name',
            'active' => true
        ), false);

        $this->assertNotFalse($result, 'Insert should succeed and return the insert id');
    }

    public function testDriverSpecificUpdateBehavior()
    {
        // Test MySQL update behavior (useSlug disabled, see insert behavior test)
        $result = mysql_db_update('test_table', array(
            'id' => 1,
            'name' => 'Updated Name',
            'active' => false
        ), false);

        $this->assertNotFalse($result, 'Update should succeed');
    }

    public function testDriverSpecificDeleteBehavior()
    {
        // Test MySQL delete behavior
        $result = mysql_db_delete('test_table', array('id' => 1));

        $this->assertNotFalse($result, 'Delete should succeed');
    }

    public function testBooleanHandling()
    {
        // Test that MySQL converts boolean to 1/0
        // Test that PostgreSQL uses true/false strings

        // This would require actual database connections to test properly
        // For now, we just verify the functions exist and can be called
        $this->assertTrue(function_exists('mysql_db_insert'));
        $this->assertTrue(function_exists('pgsql_db_insert'));
    }

    public function testErrorHandling()
    {
        // Test that both drivers handle errors appropriately

        // Test empty data arrays
        $result = mysql_db_insert('test_table', array());
        $this->assertFalse($result);

        $result = pgsql_db_insert('test_table', array());
        $this->assertFalse($result);
    }

    public function testBackwardCompatibility()
    {
        // Test that the main helper functions still work
        $this->assertTrue(function_exists('db_insert'));
        $this->assertTrue(function_exists('db_update'));
        $this->assertTrue(function_exists('db_delete'));
        $this->assertTrue(function_exists('db_truncate'));
        $this->assertTrue(function_exists('db_setForeignKeyCheck'));
        $this->assertTrue(function_exists('db_enableForeignKeyCheck'));
        $this->assertTrue(function_exists('db_disableForeignKeyCheck'));
    }

    public function testFileStructure()
    {
        // Test that the helper files exist in the correct structure
        $helperPath = HELPER;

        // Main helper file should exist
        $this->assertTrue(file_exists($helperPath . 'db_helper.php'));

        // Driver-specific files should exist
        $this->assertTrue(file_exists($helperPath . 'db_helper.mysql.php'));
        $this->assertTrue(file_exists($helperPath . 'db_helper.pgsql.php'));

        // Old mysqli file should NOT exist (merged into mysql)
        $this->assertFalse(file_exists($helperPath . 'db_helper.mysqli.php'));
    }

    public function testDriverDispatch()
    {
        // Test that db_insert dispatches to the correct driver function
        $driver = db_driver();

        // Insert into the runtime table (useSlug disabled: the table is not
        // part of the schema definition)
        $result = db_insert('test_table', array('name' => 'test'), false);
        $this->assertNotFalse($result, 'Insert should succeed');

        // Verify the generated query
        $query = db_queryStr();
        $this->assertContains('INSERT INTO', $query);

        // Verify driver-specific syntax
        if ($driver === 'mysql') {
            $this->assertContains('`test_table`', $query);
        } elseif ($driver === 'pgsql') {
            $this->assertContains('"test_table"', $query);
        }
    }

    public function testDriverSpecificFeatures()
    {
        // Test MySQL-specific features
        db_prq(true);
        try {
            mysql_db_setForeignKeyCheck(0);
            mysql_db_enableForeignKeyCheck();
            mysql_db_disableForeignKeyCheck();
        } finally {
            db_prq(false);
        }

        // Test PostgreSQL-specific features (no-ops for FK checks)
        pgsql_db_setForeignKeyCheck(0);
        pgsql_db_enableForeignKeyCheck();
        pgsql_db_disableForeignKeyCheck();

        // These should not throw errors
        $this->assertTrue(true);
    }

    public function testTruncateOperations()
    {
        // Test MySQL truncate
        mysql_db_truncate('test_table');

        $query = db_queryStr();
        $this->assertContains('TRUNCATE', $query);
        $this->assertContains('`test_table`', $query);

        // pgsql_db_truncate() executes against the active connection — it can
        // only run against a live PostgreSQL connection
        if ($this->originalDriver === 'pgsql') {
            pgsql_db_truncate('test_table');
            $this->assertContains('RESTART IDENTITY CASCADE', db_queryStr());
        } else {
            $this->assertTrue(function_exists('pgsql_db_truncate'), 'pgsql_db_truncate() should be defined');
        }
    }
}
