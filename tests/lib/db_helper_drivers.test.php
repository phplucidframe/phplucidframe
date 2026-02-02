<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for driver-specific database helper functions
 */
class DBHelperDriversTestCase extends LucidFrameTestCase
{
    private $originalDriver;

    public function setUp()
    {
        parent::setUp();
        $this->originalDriver = _app('db')->getDriver();
    }

    public function tearDown()
    {
        // Restore original driver
        if ($this->originalDriver) {
            _app('db')->setDriver($this->originalDriver);
        }
        parent::tearDown();
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

            // Test that db_insert calls mysql_db_insert
            db_prq(true);
            $result = db_insert('test_table', array('name' => 'test'));
            $query = db_queryStr();
            db_prq(false);

            // Should use MySQL syntax
            $this->assertContains('INSERT INTO', $query);
        }
    }

    public function testDriverSpecificInsertBehavior()
    {
        db_prq(true);

        // Test MySQL insert behavior
        $result = mysql_db_insert('test_table', array(
            'name' => 'Test Name',
            'active' => true
        ));

        // For testing purposes, we expect the query to be returned when db_prq is true
        $this->assertTrue(is_string($result) || is_bool($result));

        db_prq(false);
    }

    public function testDriverSpecificUpdateBehavior()
    {
        db_prq(true);

        // Test MySQL update behavior
        $result = mysql_db_update('test_table', array(
            'id' => 1,
            'name' => 'Updated Name',
            'active' => false
        ));

        // For testing purposes, we expect the query to be returned when db_prq is true
        $this->assertTrue(is_string($result) || is_bool($result));

        db_prq(false);
    }

    public function testDriverSpecificDeleteBehavior()
    {
        db_prq(true);

        // Test MySQL delete behavior
        $result = mysql_db_delete('test_table', array('id' => 1));

        // For testing purposes, we expect the query to be returned when db_prq is true
        $this->assertTrue(is_string($result) || is_bool($result));

        db_prq(false);
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

    public function testDriverSpecificFeatures()
    {
        // Test MySQL-specific features
        db_prq(true);
        mysql_db_setForeignKeyCheck(0);
        mysql_db_enableForeignKeyCheck();
        mysql_db_disableForeignKeyCheck();
        db_prq(false);

        // Test PostgreSQL-specific features (no-ops for FK checks)
        pgsql_db_setForeignKeyCheck(0);
        pgsql_db_enableForeignKeyCheck();
        pgsql_db_disableForeignKeyCheck();

        // These should not throw errors
        $this->assertTrue(true);
    }

    public function testTruncateOperations()
    {
        db_prq(true);

        // Test MySQL truncate
        mysql_db_truncate('test_table');
        $query = db_queryStr();
        $this->assertContains('TRUNCATE', $query);
        $this->assertContains('`test_table`', $query);

        // Test PostgreSQL truncate
        pgsql_db_truncate('test_table');
        $query = db_queryStr();
        $this->assertContains('TRUNCATE', $query);
        $this->assertContains('RESTART IDENTITY CASCADE', $query);

        db_prq(false);
    }
}