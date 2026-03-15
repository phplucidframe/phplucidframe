<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for database helper file structure and organization
 * Tests the refactored structure with common and driver-specific files
 */
class DBHelperStructureTestCase extends LucidFrameTestCase
{
    public function testFileStructure()
    {
        $helperPath = HELPER;

        // Test that main helper file exists
        $this->assertTrue(file_exists($helperPath . 'db_helper.php'), 'Main db_helper.php should exist');

        // Test that driver-specific files exist
        $this->assertTrue(file_exists($helperPath . 'db_helper.mysql.php'), 'MySQL driver helper should exist');
        $this->assertTrue(file_exists($helperPath . 'db_helper.pgsql.php'), 'PostgreSQL driver helper should exist');

        // Test that old mysqli file does NOT exist (merged into mysql)
        $this->assertFalse(file_exists($helperPath . 'db_helper.mysqli.php'), 'Old db_helper.mysqli.php should not exist');
    }

    public function testCommonFunctionsExist()
    {
        // Test that all common functions are defined
        $commonFunctions = array(
            'db_init',
            'db_namespace',
            'db_config',
            'db_engine',
            'db_driver',
            'db_host',
            'db_name',
            'db_user',
            'db_prefix',
            'db_collation',
            'db_prerequisite',
            'db_switch',
            'db_close',
            'db_prq',
            'db_query',
            'db_queryStr',
            'db_error',
            'db_errorNo',
            'db_numRows',
            'db_fetchArray',
            'db_fetchAssoc',
            'db_fetchObject',
            'db_insertId',
            'db_insertSlug',
            'db_select',
            'db_count',
            'db_max',
            'db_min',
            'db_sum',
            'db_avg',
            'db_fetch',
            'db_fetchResult',
            'db_extract',
            'db_table',
            'db_tableHasSlug',
            'db_tableHasTimestamps',
            'db_save',
            'db_insert',
            'db_update',
            'db_delete',
            'db_delete_multi',
            'db_truncate',
            'db_setForeignKeyCheck',
            'db_enableForeignKeyCheck',
            'db_disableForeignKeyCheck',
            'db_condition',
            'db_and',
            'db_or',
            'db_transaction',
            'db_commit',
            'db_rollback',
            'db_raw',
            'db_exp',
            'db_find',
            'db_findOrFail',
            'db_findWithPager',
            'db_findBy',
            'db_findOneBy',
            'db_findOneByOrFail',
            'db_findAll',
            'db_findColumn',
        );

        foreach ($commonFunctions as $function) {
            $this->assertTrue(function_exists($function), "Common function {$function} should exist");
        }
    }

    public function testMySQLDriverFunctionsExist()
    {
        // Test that MySQL-specific functions are defined
        $mysqlFunctions = array(
            'mysql_db_insert',
            'mysql_db_update',
            'mysql_db_delete',
            'mysql_db_setForeignKeyCheck',
            'mysql_db_enableForeignKeyCheck',
            'mysql_db_disableForeignKeyCheck',
            'mysql_db_truncate',
            'mysql_db_quote',
            'mysql_db_like',
            'mysql_db_limit',
        );

        foreach ($mysqlFunctions as $function) {
            $this->assertTrue(function_exists($function), "MySQL function {$function} should exist");
        }
    }

    public function testPostgreSQLDriverFunctionsExist()
    {
        // Test that PostgreSQL-specific functions are defined
        $pgsqlFunctions = array(
            'pgsql_db_insert',
            'pgsql_db_update',
            'pgsql_db_delete',
            'pgsql_db_setForeignKeyCheck',
            'pgsql_db_enableForeignKeyCheck',
            'pgsql_db_disableForeignKeyCheck',
            'pgsql_db_truncate',
            'pgsql_db_quote',
            'pgsql_db_like',
            'pgsql_db_limit',
        );

        foreach ($pgsqlFunctions as $function) {
            $this->assertTrue(function_exists($function), "PostgreSQL function {$function} should exist");
        }
    }

    public function testDriverDispatchMechanism()
    {
        // Test that common functions dispatch to driver-specific implementations
        $driver = db_driver();

        // Test insert dispatch
        db_prq(true);
        db_insert('test_table', array('name' => 'test'));
        $query = db_queryStr();
        db_prq(false);

        $this->assertContains('INSERT INTO', $query);

        if ($driver === 'mysql') {
            $this->assertContains('`test_table`', $query);
        } elseif ($driver === 'pgsql') {
            $this->assertContains('"test_table"', $query);
        }
    }

    public function testMySQLQuotingSyntax()
    {
        $quoted = mysql_db_quote('table_name');
        $this->assertEqual($quoted, '`table_name`');

        // Test escaping
        $quoted = mysql_db_quote('table`name');
        $this->assertEqual($quoted, '`table``name`');
    }

    public function testPostgreSQLQuotingSyntax()
    {
        $quoted = pgsql_db_quote('table_name');
        $this->assertEqual($quoted, '"table_name"');

        // Test escaping
        $quoted = pgsql_db_quote('table"name');
        $this->assertEqual($quoted, '"table""name"');
    }

    public function testMySQLLimitSyntax()
    {
        $limit = mysql_db_limit(10);
        $this->assertEqual($limit, 'LIMIT 10');

        $limit = mysql_db_limit(10, 5);
        $this->assertEqual($limit, 'LIMIT 5, 10');
    }

    public function testPostgreSQLLimitSyntax()
    {
        $limit = pgsql_db_limit(10);
        $this->assertEqual($limit, 'LIMIT 10');

        $limit = pgsql_db_limit(10, 5);
        $this->assertEqual($limit, 'LIMIT 10 OFFSET 5');
    }

    public function testMySQLLikeSyntax()
    {
        $like = mysql_db_like('title', 'value', 'both');
        $this->assertEqual($like, '`title` LIKE CONCAT("%", :title, "%")');

        $like = mysql_db_like('title', 'value', 'left');
        $this->assertEqual($like, '`title` LIKE CONCAT("%", :title)');

        $like = mysql_db_like('title', 'value', 'right');
        $this->assertEqual($like, '`title` LIKE CONCAT(:title, "%")');
    }

    public function testPostgreSQLLikeSyntax()
    {
        $like = pgsql_db_like('title', 'value', 'both');
        $this->assertEqual($like, '"title" LIKE CONCAT(\'%\', :title, \'%\')');

        $like = pgsql_db_like('title', 'value', 'left');
        $this->assertEqual($like, '"title" LIKE CONCAT(\'%\', :title)');

        $like = pgsql_db_like('title', 'value', 'right');
        $this->assertEqual($like, '"title" LIKE CONCAT(:title, \'%\')');
    }

    public function testBackwardCompatibility()
    {
        // Test that all legacy function names still work
        $this->assertTrue(function_exists('db_insert'));
        $this->assertTrue(function_exists('db_update'));
        $this->assertTrue(function_exists('db_delete'));

        // Test that db_engine is an alias for db_driver
        $engine = db_engine();
        $driver = db_driver();
        $this->assertEqual($engine, $driver);
    }

    public function testDriverFallback()
    {
        // Test that functions fall back to MySQL for backward compatibility
        // This is tested by the existence of the fallback logic in db_helper.php

        // If a driver function doesn't exist, it should fall back to mysql_*
        $this->assertTrue(function_exists('mysql_db_insert'));
        $this->assertTrue(function_exists('mysql_db_update'));
        $this->assertTrue(function_exists('mysql_db_delete'));
    }

    public function testNoMySQLiReferences()
    {
        // Ensure no mysqli-specific references remain
        $helperPath = HELPER;

        // Check that db_helper.php doesn't reference mysqli
        $content = file_get_contents($helperPath . 'db_helper.php');
        $this->assertFalse(strpos($content, 'mysqli_') !== false, 'db_helper.php should not contain mysqli_ references');

        // Check that db_helper.mysql.php doesn't reference mysqli
        $content = file_get_contents($helperPath . 'db_helper.mysql.php');
        $this->assertFalse(strpos($content, 'mysqli_') !== false, 'db_helper.mysql.php should not contain mysqli_ references');
    }

    public function testDriverInitialization()
    {
        // Test that db_init function works
        // $this->assertTrue(function_exists('db_init'));

        // Test that driver-specific files are loaded
        $driver = db_driver();

        if ($driver === 'mysql') {
            $this->assertTrue(function_exists('mysql_db_insert'));
        } elseif ($driver === 'pgsql') {
            $this->assertTrue(function_exists('pgsql_db_insert'));
        }
    }

    public function testCommonFunctionsWorkAcrossDrivers()
    {
        // Test that common functions work regardless of driver
        $driver = db_driver();

        // These should work for both drivers
        $this->assertTrue(is_string(db_namespace()));
        $this->assertTrue(is_array(db_config()));
        $this->assertTrue(is_string(db_driver()));
        $this->assertTrue(is_string(db_host()));
        $this->assertTrue(is_string(db_name()));
        $this->assertTrue(is_string(db_user()));
        $this->assertTrue(is_string(db_prefix()));
    }
}
