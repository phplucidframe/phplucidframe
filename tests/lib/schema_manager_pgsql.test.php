<?php
/**
 * Test file for PostgreSQL data type mappings in SchemaManager
 */

require_once 'test_bootstrap.php';

use LucidFrame\Core\SchemaManager;

class SchemaManagerPgsqlTestCase extends LucidFrameTestCase
{
    private $schemaManager;

    public function setUp()
    {
        parent::setUp();
        $this->schemaManager = new SchemaManager();
        $this->schemaManager->setDriver('pgsql');
    }

    public function testPostgreSQLDataTypeMappings()
    {
        $testCases = array(
            'int' => 'INTEGER',
            'integer' => 'INTEGER',
            'bigint' => 'BIGINT',
            'smallint' => 'SMALLINT',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'boolean' => 'BOOLEAN',
            'decimal' => 'NUMERIC',
            'float' => 'DOUBLE PRECISION',
            'json' => 'JSONB',
            'date' => 'DATE',
            'datetime' => 'TIMESTAMP',
            'time' => 'TIME',
            'binary' => 'BYTEA',
            'blob' => 'BYTEA'
        );

        foreach ($testCases as $frameworkType => $expectedPgsqlType) {
            $definition = array('type' => $frameworkType);
            $actualType = $this->schemaManager->getVendorFieldType($definition);
            $this->assertEqual($expectedPgsqlType, $actualType,
                "Failed mapping for type '{$frameworkType}': expected '{$expectedPgsqlType}', got '{$actualType}'");
        }
    }

    public function testPostgreSQLSerialTypes()
    {
        // Test SERIAL for int with autoinc
        $definition = array('type' => 'int', 'autoinc' => true);
        $statement = $this->schemaManager->getFieldStatement('id', $definition);
        $this->assertContains('SERIAL', $statement, 'INT with autoinc should use SERIAL');
        $this->assertContains('"id"', $statement, 'PostgreSQL should use double quotes for identifiers');

        // Test BIGSERIAL for bigint with autoinc
        $definition = array('type' => 'bigint', 'autoinc' => true);
        $statement = $this->schemaManager->getFieldStatement('id', $definition);
        $this->assertContains('BIGSERIAL', $statement, 'BIGINT with autoinc should use BIGSERIAL');

        // Test SMALLSERIAL for smallint with autoinc
        $definition = array('type' => 'smallint', 'autoinc' => true);
        $statement = $this->schemaManager->getFieldStatement('id', $definition);
        $this->assertContains('SMALLSERIAL', $statement, 'SMALLINT with autoinc should use SMALLSERIAL');
    }

    public function testPostgreSQLBooleanHandling()
    {
        // Test boolean with default true
        $definition = array('type' => 'boolean', 'default' => true);
        $statement = $this->schemaManager->getFieldStatement('active', $definition);
        $this->assertContains('BOOLEAN', $statement, 'Boolean should use BOOLEAN type');
        $this->assertContains('DEFAULT TRUE', $statement, 'Boolean true default should be TRUE');
        $this->assertNotContains('unsigned', $statement, 'PostgreSQL boolean should not have unsigned');

        // Test boolean with default false
        $definition = array('type' => 'boolean', 'default' => false);
        $statement = $this->schemaManager->getFieldStatement('active', $definition);
        $this->assertContains('DEFAULT FALSE', $statement, 'Boolean false default should be FALSE');
    }

    public function testPostgreSQLIdentifierQuoting()
    {
        $definition = array('type' => 'string');
        $statement = $this->schemaManager->getFieldStatement('test_field', $definition);
        $this->assertContains('"test_field"', $statement, 'PostgreSQL should use double quotes for identifiers');
        $this->assertNotContains('`test_field`', $statement, 'PostgreSQL should not use backticks');
    }

    public function testPostgreSQLTextTypes()
    {
        // Test that all text length variations map to TEXT in PostgreSQL
        $textTypes = array('tinytext', 'text', 'mediumtext', 'longtext');

        foreach ($textTypes as $textType) {
            $definition = array('type' => $textType);
            $actualType = $this->schemaManager->getVendorFieldType($definition);
            $this->assertEqual('TEXT', $actualType,
                "PostgreSQL should map '{$textType}' to 'TEXT'");
        }

        // Test text with length specification
        $definition = array('type' => 'text', 'length' => 'long');
        $actualType = $this->schemaManager->getVendorFieldType($definition);
        $this->assertEqual('TEXT', $actualType,
            "PostgreSQL should map 'text' with length to 'TEXT'");
    }

    public function testPostgreSQLNoCollateInFieldStatement()
    {
        $definition = array('type' => 'string');
        $statement = $this->schemaManager->getFieldStatement('name', $definition);
        $this->assertNotContains('COLLATE', $statement, 'PostgreSQL field statement should not contain COLLATE');
    }

    public function testPostgreSQLNoUnsignedSupport()
    {
        $definition = array('type' => 'int', 'unsigned' => true);
        $statement = $this->schemaManager->getFieldStatement('count', $definition);
        $this->assertNotContains('unsigned', $statement, 'PostgreSQL should not support unsigned');
    }

    public function testPostgreSQLJSONBMapping()
    {
        $definition = array('type' => 'json');
        $actualType = $this->schemaManager->getVendorFieldType($definition);
        $this->assertEqual('JSONB', $actualType, 'PostgreSQL should map json to JSONB');
    }
}