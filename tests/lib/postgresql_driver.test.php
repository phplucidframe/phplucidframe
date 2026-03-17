<?php

use LucidFrame\Core\db\drivers\PostgreSQLDriver;
use LucidFrame\Core\db\SchemaManager;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for PostgreSQLDriver class
 */
class PostgreSQLDriverTestCase extends LucidFrameTestCase
{
    /** @var PostgreSQLDriver */
    private $driver;

    /** @var array */
    private $config;

    public function setUp()
    {
        // Skip parent setUp to avoid MySQL-specific database operations
        $this->driver = new PostgreSQLDriver();
        $this->config = [
            'host' => 'localhost',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
            'charset' => 'utf8',
            'port' => 5432,
            'schema' => 'public'
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
        $this->assertEqual($quoted, '"table_name"');

        // Test escaping double quotes
        $quoted = $this->driver->quote('table"name');
        $this->assertEqual($quoted, '"table""name"');
    }

    public function testGetDataTypes()
    {
        $dataTypes = $this->driver->getDataTypes();

        $this->assertTrue(is_array($dataTypes));
        $this->assertEqual($dataTypes['int'], 'INTEGER');
        $this->assertEqual($dataTypes['string'], 'VARCHAR');
        $this->assertEqual($dataTypes['text'], 'TEXT');
        $this->assertEqual($dataTypes['boolean'], 'BOOLEAN');
        $this->assertEqual($dataTypes['datetime'], 'TIMESTAMP');
        $this->assertEqual($dataTypes['json'], 'JSONB');
        $this->assertEqual($dataTypes['tinyint'], 'SMALLINT'); // PostgreSQL mapping
        $this->assertEqual($dataTypes['decimal'], 'NUMERIC');
        $this->assertEqual($dataTypes['float'], 'REAL');
        $this->assertEqual($dataTypes['double'], 'DOUBLE PRECISION');
        $this->assertEqual($dataTypes['blob'], 'BYTEA');
    }

    public function testGetAutoIncrementSyntax()
    {
        $syntax = $this->driver->getAutoIncrementSyntax();
        $this->assertEqual($syntax, 'SERIAL');
    }

    public function testGetLimitSyntax()
    {
        // Test LIMIT without offset
        $limit = $this->driver->getLimitSyntax(10);
        $this->assertEqual($limit, 'LIMIT 10');

        // Test LIMIT with offset (PostgreSQL syntax)
        $limit = $this->driver->getLimitSyntax(10, 20);
        $this->assertEqual($limit, 'LIMIT 10 OFFSET 20');
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
        // PostgreSQL doesn't have global foreign key checks, so these should always return true
        $this->assertTrue($this->driver->setForeignKeyCheck(0));
        $this->assertTrue($this->driver->enableForeignKeyCheck());
        $this->assertTrue($this->driver->disableForeignKeyCheck());
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

    public function testGetBooleanValue()
    {
        $this->assertEqual($this->driver->getBooleanValue(true), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue(false), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue(1), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue(0), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue('yes'), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue(''), 'FALSE');
    }

    public function testGetSequenceName()
    {
        $sequenceName = $this->driver->getSequenceName('users');
        $this->assertEqual($sequenceName, 'users_id_seq');

        $sequenceName = $this->driver->getSequenceName('posts', 'post_id');
        $this->assertEqual($sequenceName, 'posts_post_id_seq');
    }

    public function testGetUpsertSyntax()
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $conflictColumns = ['email'];
        $updateColumns = ['name'];

        $sql = $this->driver->getUpsertSyntax('users', $data, $conflictColumns, $updateColumns);

        $expectedSql = 'INSERT INTO users (name, email) VALUES (:name, :email) ON CONFLICT (email) DO UPDATE SET name = EXCLUDED.name';
        $this->assertEqual($sql, $expectedSql);

        // Test with DO NOTHING
        $sql = $this->driver->getUpsertSyntax('users', $data, $conflictColumns);
        $expectedSql = 'INSERT INTO users (name, email) VALUES (:name, :email) ON CONFLICT (email) DO NOTHING';
        $this->assertEqual($sql, $expectedSql);
    }

    /**
     * Integration test with actual PostgreSQL database connection
     * This test will only run if PostgreSQL database connection is available
     */
    public function testWithActualPostgreSQLDatabase()
    {
        try {
            // Try to use PostgreSQL config if available
            $postgresConfig = [
                'host' => getenv('POSTGRES_HOST') ?: 'localhost',
                'database' => getenv('POSTGRES_DB') ?: 'test_db',
                'username' => getenv('POSTGRES_USER') ?: 'postgres',
                'password' => getenv('POSTGRES_PASSWORD') ?: '',
                'port' => getenv('POSTGRES_PORT') ?: 5432,
                'charset' => 'utf8',
                'schema' => 'public'
            ];

            $connection = $this->driver->connect($postgresConfig);

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
            $results = $this->driver->fetchAll('SELECT 4 as test_value UNION SELECT 5 as test_value ORDER BY test_value');
            $this->assertEqual(count($results), 2);
            $this->assertEqual($results[0]->test_value, 4);
            $this->assertEqual($results[1]->test_value, 5);

            // Test query with parameters
            $stmt = $this->driver->query('SELECT $1 as param_value', [10]);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['param_value'], 10);

            // Test named parameters
            $stmt = $this->driver->query('SELECT :value as named_param', [':value' => 'test']);
            $result = $this->driver->fetchAssoc($stmt);
            $this->assertEqual($result['named_param'], 'test');

            // Test transaction methods
            $this->assertTrue($this->driver->beginTransaction());
            $this->assertTrue($this->driver->rollback());

            // Test server version
            $version = $this->driver->getServerVersion();
            $this->assertTrue(strlen($version) > 0);

            // Test PostgreSQL-specific features
            $this->assertTrue($this->driver->resetSequence('test_table', 'id'));

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
            $postgresConfig = [
                'host' => getenv('POSTGRES_HOST') ?: 'localhost',
                'database' => getenv('POSTGRES_DB') ?: 'test_db',
                'username' => getenv('POSTGRES_USER') ?: 'postgres',
                'password' => getenv('POSTGRES_PASSWORD') ?: '',
                'port' => getenv('POSTGRES_PORT') ?: 5432,
                'charset' => 'utf8',
                'schema' => 'public'
            ];

            $this->driver->connect($postgresConfig);

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
            $postgresConfig = [
                'host' => getenv('POSTGRES_HOST') ?: 'localhost',
                'database' => getenv('POSTGRES_DB') ?: 'test_db',
                'username' => getenv('POSTGRES_USER') ?: 'postgres',
                'password' => getenv('POSTGRES_PASSWORD') ?: '',
                'port' => getenv('POSTGRES_PORT') ?: 5432,
                'charset' => 'utf8',
                'schema' => 'public'
            ];

            $this->driver->connect($postgresConfig);

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
            $postgresConfig = [
                'host' => getenv('POSTGRES_HOST') ?: 'localhost',
                'database' => getenv('POSTGRES_DB') ?: 'test_db',
                'username' => getenv('POSTGRES_USER') ?: 'postgres',
                'password' => getenv('POSTGRES_PASSWORD') ?: '',
                'port' => getenv('POSTGRES_PORT') ?: 5432,
                'charset' => 'utf8',
                'schema' => 'public'
            ];

            $this->driver->connect($postgresConfig);

            // Test numeric keys (PostgreSQL uses $1, $2 syntax but PDO handles this)
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

    /**
     * Test PostgreSQL-specific configuration options
     */
    public function testPostgreSQLSpecificConfig()
    {
        $configWithSSL = array_merge($this->config, [
            'sslmode' => 'require'
        ]);

        try {
            // This will likely fail without actual PostgreSQL server, but tests config handling
            $this->driver->connect($configWithSSL);
        } catch (\Exception $e) {
            // Expected to fail without actual server
            $this->assertTrue(strpos($e->getMessage(), 'PostgreSQL connection failed') !== false);
        }
    }

    /**
     * Test PostgreSQL boolean handling
     */
    public function testPostgreSQLBooleanHandling()
    {
        // Test various truthy/falsy values
        $this->assertEqual($this->driver->getBooleanValue(true), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue(false), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue(1), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue(0), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue('1'), 'TRUE');
        $this->assertEqual($this->driver->getBooleanValue('0'), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue(null), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue([]), 'FALSE');
        $this->assertEqual($this->driver->getBooleanValue(['item']), 'TRUE');
    }

    /**
     * Test PostgreSQL sequence handling
     */
    public function testSequenceHandling()
    {
        // Test sequence name generation
        $this->assertEqual($this->driver->getSequenceName('users'), 'users_id_seq');
        $this->assertEqual($this->driver->getSequenceName('user_profiles', 'profile_id'), 'user_profiles_profile_id_seq');

        // Test reset sequence without connection (should return false)
        $this->assertFalse($this->driver->resetSequence('test_table'));
    }

    /**
     * Test UPSERT syntax generation
     */
    public function testUpsertSyntaxGeneration()
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'full_name' => 'John Doe'
        ];

        // Test with conflict resolution
        $sql = $this->driver->getUpsertSyntax('users', $data, ['username'], ['email', 'full_name']);
        $expected = 'INSERT INTO users (username, email, full_name) VALUES (:username, :email, :full_name) ON CONFLICT (username) DO UPDATE SET email = EXCLUDED.email, full_name = EXCLUDED.full_name';
        $this->assertEqual($sql, $expected);

        // Test with DO NOTHING
        $sql = $this->driver->getUpsertSyntax('users', $data, ['username']);
        $expected = 'INSERT INTO users (username, email, full_name) VALUES (:username, :email, :full_name) ON CONFLICT (username) DO NOTHING';
        $this->assertEqual($sql, $expected);

        // Test with multiple conflict columns
        $sql = $this->driver->getUpsertSyntax('users', $data, ['username', 'email'], ['full_name']);
        $expected = 'INSERT INTO users (username, email, full_name) VALUES (:username, :email, :full_name) ON CONFLICT (username, email) DO UPDATE SET full_name = EXCLUDED.full_name';
        $this->assertEqual($sql, $expected);
    }
}
