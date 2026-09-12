<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\SchemaMySQL;

/**
 * Unit Test for SchemaMySQL class (lib/classes/db/SchemaMySQL.php)
 * Covers the MySQL schema-driver implementation: defaults, identifier
 * quoting, vendor field types/lengths, inline identity stubs, collation
 * statements, defaults, auto-increment, FK check statements, index building,
 * CREATE TABLE options and ALTER statements.
 * (getDropConstraintStatement requires a live database; it is covered by the
 * schema_manager_mysql round-trip test instead.)
 */
class SchemaMySqlTest extends LucidFrameTestCase
{
    /** @var SchemaMySQL */
    private $schema;

    public function setUp()
    {
        parent::setUp();

        $this->schema = new SchemaMySQL();
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
        $this->assertEqual($options['charset'], 'utf8mb4');
        $this->assertEqual($options['collate'], 'utf8mb4_general_ci');
        $this->assertEqual($options['engine'], 'InnoDB');
    }

    public function testForQuoteIdentifier()
    {
        $this->assertEqual($this->schema->quoteIdentifier('user'), '`user`');
        // Inner backticks are escaped
        $this->assertEqual($this->schema->quoteIdentifier('a`b'), '`a``b`');
    }

    public function testForGetSchemaQualifiedTableName()
    {
        $this->assertEqual($this->schema->getSchemaQualifiedTableName('post'), '`post`');
    }

    public function testForGetVendorFieldType()
    {
        $dataTypes = array(
            'string' => 'VARCHAR',
            'text'   => 'TEXT',
            'blob'   => 'BLOB',
            'json'   => 'JSON',
            'int'    => 'INT',
        );

        $definition = array('type' => 'string', 'length' => 120);
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'VARCHAR');

        $definition = array('type' => 'text', 'length' => 'long');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'LONGTEXT');

        $definition = array('type' => 'blob', 'length' => 'medium');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'MEDIUMBLOB');

        $definition = array('type' => 'json');
        $this->assertEqual($this->schema->getVendorFieldType($definition, $dataTypes), 'JSON');

        $definition = array('type' => 'unknown');
        $this->assertNull($this->schema->getVendorFieldType($definition, $dataTypes));
    }

    public function testForGetVendorFieldTypeBooleanForcesUnsignedAndDefault()
    {
        $dataTypes = array('boolean' => 'TINYINT(1)');

        $definition = array('type' => 'boolean');
        $type = $this->schema->getVendorFieldType($definition, $dataTypes);

        $this->assertEqual($type, 'TINYINT(1)');
        $this->assertTrue($definition['unsigned']);
        $this->assertEqual($definition['default'], false);
        $this->assertEqual($definition['null'], false);
    }

    public function testForGetFieldLength()
    {
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'string')), 255);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'string', 'length' => 20)), 20);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'int')), 11);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'boolean')), 1);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'text')), 0);
        $this->assertEqual($this->schema->getFieldLength(array('type' => 'json')), 0);

        $decimal = $this->schema->getFieldLength(array('type' => 'decimal', 'length' => array(10, 2)));
        $this->assertEqual($decimal, '10, 2');
    }

    public function testForIdentityHelpersAreInertOnMySQL()
    {
        // MySQL uses AUTO_INCREMENT, not inline identity
        $this->assertFalse($this->schema->shouldUseInlineIdentityPK(array('type' => 'int', 'autoinc' => true)));
        $this->assertFalse($this->schema->isSerialType('serial'));
        $this->assertEqual($this->schema->buildInlineIdentityPKStatement('id', 'INT', array()), '');
    }

    public function testForAppendDriverSpecificFieldStatement()
    {
        $statement = '`name` VARCHAR(120)';

        // String types get a COLLATE clause
        $out = $this->schema->appendDriverSpecificFieldStatement($statement, array('type' => 'string'), 'utf8mb4_unicode_ci', array('collate' => 'x'));
        $this->assertStringContainsString(' COLLATE utf8mb4_unicode_ci', $out);

        // Non-string types do not
        $out = $this->schema->appendDriverSpecificFieldStatement('`n` INT', array('type' => 'int'), 'utf8mb4_unicode_ci', array('collate' => 'x'));
        $this->assertStringNotContainsString('COLLATE', $out);

        // Unsigned is appended when declared
        $out = $this->schema->appendDriverSpecificFieldStatement('`n` INT', array('type' => 'int', 'unsigned' => true), '', array());
        $this->assertStringContainsString(' unsigned', $out);
    }

    public function testForGetDefaultValueStatement()
    {
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => null, 'type' => 'string')), ' DEFAULT NULL');
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => 'draft', 'type' => 'string')), " DEFAULT 'draft'");
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => true, 'type' => 'boolean')), ' DEFAULT 1');
        $this->assertEqual($this->schema->getDefaultValueStatement(array('default' => false, 'type' => 'boolean')), ' DEFAULT 0');
    }

    public function testForGetAutoIncStatement()
    {
        $this->assertEqual($this->schema->getAutoIncStatement(array('primary' => true, 'type' => 'int')), ' AUTO_INCREMENT');
        $this->assertEqual($this->schema->getAutoIncStatement(array('autoinc' => true, 'type' => 'int')), ' AUTO_INCREMENT');
        $this->assertEqual($this->schema->getAutoIncStatement(array('type' => 'int')), '');
    }

    public function testForForeignKeyCheckStatements()
    {
        $this->assertEqual($this->schema->getDisableFKCheckStatements(), array('SET FOREIGN_KEY_CHECKS=0;'));
        $this->assertEqual($this->schema->getEnableFKCheckStatements(), array('SET FOREIGN_KEY_CHECKS=1;'));
    }

    public function testForBuildTableIndexDefinitions()
    {
        $quote = array($this->schema, 'quoteIdentifier');
        $fkFields = array(
            'cat_id' => array('unique' => false),
        );

        $definitions = $this->schema->buildTableIndexDefinitions('post', $fkFields, array(), $quote);
        $this->assertContains('  KEY `IDX_cat_id` (`cat_id`)', $definitions);

        $fkFields['cat_id']['unique'] = true;
        $definitions = $this->schema->buildTableIndexDefinitions('post', $fkFields, array(), $quote);
        $this->assertContains('  UNIQUE KEY `IDX_cat_id` (`cat_id`)', $definitions);
    }

    public function testForSharedFeatures()
    {
        // MySQL always declares a table-level primary key
        $this->assertFalse($this->schema->shouldSkipTablePrimaryKey(array('id')));

        // No post-create statements are generated
        $this->assertEqual($this->schema->getPostCreateTableStatements('post', '`post`', array(), array($this->schema, 'quoteIdentifier')), array());
    }

    public function testForGetCreateTableOptionsStatement()
    {
        $options = array(
            'engine'  => 'InnoDB',
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
        );

        $sql = $this->schema->getCreateTableOptionsStatement($options, true);

        $this->assertStringContainsString(' ENGINE=InnoDB', $sql);
        $this->assertStringContainsString(' DEFAULT CHARSET=utf8mb4', $sql);
        $this->assertStringContainsString(' COLLATE=utf8mb4_unicode_ci', $sql);
        $this->assertStringContainsString(' AUTO_INCREMENT=1', $sql);
    }

    public function testForAlterStatements()
    {
        $quoted = '`post`';

        $alter = $this->schema->buildAlterColumnStatement($quoted, '`title`', '`title` VARCHAR(200)');
        $this->assertEqual($alter, 'ALTER TABLE `post` CHANGE COLUMN `title` `title` VARCHAR(200);');

        $rename = $this->schema->buildRenameColumnStatement($quoted, 'old_name', 'new_name', '`new_name` VARCHAR(200)', array($this->schema, 'quoteIdentifier'));
        $this->assertEqual($rename, 'ALTER TABLE `post` CHANGE COLUMN `old_name` `new_name` VARCHAR(200);');
    }

    public function testForAddColumnPosition()
    {
        $this->assertEqual($this->schema->addColumnPosition('title', 'slug'), ' AFTER `slug`');
        // The created column is always appended at the end
        $this->assertEqual($this->schema->addColumnPosition('created', 'slug'), '');
    }
}
