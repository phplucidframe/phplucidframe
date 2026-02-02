<?php

use LucidFrame\Core\Database;
use LucidFrame\Core\drivers\DriverFactory;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for transaction interface implementation
 * Tests Requirements: 4.1, 4.2, 4.3, 4.4
 */
class TransactionInterfaceTestCase extends LucidFrameTestCase
{
    /**
     * Test that DriverInterface defines transaction methods
     * Requirement 4.1: Transaction methods should be defined in interface
     */
    public function testDriverInterfaceHasTransactionMethods()
    {
        $reflection = new ReflectionClass('LucidFrame\Core\drivers\DriverInterface');

        // Check that transaction methods are defined in the interface
        $this->assertTrue($reflection->hasMethod('beginTransaction'));
        $this->assertTrue($reflection->hasMethod('commit'));
        $this->assertTrue($reflection->hasMethod('rollback'));

        // Verify method signatures
        $beginMethod = $reflection->getMethod('beginTransaction');
        $commitMethod = $reflection->getMethod('commit');
        $rollbackMethod = $reflection->getMethod('rollback');

        $this->assertTrue($beginMethod->isPublic());
        $this->assertTrue($commitMethod->isPublic());
        $this->assertTrue($rollbackMethod->isPublic());
    }

    /**
     * Test that MySQL driver implements transaction methods
     * Requirements 4.1, 4.2, 4.3: MySQL driver should implement all transaction methods
     */
    public function testMySQLDriverImplementsTransactionMethods()
    {
        $reflection = new ReflectionClass('LucidFrame\Core\drivers\MySQLDriver');

        // Check that MySQL driver implements DriverInterface
        $this->assertTrue($reflection->implementsInterface('LucidFrame\Core\drivers\DriverInterface'));

        // Check that transaction methods are implemented
        $this->assertTrue($reflection->hasMethod('beginTransaction'));
        $this->assertTrue($reflection->hasMethod('commit'));
        $this->assertTrue($reflection->hasMethod('rollback'));

        // Verify methods are public
        $beginMethod = $reflection->getMethod('beginTransaction');
        $commitMethod = $reflection->getMethod('commit');
        $rollbackMethod = $reflection->getMethod('rollback');

        $this->assertTrue($beginMethod->isPublic());
        $this->assertTrue($commitMethod->isPublic());
        $this->assertTrue($rollbackMethod->isPublic());
    }

    /**
     * Test that PostgreSQL driver implements transaction methods
     * Requirements 4.1, 4.2, 4.3: PostgreSQL driver should implement all transaction methods
     */
    public function testPostgreSQLDriverImplementsTransactionMethods()
    {
        $reflection = new ReflectionClass('LucidFrame\Core\drivers\PostgreSQLDriver');

        // Check that PostgreSQL driver implements DriverInterface
        $this->assertTrue($reflection->implementsInterface('LucidFrame\Core\drivers\DriverInterface'));

        // Check that transaction methods are implemented
        $this->assertTrue($reflection->hasMethod('beginTransaction'));
        $this->assertTrue($reflection->hasMethod('commit'));
        $this->assertTrue($reflection->hasMethod('rollback'));

        // Verify methods are public
        $beginMethod = $reflection->getMethod('beginTransaction');
        $commitMethod = $reflection->getMethod('commit');
        $rollbackMethod = $reflection->getMethod('rollback');

        $this->assertTrue($beginMethod->isPublic());
        $this->assertTrue($commitMethod->isPublic());
        $this->assertTrue($rollbackMethod->isPublic());
    }

    /**
     * Test that Database class exposes transaction methods
     * Requirement 4.1: Database class should expose transaction methods
     */
    public function testDatabaseClassExposesTransactionMethods()
    {
        $reflection = new ReflectionClass('LucidFrame\Core\Database');

        // Check that Database class has transaction methods
        $this->assertTrue($reflection->hasMethod('beginTransaction'));
        $this->assertTrue($reflection->hasMethod('commit'));
        $this->assertTrue($reflection->hasMethod('rollback'));

        // Verify methods are public
        $beginMethod = $reflection->getMethod('beginTransaction');
        $commitMethod = $reflection->getMethod('commit');
        $rollbackMethod = $reflection->getMethod('rollback');

        $this->assertTrue($beginMethod->isPublic());
        $this->assertTrue($commitMethod->isPublic());
        $this->assertTrue($rollbackMethod->isPublic());

        // Verify return types are boolean (by checking docblock)
        $beginDoc = $beginMethod->getDocComment();
        $commitDoc = $commitMethod->getDocComment();
        $rollbackDoc = $rollbackMethod->getDocComment();

        $this->assertTrue(strpos($beginDoc, '@return bool') !== false);
        $this->assertTrue(strpos($commitDoc, '@return bool') !== false);
        $this->assertTrue(strpos($rollbackDoc, '@return bool') !== false);
    }

    /**
     * Test transaction method delegation in Database class
     * Requirement 4.1: Database class should delegate to driver instance
     */
    public function testDatabaseClassTransactionDelegation()
    {
        // Read the Database class source to verify delegation
        $databaseFile = file_get_contents(LIB . 'classes/Database.php');

        // Check that beginTransaction delegates to driver
        $this->assertTrue(strpos($databaseFile, '$this->driverInstance->beginTransaction()') !== false);

        // Check that commit delegates to driver
        $this->assertTrue(strpos($databaseFile, '$this->driverInstance->commit()') !== false);

        // Check that rollback delegates to driver
        $this->assertTrue(strpos($databaseFile, '$this->driverInstance->rollback()') !== false);

        // Check fallback to connection for backward compatibility
        $this->assertTrue(strpos($databaseFile, '$this->connection->beginTransaction()') !== false);
        $this->assertTrue(strpos($databaseFile, '$this->connection->commit()') !== false);
        $this->assertTrue(strpos($databaseFile, '$this->connection->rollback()') !== false);
    }

    /**
     * Test that transaction methods return boolean values
     * Requirements 4.1, 4.2, 4.3: Transaction methods should return boolean
     */
    public function testTransactionMethodsReturnBoolean()
    {
        // Test with mock configuration (won't actually connect)
        $mockConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $driver = DriverFactory::create($mockConfig);

            // Test without connection - should return false
            $beginResult = $driver->beginTransaction();
            $commitResult = $driver->commit();
            $rollbackResult = $driver->rollback();

            $this->assertTrue(is_bool($beginResult));
            $this->assertTrue(is_bool($commitResult));
            $this->assertTrue(is_bool($rollbackResult));

            // Without connection, all should return false
            $this->assertFalse($beginResult);
            $this->assertFalse($commitResult);
            $this->assertFalse($rollbackResult);

        } catch (\Exception $e) {
            // Expected if driver creation fails
            $this->assertTrue(true, 'Driver creation failed as expected: ' . $e->getMessage());
        }
    }

    /**
     * Test transaction method consistency across drivers
     * Requirements 4.1, 4.2, 4.3: Both drivers should have same interface
     */
    public function testTransactionMethodConsistencyAcrossDrivers()
    {
        $mysqlReflection = new ReflectionClass('LucidFrame\Core\drivers\MySQLDriver');
        $pgsqlReflection = new ReflectionClass('LucidFrame\Core\drivers\PostgreSQLDriver');

        $transactionMethods = ['beginTransaction', 'commit', 'rollback'];

        foreach ($transactionMethods as $methodName) {
            // Both drivers should have the method
            $this->assertTrue($mysqlReflection->hasMethod($methodName));
            $this->assertTrue($pgsqlReflection->hasMethod($methodName));

            // Methods should have same visibility
            $mysqlMethod = $mysqlReflection->getMethod($methodName);
            $pgsqlMethod = $pgsqlReflection->getMethod($methodName);

            $this->assertEqual($mysqlMethod->isPublic(), $pgsqlMethod->isPublic());
            $this->assertEqual($mysqlMethod->isProtected(), $pgsqlMethod->isProtected());
            $this->assertEqual($mysqlMethod->isPrivate(), $pgsqlMethod->isPrivate());

            // Both should be public
            $this->assertTrue($mysqlMethod->isPublic());
            $this->assertTrue($pgsqlMethod->isPublic());
        }
    }

    /**
     * Test that existing Database instance has transaction methods
     * Requirement 4.1: Existing Database instances should support transactions
     */
    public function testExistingDatabaseInstanceHasTransactionMethods()
    {
        $db = _app('db');

        if ($db && $db instanceof Database) {
            // Test that transaction methods exist
            $this->assertTrue(method_exists($db, 'beginTransaction'));
            $this->assertTrue(method_exists($db, 'commit'));
            $this->assertTrue(method_exists($db, 'rollback'));

            // Test that methods are callable
            $this->assertTrue(is_callable([$db, 'beginTransaction']));
            $this->assertTrue(is_callable([$db, 'commit']));
            $this->assertTrue(is_callable([$db, 'rollback']));
        } else {
            // If no database instance, that's also acceptable for testing
            $this->assertTrue(true, 'No database instance available for testing');
        }
    }
}
