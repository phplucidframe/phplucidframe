<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for backward compatibility layer
 * Tests that existing MySQL applications work without code changes
 * and framework-specific features work consistently across drivers
 */
class BackwardCompatibilityTest extends LucidFrameTestCase
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
        $testName = 'Test Slug Generation';
        $expectedSlug = 'test-slug-generation';

        // Insert with slug generation
        $data = array(
            'name' => $testName,
            'description' => 'Testing slug generation'
        );

        $insertId = db_insert($this->testTable, $data, true);
        $this->assertNotFalse($insertId, 'Insert with slug should succeed');

        // Verify slug was generated
        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertNotEmpty($result->slug, 'Slug should be generated');
        $this->assertEquals($expectedSlug, $result->slug, 'Slug should match expected format');

        // Test duplicate slug handling
        $data2 = array(
            'name' => $testName,
            'description' => 'Testing duplicate slug handling'
        );

        $insertId2 = db_insert($this->testTable, $data2, true);
        $this->assertNotFalse($insertId2, 'Second insert with same name should succeed');

        $result2 = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId2));
        $this->assertNotEquals($result->slug, $result2->slug, 'Duplicate slugs should be handled');
        $this->assertStringStartsWith($expectedSlug, $result2->slug, 'Duplicate slug should start with base slug');
    }

    /**
     * Test timestamp functionality works consistently across drivers
     */
    public function testTimestampFunctionality()
    {
        $beforeInsert = date('Y-m-d H:i:s');

        // Insert without explicit timestamps
        $insertId = db_insert($this->testTable, $this->testData);
        $this->assertNotFalse($insertId, 'Insert should succeed');

        $afterInsert = date('Y-m-d H:i:s');

        // Verify timestamps were added
        $result = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertNotNull($result->created, 'Created timestamp should be set');
        $this->assertNotNull($result->updated, 'Updated timestamp should be set');

        // Verify timestamps are within expected range
        $this->assertGreaterThanOrEqual($beforeInsert, $result->created, 'Created timestamp should be after insert start');
        $this->assertLessThanOrEqual($afterInsert, $result->created, 'Created timestamp should be before insert end');

        // Test update timestamp
        sleep(1); // Ensure different timestamp
        $beforeUpdate = date('Y-m-d H:i:s');

        $updateResult = db_update($this->testTable, array(
            'id' => $insertId,
            'name' => 'Updated Name'
        ));
        $this->assertTrue($updateResult, 'Update should succeed');

        $afterUpdate = date('Y-m-d H:i:s');

        // Verify updated timestamp changed
        $updatedResult = db_fetchResult("SELECT * FROM " . db_table($this->testTable) . " WHERE id = :id", array(':id' => $insertId));
        $this->assertEquals($result->created, $updatedResult->created, 'Created timestamp should not change');
        $this->assertNotEquals($result->updated, $updatedResult->updated, 'Updated timestamp should change');
        $this->assertGreaterThanOrEqual($beforeUpdate, $updatedResult->updated, 'Updated timestamp should be recent');
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
        // Test successful transaction
        $result = db_transaction(function() {
            $id1 = db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 1')));
            $id2 = db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 2')));
            return array($id1, $id2);
        });

        $this->assertIsArray($result, 'Transaction should return result');
        $this->assertCount(2, $result, 'Transaction should return both IDs');

        // Verify both records were inserted
        $count = db_fetch("SELECT COUNT(*) FROM " . db_table($this->testTable) . " WHERE name LIKE 'Transaction Test%'");
        $this->assertEquals(2, $count, 'Both records should be inserted');

        // Test failed transaction (rollback)
        try {
            db_transaction(function() {
                db_insert($this->testTable, array_merge($this->testData, array('name' => 'Transaction Test 3')));
                // Force an error
                db_query("INVALID SQL STATEMENT");
                return true;
            });
            $this->fail('Transaction should have thrown an exception');
        } catch (Exception $e) {
            // Expected exception
        }

        // Verify rollback worked
        $count = db_fetch("SELECT COUNT(*) FROM " . db_table($this->testTable) . " WHERE name = 'Transaction Test 3'");
        $this->assertEquals(0, $count, 'Failed transaction should be rolled back');
    }

    /**
     * Test helper functions work consistently across drivers
     */
    public function testHelperFunctions()
    {
        // Test identifier quoting
        $quoted = db_quote_identifier('test_field');
        $driver = _app('db')->getDriver();

        if ($driver === 'mysql') {
            $this->assertEquals('`test_field`', $quoted, 'MySQL should use backticks');
        } else {
            $this->assertEquals('"test_field"', $quoted, 'PostgreSQL should use double quotes');
        }

        // Test LIKE clause generation
        $likeClause = db_like_clause('name', 'test', 'both');
        $this->assertStringContainsString('LIKE', $likeClause, 'LIKE clause should contain LIKE keyword');
        $this->assertStringContainsString(':name', $likeClause, 'LIKE clause should contain parameter placeholder');

        // Test LIMIT clause generation
        $limitClause = db_limit_clause(10, 5);
        $this->assertStringContainsString('10', $limitClause, 'LIMIT clause should contain limit');
        $this->assertStringContainsString('5', $limitClause, 'LIMIT clause should contain offset');
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
