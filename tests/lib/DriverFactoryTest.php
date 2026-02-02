<?php

/**
 * Test file for DriverFactory class
 * Basic tests to verify driver infrastructure works correctly
 */

require_once __DIR__ . '/../../lib/lc.php';
require_once __DIR__ . '/../../lib/classes/DatabaseException.php';
require_once __DIR__ . '/../../lib/classes/DriverInterface.php';
require_once __DIR__ . '/../../lib/classes/DriverFactory.php';
require_once __DIR__ . '/../../lib/classes/MySQLDriver.php';
require_once __DIR__ . '/../../lib/classes/PostgreSQLDriver.php';

use LucidFrame\Core\DatabaseException;
use LucidFrame\Core\drivers\DriverFactory;
use LucidFrame\Core\drivers\MySQLDriver;
use LucidFrame\Core\drivers\PostgreSQLDriver;

/**
 * Test DriverFactory functionality
 */
function testDriverFactory()
{
    echo "Testing DriverFactory...\n";

    // Test supported drivers
    $supportedDrivers = DriverFactory::getSupportedDrivers();
    assert(in_array('mysql', $supportedDrivers), 'MySQL should be supported');
    assert(in_array('pgsql', $supportedDrivers), 'PostgreSQL should be supported');
    echo "✓ Supported drivers test passed\n";

    // Test driver support check
    assert(DriverFactory::isSupported('mysql'), 'MySQL should be supported');
    assert(DriverFactory::isSupported('pgsql'), 'PostgreSQL should be supported');
    assert(!DriverFactory::isSupported('sqlite'), 'SQLite should not be supported');
    echo "✓ Driver support check test passed\n";

    // Test driver class names
    $mysqlClass = DriverFactory::getDriverClass('mysql');
    assert($mysqlClass === 'LucidFrame\\Core\\drivers\\MySQLDriver', 'MySQL driver class should be correct');

    $pgsqlClass = DriverFactory::getDriverClass('pgsql');
    assert($pgsqlClass === 'LucidFrame\\Core\\drivers\\PostgreSQLDriver', 'PostgreSQL driver class should be correct');
    echo "✓ Driver class name test passed\n";

    // Test configuration validation
    try {
        DriverFactory::validateConfig([]);
        assert(false, 'Should throw exception for empty config');
    } catch (DatabaseException $e) {
        assert($e->getStandardCode() === DatabaseException::DB_CONFIG_ERROR, 'Should be config error');
        echo "✓ Empty config validation test passed\n";
    }

    try {
        DriverFactory::validateConfig(['driver' => 'unsupported']);
        assert(false, 'Should throw exception for unsupported driver');
    } catch (DatabaseException $e) {
        echo "✓ Unsupported driver validation test passed\n";
    }

    // Test valid MySQL config validation
    $validMySQLConfig = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'test',
        'username' => 'user'
    ];

    try {
        DriverFactory::validateConfig($validMySQLConfig);
        echo "✓ Valid MySQL config validation test passed\n";
    } catch (DatabaseException $e) {
        assert(false, 'Valid MySQL config should not throw exception: ' . $e->getMessage());
    }

    // Test valid PostgreSQL config validation
    $validPgSQLConfig = [
        'driver' => 'pgsql',
        'host' => 'localhost',
        'database' => 'test',
        'username' => 'user'
    ];

    try {
        DriverFactory::validateConfig($validPgSQLConfig);
        echo "✓ Valid PostgreSQL config validation test passed\n";
    } catch (DatabaseException $e) {
        assert(false, 'Valid PostgreSQL config should not throw exception: ' . $e->getMessage());
    }

    echo "All DriverFactory tests passed!\n\n";
}

/**
 * Test DatabaseException functionality
 */
function testDatabaseException()
{
    echo "Testing DatabaseException...\n";

    // Test basic exception creation
    $exception = new DatabaseException('Test message', 123, null, 456, 'Driver message', DatabaseException::DB_SYNTAX_ERROR);

    assert($exception->getMessage() === 'Test message', 'Message should match');
    assert($exception->getCode() === 123, 'Code should match');
    assert($exception->getDriverCode() === 456, 'Driver code should match');
    assert($exception->getDriverMessage() === 'Driver message', 'Driver message should match');
    assert($exception->getStandardCode() === DatabaseException::DB_SYNTAX_ERROR, 'Standard code should match');
    echo "✓ Basic exception creation test passed\n";

    // Test static factory methods
    $connectionError = DatabaseException::connectionError('Connection failed', 2002, 'MySQL connection error');
    assert($connectionError->getStandardCode() === DatabaseException::DB_CONNECTION_ERROR, 'Should be connection error');
    echo "✓ Connection error factory test passed\n";

    $syntaxError = DatabaseException::syntaxError('Syntax error', 1064, 'MySQL syntax error');
    assert($syntaxError->getStandardCode() === DatabaseException::DB_SYNTAX_ERROR, 'Should be syntax error');
    echo "✓ Syntax error factory test passed\n";

    // Test error code mapping
    $mysqlDuplicateCode = DatabaseException::mapDriverError(1062, 'mysql');
    assert($mysqlDuplicateCode === DatabaseException::DB_DUPLICATE_KEY_ERROR, 'MySQL 1062 should map to duplicate key error');

    $pgsqlDuplicateCode = DatabaseException::mapDriverError('23505', 'pgsql');
    assert($pgsqlDuplicateCode === DatabaseException::DB_DUPLICATE_KEY_ERROR, 'PostgreSQL 23505 should map to duplicate key error');
    echo "✓ Error code mapping test passed\n";

    echo "All DatabaseException tests passed!\n\n";
}

/**
 * Test MySQL driver basic functionality
 */
function testMySQLDriver()
{
    echo "Testing MySQLDriver...\n";

    $driver = new MySQLDriver();

    // Test identifier quoting
    $quoted = $driver->quote('table_name');
    assert($quoted === '`table_name`', 'MySQL should use backticks for quoting');

    $quotedWithBackticks = $driver->quote('table`name');
    assert($quotedWithBackticks === '`table``name`', 'MySQL should escape backticks');
    echo "✓ MySQL identifier quoting test passed\n";

    // Test data types
    $dataTypes = $driver->getDataTypes();
    assert($dataTypes['boolean'] === 'TINYINT(1)', 'MySQL boolean should map to TINYINT(1)');
    assert($dataTypes['string'] === 'VARCHAR', 'MySQL string should map to VARCHAR');
    assert($dataTypes['json'] === 'JSON', 'MySQL json should map to JSON');
    echo "✓ MySQL data types test passed\n";

    echo "All MySQLDriver tests passed!\n\n";
}

/**
 * Test PostgreSQL driver basic functionality
 */
function testPostgreSQLDriver()
{
    echo "Testing PostgreSQLDriver...\n";

    $driver = new PostgreSQLDriver();

    // Test identifier quoting
    $quoted = $driver->quote('table_name');
    assert($quoted === '"table_name"', 'PostgreSQL should use double quotes for quoting');

    $quotedWithQuotes = $driver->quote('table"name');
    assert($quotedWithQuotes === '"table""name"', 'PostgreSQL should escape double quotes');
    echo "✓ PostgreSQL identifier quoting test passed\n";

    // Test data types
    $dataTypes = $driver->getDataTypes();
    assert($dataTypes['boolean'] === 'BOOLEAN', 'PostgreSQL boolean should map to BOOLEAN');
    assert($dataTypes['string'] === 'VARCHAR', 'PostgreSQL string should map to VARCHAR');
    assert($dataTypes['json'] === 'JSONB', 'PostgreSQL json should map to JSONB');
    echo "✓ PostgreSQL data types test passed\n";

    echo "All PostgreSQLDriver tests passed!\n\n";
}

// Run all tests
echo "Running core driver infrastructure tests...\n\n";

try {
    testDriverFactory();
    testDatabaseException();
    testMySQLDriver();
    testPostgreSQLDriver();

    echo "🎉 All tests passed! Core driver infrastructure is working correctly.\n";
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
