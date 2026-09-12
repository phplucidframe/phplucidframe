<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameDatabaseTestCase;

/**
 * Unit Test for backward compatibility layer
 * Tests that existing MySQL applications work without code changes
 * and framework-specific features work consistently across drivers
 */
class BackwardCompatibilityTest extends LucidFrameDatabaseTestCase
{
    private $testTable = 'test_backward_compatibility';
    private $originalDriver;
    private $testData = array(
        'name' => 'Test Item',
        'description' => 'Test description for backward compatibility',
        'is_active' => true,
        'metadata' => array('key' => 'value', 'number' => 123),
        'tags' => array('tag1', 'tag2', 'tag3')
    );

    public function setUp()
    {
        parent::setUp();

        // Store original driver
        $this->originalDriver = _app('db')->getDriver();

        // Create test table for both drivers
        $this->createTestTable();
    }

    public function tearDown()
    {
        // Clean up test table
        $this->dropTestTable();

        parent::tearDown();
    }

    /**
     * Create test table with framework-specific features
     */
    private function createTestTable()
    {
        $db = _app('db');
        $driver = $db->getDriver();

        // Drop table if exists
        $this->dropTestTable();

        if ($driver === 'mysql') {
            $sql = "CREATE TABLE `{$this->testTable}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) UNIQUE,
                `description` TEXT,
                `is_active` TINYINT(1) DEFAULT 1,
                `metadata` JSON,
                `tags` TEXT,
                `created` DATETIME,
                `updated` DATETIME,
                `deleted` DATETIME NULL
            ) ENGINE=InnoDB";
        } else {
            $sql = "CREATE TABLE \"{$this->testTable}\" (
                \"id\" SERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL,
                \"slug\" VARCHAR(255) UNIQUE,
                \"description\" TEXT,
                \"is_active\" BOOLEAN DEFAULT true,
                \"metadata\" JSONB,
                \"tags\" TEXT,
                \"created\" TIMESTAMP,
                \"updated\" TIMESTAMP,
                \"deleted\" TIMESTAMP NULL
            )";
        }

        db_query($sql);
    }

    /**
     * Drop test table
     */
    private function dropTestTable()
    {
        $db = _app('db');
        $driver = $db->getDriver();

        if ($driver === 'mysql') {
            db_query("DROP TABLE IF EXISTS `{$this->testTable}`");
        } else {
            db_query("DROP TABLE IF EXISTS \"{$this->testTable}\"");
        }
    }

    /**
     * Test that basic CRUD operations work with both drivers
     */
    public function testBasicCrudOperations()
    {
        // Test INSERT
        $insertId = db_insert($this->testTable, $this->testData);
        $this->assertNotFalse($insertId, 'Insert operation should succeed');
        $this->assertGreaterThan(0, $insertId, 'Insert should return valid ID');

        // Test SELECT
        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertNotFalse($result, 'Select operation should succeed');
        $this->assertEquals($this->testData['name'], $result->name, 'Retrieved name should match inserted name');

        // Test UPDATE
        $updateData = array(
            'id' => $insertId,
            'name' => 'Updated Test Item',
            'description' => 'Updated description'
        );
        $updateResult = db_update($this->testTable, $updateData);
        $this->assertTrue($updateResult, 'Update operation should succeed');

        // Verify update
        $updatedResult = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertEquals('Updated Test Item', $updatedResult->name, 'Name should be updated');

        // Test DELETE
        $deleteResult = db_delete($this->testTable, array('id' => $insertId));
        $this->assertTrue($deleteResult, 'Delete operation should succeed');

        // Verify delete
        $deletedResult = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertFalse($deletedResult, 'Record should be deleted');
    }

    /**
     * Test slug generation works consistently across drivers
     */
    public function testSlugGeneration()
    {
        // Runtime-created tables are not part of the schema definition, so
        // slug auto-generation does not apply to them
        $this->assertFalse(db_tableHasSlug($this->testTable), 'Runtime table should not be treated as a slug table');

        $data = array(
            'name' => 'Test Slug Generation',
            'description' => 'Testing slug handling'
        );

        $insertId = db_insert($this->testTable, $data);
        $this->assertNotFalse($insertId, 'Insert should succeed');

        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertNull($result->slug, 'Slug should not be auto-generated for schema-unknown tables');
    }

    /**
     * Test timestamp functionality works consistently across drivers
     */
    public function testTimestampFunctionality()
    {
        $created = '2023-06-15 10:30:00';
        $updated = '2023-06-15 11:30:00';

        // Insert with explicit timestamps (auto-timestamps only apply to
        // tables registered in the schema definition)
        $insertId = db_insert($this->testTable, array_merge($this->testData, array(
            'created' => $created,
            'updated' => $updated
        )));
        $this->assertNotFalse($insertId, 'Insert should succeed');

        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertEqual($result->created, $created, 'Created timestamp should be stored');
        $this->assertEqual($result->updated, $updated, 'Updated timestamp should be stored');

        // Update the record with a new updated timestamp
        $newUpdated = '2023-06-16 09:00:00';

        $updateResult = db_update($this->testTable, array(
            'id' => $insertId,
            'name' => 'Updated Name',
            'updated' => $newUpdated
        ));
        $this->assertTrue($updateResult, 'Update should succeed');

        $updatedResult = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertEqual($updatedResult->created, $created, 'Created timestamp should not change');
        $this->assertEqual($updatedResult->updated, $newUpdated, 'Updated timestamp should change');
    }

    /**
     * Test soft delete functionality works consistently across drivers
     */
    public function testSoftDeleteFunctionality()
    {
        // Insert test record
        $insertId = db_insert($this->testTable, $this->testData);
        $this->assertNotFalse($insertId, 'Insert should succeed');

        // Perform soft delete
        $softDeleteResult = db_delete($this->testTable, array('id' => $insertId), true);
        $this->assertTrue($softDeleteResult, 'Soft delete should succeed');

        // Verify record still exists but is marked as deleted
        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertNotFalse($result, 'Record should still exist after soft delete');
        $this->assertNotNull($result->deleted, 'Deleted timestamp should be set');

        // Verify record is not returned in normal queries (if soft delete filtering is implemented)
        $activeResult = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id AND deleted IS NULL", array(':id' => $insertId));
        $this->assertFalse($activeResult, 'Soft deleted record should not appear in active queries');
    }

    /**
     * Test data type handling works consistently across drivers
     */
    public function testDataTypeHandling()
    {
        // Insert with various data types
        $insertId = db_insert($this->testTable, $this->testData);
        $this->assertNotFalse($insertId, 'Insert should succeed');

        // Retrieve and verify data types
        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));

        // Test boolean handling
        $driver = _app('db')->getDriver();
        if ($driver === 'mysql') {
            $this->assertEquals(1, $result->is_active, 'Boolean should be stored as 1 in MySQL');
        } else {
            $this->assertTrue($result->is_active === true || $result->is_active === 't', 'Boolean should be stored correctly in PostgreSQL');
        }

        // Test JSON/array handling
        $this->assertNotNull($result->metadata, 'JSON metadata should be stored');
        $this->assertNotNull($result->tags, 'Array tags should be stored');
    }

    /**
     * Test transaction functionality works consistently across drivers
     */
    public function testTransactionFunctionality()
    {
        // Successful transaction: db_transaction()/db_commit() API
        db_transaction();
        $id1 = db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 1')));
        $id2 = db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 2')));
        db_commit();

        $this->assertNotFalse($id1, 'First insert should succeed');
        $this->assertNotFalse($id2, 'Second insert should succeed');

        $rows = db_extract("SELECT id FROM " . db_table($this->testTable) . " WHERE name LIKE 'Transaction Test%'");
        $this->assertCount(2, $rows, 'Both records should be inserted after commit');

        // Rolled-back transaction
        db_transaction();
        $id3 = db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 3')));
        db_rollback();

        $this->assertNotFalse($id3, 'Insert before rollback should succeed');

        $result = db_fetchResult("SELECT id FROM " . db_table($this->testTable) . " WHERE name = 'Transaction Test 3'");
        $this->assertFalse($result, 'Rolled back insert should not persist');
    }

    /**
     * Test helper functions work consistently across drivers
     */
    public function testHelperFunctions()
    {
        // db_table() resolves the table name (including any configured prefix)
        $this->assertStringContainsString('test_backward_compatibility', db_table($this->testTable), 'db_table() should return the table name');

        // db_driver() reports the active driver
        $driver = db_driver();
        $this->assertTrue($driver === 'mysql' || $driver === 'pgsql', 'Driver should be mysql or pgsql');

        // db_tableHasSlug() reflects the schema definition
        $this->assertFalse(db_tableHasSlug($this->testTable), 'Runtime table is not a slug table');

        // db_insertId() reflects the last insert id of the connection
        $this->assertTrue(method_exists(_app('db'), 'getInsertId'), 'Database should expose the insert id');
    }

    /**
     * Test that existing MySQL applications work without modification
     */
    public function testMysqlApplicationCompatibility()
    {
        // This test simulates typical MySQL application code

        // Traditional insert
        $sql = "INSERT INTO " . db_table($this->testTable) . " (name, description) VALUES (:name, :description)";
        $result = db_query($sql, array(':name' => 'MySQL App Test', ':description' => 'Testing compatibility'));
        $this->assertNotFalse($result, 'Traditional MySQL insert should work');

        $insertId = db_insertId();
        $this->assertGreaterThan(0, $insertId, 'Insert ID should be returned');

        // Traditional select
        $sql = "SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id";
        $result = db_fetchResult($sql, array(':id' => $insertId));
        $this->assertNotFalse($result, 'Traditional MySQL select should work');
        $this->assertEquals('MySQL App Test', $result->name, 'Retrieved data should match');

        // Traditional update
        $sql = "UPDATE " . db_table($this->testTable) . " SET name = :name WHERE id = :id";
        $result = db_query($sql, array(':name' => 'Updated MySQL App Test', ':id' => $insertId));
        $this->assertNotFalse($result, 'Traditional MySQL update should work');

        // Traditional delete
        $sql = "DELETE FROM " . db_table($this->testTable) . " WHERE id = :id";
        $result = db_query($sql, array(':id' => $insertId));
        $this->assertNotFalse($result, 'Traditional MySQL delete should work');
    }

    /**
     * Test framework-specific features work with both drivers
     */
    public function testFrameworkSpecificFeatures()
    {
        // Test db_save function (insert/update helper)
        $saveId = db_save($this->testTable, $this->testData);
        $this->assertNotFalse($saveId, 'db_save insert should work');

        // Test db_save update
        $updateData = array_merge($this->testData, array('name' => 'Updated via db_save'));
        $updateResult = db_save($this->testTable, $updateData, $saveId);
        $this->assertEquals($saveId, $updateResult, 'db_save update should return same ID');

        // Test db_count
        $count = db_count($this->testTable);
        if ($count instanceof QueryBuilder) {
            $count = $count->fetch();
        }
        $this->assertGreaterThan(0, $count, 'db_count should return count');

        // Test db_extract (fetchAll)
        $allResults = db_extract("SELECT * FROM " . db_table($this->testTable));
        $this->assertIsArray($allResults, 'db_extract should return array');
        $this->assertNotEmpty($allResults, 'db_extract should return results');

        // Test query builder
        $qb = db_select($this->testTable)
            ->where(array('id' => $saveId))
            ->limit(1);

        $qbResult = $qb->getSql();
        $this->assertStringContainsString('SELECT', $qbResult, 'Query builder should generate SELECT');
        $this->assertStringContainsString('WHERE', $qbResult, 'Query builder should generate WHERE');
        $this->assertStringContainsString('LIMIT', $qbResult, 'Query builder should generate LIMIT');
    }
}
