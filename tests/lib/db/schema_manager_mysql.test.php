<?php

use LucidFrame\Core\db\SchemaManager;

/**
 * Test file for MySQL data type mappings in SchemaManager
 * (MySQL mirror of the PostgreSQL manager suite.)
 */
class SchemaManagerMysqlTestCase extends \LucidFrame\Test\LucidFrameDatabaseTestCase
{
    /** @var SchemaManager */
    private $schemaManager;

    public function setUp()
    {
        parent::setUp();

        $this->schemaManager = new SchemaManager();
        $this->schemaManager->setDriver('mysql');
    }

    public function testForMySQLDataTypeMappings()
    {
        $testCases = array(
            'int' => 'INT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'smallint' => 'SMALLINT',
            'tinyint' => 'TINYINT',
            'string' => 'VARCHAR',
            'char' => 'CHAR',
            'text' => 'TEXT',
            'longtext' => 'LONGTEXT',
            'boolean' => 'TINYINT',
            'decimal' => 'NUMERIC',
            'float' => 'DOUBLE',
            'array' => 'TEXT',
            'json' => 'TEXT',
            'binary' => 'VARBINARY',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'time' => 'TIME',
        );

        foreach ($testCases as $frameworkType => $expectedMySQLType) {
            $definition = array('type' => $frameworkType);
            $actualType = $this->schemaManager->getVendorFieldType($definition);
            $this->assertEqual($expectedMySQLType, $actualType,
                "Failed mapping for type '{$frameworkType}': expected '{$expectedMySQLType}', got '{$actualType}'");
        }
    }

    public function testForMySQLAutoIncrement()
    {
        $definition = array('type' => 'int', 'autoinc' => true);
        $statement = $this->schemaManager->getFieldStatement('id', $definition);

        $this->assertStringContainsString('AUTO_INCREMENT', $statement, 'MySQL int with autoinc should use AUTO_INCREMENT');
        $this->assertStringContainsString('`id`', $statement, 'MySQL should use backticks for identifiers');
        $this->assertStringNotContainsString('IDENTITY', $statement, 'MySQL should not use IDENTITY');
    }

    public function testForMySQLPrimaryKeyUsesTableLevelConstraint()
    {
        $schema = array(
            'article' => array(
                'title' => array('type' => 'string', 'length' => 120, 'null' => false),
                'options' => array('timestamps' => false),
            ),
        );

        $pkFields = $this->schemaManager->populatePrimaryKeys($schema);
        $constraints = array();
        $sql = $this->schemaManager->createTableStatement('article', $schema, $pkFields, $constraints);

        $this->assertStringContainsString('`id` INT(11) unsigned NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql, 'MySQL should use a table-level PK constraint');
        $this->assertStringNotContainsString('GENERATED', $sql, 'MySQL should not use inline identity');
    }

    public function testForMySQLBooleanHandling()
    {
        $definition = array('type' => 'boolean', 'default' => true);
        $statement = $this->schemaManager->getFieldStatement('active', $definition);

        $this->assertStringContainsString('TINYINT(1)', $statement, 'Boolean should use TINYINT(1)');
        $this->assertStringContainsString('DEFAULT 1', $statement, 'Boolean true default should be 1');
        $this->assertStringContainsString('unsigned', $statement, 'MySQL boolean should be unsigned');

        $definition = array('type' => 'boolean', 'default' => false);
        $statement = $this->schemaManager->getFieldStatement('active', $definition);
        $this->assertStringContainsString('DEFAULT 0', $statement, 'Boolean false default should be 0');
    }

    public function testForMySQLIdentifierQuoting()
    {
        $definition = array('type' => 'string');
        $statement = $this->schemaManager->getFieldStatement('test_field', $definition);

        $this->assertStringContainsString('`test_field`', $statement, 'MySQL should use backticks');
        $this->assertStringNotContainsString('"test_field"', $statement, 'MySQL should not use double quotes');
    }

    public function testForMySQLTextTypes()
    {
        // Map to the vendor text types
        $definition = array('type' => 'text', 'length' => 'tiny');
        $this->assertEqual('TINYTEXT', $this->schemaManager->getVendorFieldType($definition));

        $definition = array('type' => 'text', 'length' => 'medium');
        $this->assertEqual('MEDIUMTEXT', $this->schemaManager->getVendorFieldType($definition));

        $definition = array('type' => 'text', 'length' => 'long');
        $this->assertEqual('LONGTEXT', $this->schemaManager->getVendorFieldType($definition));

        $definition = array('type' => 'text');
        $this->assertEqual('TEXT', $this->schemaManager->getVendorFieldType($definition));
    }

    public function testForMySQLCollateInFieldStatement()
    {
        $definition = array('type' => 'string');
        $statement = $this->schemaManager->getFieldStatement('name', $definition);

        $this->assertStringContainsString('COLLATE', $statement, 'MySQL string fields should contain COLLATE');
    }

    public function testForMySQLUnsignedSupport()
    {
        $definition = array('type' => 'int', 'unsigned' => true);
        $statement = $this->schemaManager->getFieldStatement('count', $definition);

        $this->assertStringContainsString('unsigned', $statement, 'MySQL should support unsigned');
    }

    public function testForMySQLJsonMapping()
    {
        $definition = array('type' => 'json');
        $actualType = $this->schemaManager->getVendorFieldType($definition);
        $this->assertEqual('TEXT', $actualType, 'MySQL should map json to TEXT in the schema manager map');
    }
}