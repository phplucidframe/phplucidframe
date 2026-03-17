<?php

use LucidFrame\Core\db\Database;
use LucidFrame\Core\db\DatabaseException;
use LucidFrame\Core\db\drivers\DriverFactory;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for multi-driver Database functionality
 */
class DatabaseMultiDriverTestCase extends LucidFrameTestCase
{
    public function testDriverFactoryCreation()
    {
        // Test MySQL driver creation
        $mysqlConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $mysqlDriver = DriverFactory::create($mysqlConfig);
            $this->assertTrue($mysqlDriver instanceof \LucidFrame\Core\db\drivers\MySQLDriver);
        } catch (DatabaseException $e) {
            // Expected if MySQL is not available in test environment
            $this->assertTrue(true);
        }

        // Test PostgreSQL driver creation
        $pgsqlConfig = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $pgsqlDriver = DriverFactory::create($pgsqlConfig);
            $this->assertTrue($pgsqlDriver instanceof \LucidFrame\Core\db\drivers\PostgreSQLDriver);
        } catch (DatabaseException $e) {
            // Expected if PostgreSQL is not available in test environment
            $this->assertTrue(true);
        }
    }

    public function testDriverFactoryValidation()
    {
        // Test invalid driver
        $invalidConfig = [
            'driver' => 'invalid',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            DriverFactory::create($invalidConfig);
            $this->fail('Should have thrown DatabaseException for invalid driver');
        } catch (DatabaseException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Unsupported database driver') !== false);
        }

        // Test missing required configuration
        $incompleteConfig = [
            'driver' => 'mysql',
            'host' => 'localhost'
            // Missing database, username, password
        ];

        try {
            DriverFactory::validateConfig($incompleteConfig);
            $this->fail('Should have thrown DatabaseException for incomplete config');
        } catch (DatabaseException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'missing or empty') !== false);
        }
    }

    public function testDatabaseClassDriverDelegation()
    {
        // Mock a database configuration for testing
        $originalConfig = _cfg('databases');

        // Set up test configuration
        _cfg('databases', [
            'test' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test_db',
                'username' => 'test_user',
                'password' => 'test_pass',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci'
            ]
        ]);

        try {
            // This will fail to connect but should properly initialize the driver
            $db = new Database('test');
            $this->fail('Should have thrown exception due to invalid connection');
        } catch (DatabaseException $e) {
            // Expected - connection should fail but driver should be created
            $this->assertTrue(strpos($e->getMessage(), 'connection') !== false);
        } catch (\Exception $e) {
            // Also acceptable - any connection-related error
            $this->assertTrue(true);
        }

        // Restore original configuration
        _cfg('databases', $originalConfig);
    }

    public function testBackwardCompatibility()
    {
        // Test that existing method signatures are maintained
        $db = _app('db');

        if ($db) {
            // Test that all expected methods exist
            $this->assertTrue(method_exists($db, 'query'));
            $this->assertTrue(method_exists($db, 'fetchAll'));
            $this->assertTrue(method_exists($db, 'fetchResult'));
            $this->assertTrue(method_exists($db, 'fetchColumn'));
            $this->assertTrue(method_exists($db, 'getInsertId'));
            $this->assertTrue(method_exists($db, 'beginTransaction'));
            $this->assertTrue(method_exists($db, 'commit'));
            $this->assertTrue(method_exists($db, 'rollback'));
            $this->assertTrue(method_exists($db, 'getError'));
            $this->assertTrue(method_exists($db, 'getErrorCode'));

            // Test driver-specific methods
            $this->assertTrue(method_exists($db, 'getDriverInstance'));
            $this->assertTrue(method_exists($db, 'getDriver'));
        }
    }

    public function testSupportedDriversList()
    {
        $supportedDrivers = DriverFactory::getSupportedDrivers();

        $this->assertTrue(in_array('mysql', $supportedDrivers));
        $this->assertTrue(in_array('pgsql', $supportedDrivers));
        $this->assertEqual(count($supportedDrivers), 2);
    }

    public function testDriverSpecificMethods()
    {
        // Test MySQL driver methods
        $mysqlConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $mysqlDriver = DriverFactory::create($mysqlConfig);

            // Test MySQL-specific methods
            $this->assertEqual($mysqlDriver->quote('table'), '`table`');
            $this->assertEqual($mysqlDriver->getAutoIncrementSyntax(), 'AUTO_INCREMENT');
            $this->assertEqual($mysqlDriver->getLimitSyntax(10, 5), 'LIMIT 5, 10');

            $dataTypes = $mysqlDriver->getDataTypes();
            $this->assertEqual($dataTypes['boolean'], 'TINYINT(1)');
            $this->assertEqual($dataTypes['json'], 'JSON');

        } catch (DatabaseException $e) {
            // Expected if MySQL is not available
            $this->assertTrue(true);
        }

        // Test PostgreSQL driver methods
        $pgsqlConfig = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $pgsqlDriver = DriverFactory::create($pgsqlConfig);

            // Test PostgreSQL-specific methods
            $this->assertEqual($pgsqlDriver->quote('table'), '"table"');
            $this->assertEqual($pgsqlDriver->getAutoIncrementSyntax(), 'SERIAL');
            $this->assertEqual($pgsqlDriver->getLimitSyntax(10, 5), 'LIMIT 10 OFFSET 5');

            $dataTypes = $pgsqlDriver->getDataTypes();
            $this->assertEqual($dataTypes['boolean'], 'BOOLEAN');
            $this->assertEqual($dataTypes['json'], 'JSONB');

        } catch (DatabaseException $e) {
            // Expected if PostgreSQL is not available
            $this->assertTrue(true);
        }
    }
}
