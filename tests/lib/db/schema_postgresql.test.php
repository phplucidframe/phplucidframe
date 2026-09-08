<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\SchemaPostgreSQL;

/**
 * Unit Test for SchemaPostgreSQL class (lib/classes/db/SchemaPostgreSQL.php)
 * Covers the PostgreSQL schema-driver implementation: defaults, identifier
 * quoting, schema-qualified names, vendor field types/lengths, inline IDENTITY
 * primary keys, serial types, defaults, FK check statements, index
 * definitions, ALTER statements and column positions.
 * (getDropConstraintStatement requires a live database; it is covered by the
 * schema_manager_mysql round-trip test where the pgsql extension is present.)
 */
class SchemaPostgreSqlTest extends LucidFrameTestCase
{
    /** @var SchemaPostgreSQL */
    private $schema;

    public function setUp()
    {
        parent::setUp();

        $this->schema = new SchemaPostgreSQL();
    }

    public function testForImplementsSchemaInterface()
    {
        $this->assertIsA($this->schema, 'LucidFrame\Core\db\SchemaInterface');
    }

    public function testForGetDefaultOptions()
    {
        $options = $this->schema->getDefaultOptions();

        $this->assertEqual($options['timestamps'], true);
        $this->assertEqual($options['constraints'], true);
        $this->assertEqual($options['schema'], 'public');
    }

    public function testForQuoteIdentifier()
    {
        $this->assertEqual($this->schema->quoteIdentifier('post'), '"post"');
        // Inner double quotes are escaped
        $this->assertEqual($this->schema->quoteIdentifier('a"b'), '"a""b"');
    }

    public function testForGetSchemaQualifiedTableName()
    {
        $this->assertEqual($this->schema->getSchemaQualifiedTableName('post'), '"public"."post"');
        $this->assertEqual($this->schema->getSchemaQualifiedTableName('post', array('schema' => 'blog')), '"blog"."post"');
    }

    public function testForGetVendorFieldType()
    {
        $dataTypes = array(
            'string' => 'VARCHAR',
            'text'   => 'TEXT',
            'blob'   => 'BYTEA',
            'json'   => 'JSONB',
            'int'    => 'INTEGER',
        );

        $definition = array('type' => 'string', 'length' => 120);
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'VARCHAR');

        $definition = array('type' => 'text', 'length' => 'long');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'TEXT');

        $definition = array('type' => 'blob');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'BYTEA');

        $definition = array('type' => 'json');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'JSONB');

        $definition = array('type' => 'unknown');
        $this->assertNull($this->schema->getVendorFieldType($definition, $dataTypes));
    }

    public function testForGetVendorFieldTypeBooleanSetsDefaults()
    {
        $dataTypes = array('boolean' => 'BOOLEAN');

        $definition = array('type' => 'boolean');
        $this->schema->getVendorFieldType($definition, $dataTypes);

        $this->assertEqual($definition['default'], false);
        $this->assertEqual($definition['null'], false);
    }

    public function testForGetFieldLength()
    {
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'string')), 255);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'string', 'length' => 200)), 200);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'int')), 0);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'boolean')), 0);

        $decimal = $this->schema->getFieldLength(array('type' => 'decimal', 'length' => array(10, 2)));
        $this->assertEqual($decimal, '10, 2');
    }

    public function testForShouldUseInlineIdentityPK()
    {
        // Plain int with primary/autoinc -> inline identity
        $this->assertTrue($this->schema->shouldUseInlineIdentityPK(array('type' => 'int', 'autoinc' => true)));
        $this->assertTrue($this->schema->shouldUseInlineIdentityPK(array('type' => 'bigint', 'primary' => true)));
        $this->assertTrue($this->schema->shouldUseInlineIdentityPK(array('type' => 'integer', 'autoinc' => true)));

        // Explicit serial types are NOT converted to identity
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array('type' => 'serial', 'autoinc' => true)));
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array('type' => 'bigserial', 'autoinc' => true)));

        // Non PK / non-numeric types
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array('type' => 'int')));
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array('type' => 'string')));
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array()));
    }

    public function testForIsSerialType()
    {
        $this->assertTrue($this->schema->isSerialType('serial'));
        $this->assertTrue($this->schema->isSerialType('bigserial'));
        $this->assertTrue($this->schema->isSerialType('smallserial'));
        $this->assertFalse($this->schema->isSerialType('int'));
    }

    public function testForBuildInlineIdentityPKStatement()
    {
        $statement = $this->schema->buildInlineIdentityPKStatement('id', 'INTEGER', array());
        $this->assertEqual($statement, 'id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY NOT NULL');

        $statement = $this->schema->buildInlineIdentityPKStatement('id', 'INTEGER', array('generator' => 'default'));
        $this->assertEqual($statement, 'id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY NOT NULL');
    }

    public function testForAppendDriverSpecificFieldStatementIsNoOp()
    {
        $out = $this->schema->appendDriverSpecificFieldStatement('"name" VARCHAR(120)', array('type' => 'string'), 'x', array());
        $this->assertEqual($out, '"name" VARCHAR(120)');
    }

    public function testForGetDefaultValueStatement()
    {
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => true, 'type' => 'boolean')), ' DEFAULT TRUE');
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => false, 'type' => 'boolean')), ' DEFAULT FALSE');
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => 'draft', 'type' => 'string')), " DEFAULT 'draft'");
    }

    public function testForGetAutoIncStatement()
    {
        $this->assertEqual($this->schema->getAutoIncStatement(array('primary' => true, 'type' => 'int')), ' PRIMARY KEY');
        $this->assertEqual($this->schema->getAutoIncStatement(array('autoinc' => true, 'type' => 'int')), ' PRIMARY KEY');
        $this->assertEqual($this->schema->getAutoIncStatement(array('type' => 'int')), '');
    }

    public function testForForeignKeyCheckStatementsAreNoOps()
    {
        $this->assertEqual($this->schema->getDisableFKCheckStatements(), array());
        $this->assertEqual($this->schema->getEnableFKCheckStatements(), array());
    }

    public function testForBuildTableIndexDefinitions()
    {
        $quote = array($this->schema, 'quoteIdentifier');
        $fkFields = array(
            'cat_id' => array('unique' => true),
        );

        $definitions = $this->schema->buildTableIndexDefinitions('post', $fkFields, array(), $quote);
        $this->assertContains('  CONSTRAINT "UQ_post_cat_id" UNIQUE ("cat_id")', $definitions);

        // Non-unique FK fields produce no inline definitions
        $fkFields['cat_id']['unique'] = false;
        $definitions = $this->schema->buildTableIndexDefinitions('post', $fkFields, array(), $quote);
        $this->assertCount(0, $definitions);
    }

    public function testForShouldSkipTablePrimaryKey()
    {
        // A single inline-identity PK is skipped at table level
        $pkFields = array(array('type' => 'int', 'autoinc' => true));
        $this->assertTrue($this->schema->shouldSkipTablePrimaryKey($pkFields));

        // Composite or explicit serial PKs are not skipped
        $pkFields = array(array('type' => 'int', 'autoinc' => true), array('type' => 'int'));
        $this->assertFalse($this->schema->shouldSkipTablePrimaryKey($pkFields));

        $pkFields = array(array('type' => 'serial', 'autoinc' => true));
        $this->assertFalse($this->schema->shouldSkipTablePrimaryKey($pkFields));

        $this->assertFalse($this->schema->shouldSkipTablePrimaryKey(array()));
    }

    public function testForGetCreateTableOptionsStatementIsEmpty()
    {
        $this->assertEqual($this->schema->getCreateTableOptionsStatement(array(), true), '');
    }

    public function testForPostCreateTableStatements()
    {
        $quote = array($this->schema, 'quoteIdentifier');
        $fkFields = array(
            'cat_id' => array('unique' => false),
        );

        $statements = $this->schema->getPostCreateTableStatements('post', '"post"', $fkFields, $quote);
        $this->assertContains('CREATE INDEX IF NOT EXISTS "IDX_post_cat_id" ON "post" ("cat_id");', $statements);

        // Unique FK fields are not indexed separately (they use a constraint)
        $fkFields['cat_id']['unique'] = true;
        $statements = $this->schema->getPostCreateTableStatements('post', '"post"', $fkFields, $quote);
        $this->assertCount(0, $statements);
    }

    public function testForAlterAndRenameStatements()
    {
        $quote = array($this->schema, 'quoteIdentifier');

        $alter = $this->schema->buildRenameColumnStatement('"post"', 'old_name', 'new_name', 'x', $quote);
        $this->assertEqual($alter, 'ALTER TABLE "post" RENAME COLUMN "old_name" TO "new_name";');

        // PostgreSQL has no column position modifier
        $this->assertEqual($this->schema->addColumnPosition('title', 'slug'), '');
    }

    public function testForBuildAlterColumnStatementWithTypeChange()
    {
        // A regular type change produces an ALTER ... TYPE ... USING cast
        $alter = $this->schema->buildAlterColumnStatement('"post"', '"title"', '"title" VARCHAR(255)');
        $this->assertIsString($alter);
        $this->assertStringContainsString('ALTER TABLE "post"', $alter);
        $this->assertStringContainsString('VARCHAR', $alter);
    }
}
