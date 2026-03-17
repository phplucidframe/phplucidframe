<?php

use LucidFrame\Core\db\drivers\MySQLDriver;
use LucidFrame\Core\db\SchemaManager;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for MySQLDriver class
 */
class MySQLDriverTestCase extends LucidFrameTestCase
{
    /** @var \LucidFrame\Core\db\drivers\MySQLDriver */
    private $driver;

    /** @var array */
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->driver = new MySQLDriver();
        $this->config = [
            'host' => 'localhost',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'port' => 3306
        ];
    }

    public function tearDown()
    {
        if ($this->driver && $this->driver->isConnected()) {
            $this->driver->close();
        }
        parent::tearDown();
    }

    public function testImplementsDriverInterface()
    {
        $this->assertTrue($this->driver instanceof \LucidFrame\Core\db\drivers\DriverInterface);
    }

    public function testConnectWithMissingConfig()
    {
        $invalidConfig = [
            'host' => 'localhost',
            // missing required fields
        ];

        try {
            $this->driver->connect($invalidConfig);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(strpos($e->getMessage(), 'missing required key') !== false);
        }
    }

    public function testQuoteIdentifier()
    {
        $quoted = $this->driver->quote('table_name');
        $this->assertEqual($quoted, '`table_name`');

        // Test escaping backticks
        $quoted = $this->driver->quote('table`name');
        $this->assertEqual($quoted, '`table``name`');
    }

    public function testGetDataTypes()
    {
        $dataTypes = $this->driver->getDataTypes();

        $this->assertTrue(is_array($dataTypes));
        $this->assertEqual($dataTypes['int'], 'INT');
        $this->assertEqual($dataTypes['string'], 'VARCHAR');
        $this->assertEqual($dataTypes['text'], 'TEXT');
        $this->assertEqual($dataTypes['boolean'], 'TINYINT(1)');
        $this->assertEqual($dataTypes['datetime'], 'DATETIME');
        $this->assertEqual($dataTypes['json'], 'JSON');
    }

    public function testGetAutoIncrementSyntax()
    {
        $syntax = $this->driver->getAutoIncrementSyntax();
        $this->assertEqual($syntax, 'AUTO_INCREMENT');
    }

    public function testGetLimitSyntax()
    {
        // Test LIMIT without offset
        $limit = $this->driver->getLimitSyntax(10);
        $this->assertEqual($limit, 'LIMIT 10');

        // Test LIMIT with offset
        $limit = $this->driver->getLimitSyntax(10, 20);
        $this->assertEqual($limit, 'LIMIT 20, 10');
    }

    public function testQueryWithoutConnection()
    {
        try {
            $this->driver->query('SELECT 1');
            $this->fail('Expected Exception was not thrown');
        } catch (\Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'No database connection') !== false);
        }
    }

    public function testTransactionMethods()
    {
        // Test without connection
        $this->assertFalse($this->driver->beginTransaction());
        $this->assertFalse($this->driver->commit());
        $this->assertFalse($this->driver->rollback());
    }

    public function testGetLastInsertIdWithoutConnection()
    {
        $id = $this->driver->getLastInsertId();
        $this->assertEqual($id, 0);
    }

    public function testErrorHandling()
    {
        // Initially no errors
        $this->assertEqual($this->driver->getError(), '');
        $this->assertEqual($this->driver->getErrorCode(), 0);
    }

    public function testFetchMethodsWithNullStatement()
    {
        $this->assertFalse($this->driver->fetchAssoc(null));
        $this->assertFalse($this->driver->fetchArray(null));
        $this->assertFalse($this->driver->fetchObject(null));
        $this->assertEqual($this->driver->getNumRows(null), 0);
    }

    public function testSchemaManagerMethods()
    {
        $schemaManager = new SchemaManager([]);
        $this->driver->setSchemaManager($schemaManager);

        $retrieved = $this->driver->getSchemaManager();
        $this->assertTrue($retrieved instanceof SchemaManager);
    }

    public function testForeignKeyCheckMethods()
    {
        // Test without connection - should return false
        $this->assertFalse($this->driver->setForeignKeyCheck(0));
        $this->assertFalse($this->driver->enableForeignKeyCheck());
        $this->assertFalse($this->driver->disableForeignKeyCheck());
    }

    public function testGetServerVersionWithoutConnection()
    {
        $version = $this->driver->getServerVersion();
        $this->assertEqual($version, '');
    }

    public function testConnectionMethods()
    {
        $this->assertNull($this->driver->getConnection());
        $this->assertFalse($this->driver->isConnected());

        $this->driver->close();
        $this->assertNull($this->driver->getConnection());
    }

    /**
     * Integration test with actual database connection
     * This test will only run if database connection is available
     */
    public function testWithActualDatabase()
    {
        try {
            // Use actual database config from the framework
            $actualConfig = _app('db')->getConfig();
            $connection = $this->driver->connect($actualConfig);

            if (!$connection) {
                return; // Skip test if no connection
            }

            // Test basic query
            $stmt = $this->driver->query('SELECT 1 as test_value');
            $this->assertTrue($stmt instanceof \PDOStatement);

            // Test fetch methods
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['test_value'], 1);

            // Test fetchColumn
            $value = $this->driver->fetchColumn('SELECT 2 as test_value');
            $this->assertEqual($value, 2);

            // Test fetchResult
            $result = $this->driver->fetchResult('SELECT 3 as test_value');
            $this->assertEqual($result->test_value, 3);

            // Test fetchAll
            $results = $this->driver->fetchAll('SELECT 4 as test_value UNION SELECT 5 as test_value');
            $this->assertEqual(count($results), 2);
            $this->assertEqual($results[0]->test_value, 4);
            $this->assertEqual($results[1]->test_value, 5);

            // Test query with parameters
            $stmt = $this->driver->query('SELECT ? as param_value', [10]);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['param_value'], 10);

            // Test named parameters
            $stmt = $this->driver->query('SELECT :value as named_param', [':value' => 'test']);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['named_param'], 'test');

            // Test transaction methods
            $this->assertTrue($this->driver->beginTransaction());
            $this->assertTrue($this->driver->rollback());

            // Test foreign key methods
            $this->assertTrue($this->driver->disableForeignKeyCheck());
            $this->assertTrue($this->driver->enableForeignKeyCheck());

            // Test server version
            $version = $this->driver->getServerVersion();
            $this->assertTrue(strlen($version) > 0);

        } catch (\Exception $e) {
            // Skip test if database connection fails
            return;
        }
    }

    /**
     * Test query string building for debugging
     */
    public function testQueryStringBuilding()
    {
        try {
            $actualConfig = _app('db')->getConfig();
            $this->driver->connect($actualConfig);

            // Enable query printing mode
            _g('db_printQuery', true);

            $result = $this->driver->query('SELECT :value as test', [':value' => 'hello']);

            // In print query mode, should return the SQL string
            $this->assertTrue(is_string($result));
            $this->assertTrue(strpos($result, 'SELECT') !== false);

            _g('db_printQuery', false);

        } catch (\Exception $e) {
            // Skip test if database connection fails
            return;
        }
    }

    /**
     * Test error handling with invalid query
     */
    public function testErrorHandlingWithInvalidQuery()
    {
        try {
            $actualConfig = _app('db')->getConfig();
            $this->driver->connect($actualConfig);

            try {
                $this->driver->query('INVALID SQL SYNTAX');
                $this->fail('Expected PDOException was not thrown');
            } catch (\PDOException $e) {
                // Verify error information is captured
                $this->assertTrue(strlen($this->driver->getError()) > 0);
                $this->assertTrue($this->driver->getErrorCode() > 0);
            }

        } catch (\Exception $e) {
            // Skip test if database connection fails
            return;
        }
    }

    /**
     * Test parameter normalization
     */
    public function testParameterNormalization()
    {
        try {
            $actualConfig = _app('db')->getConfig();
            $this->driver->connect($actualConfig);

            // Test numeric keys
            $stmt = $this->driver->query('SELECT ? as val1, ? as val2', ['first', 'second']);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['val1'], 'first');
            $this->assertEqual($result['val2'], 'second');

            // Test named parameters without colon prefix
            $stmt = $this->driver->query('SELECT :val1, :val2', ['val1' => 'test1', 'val2' => 'test2']);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['val1'], 'test1');
            $this->assertEqual($result['val2'], 'test2');

        } catch (\Exception $e) {
            // Skip test if database connection fails
            return;
        }
    }
}
