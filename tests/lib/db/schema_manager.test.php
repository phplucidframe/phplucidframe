<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\SchemaManager;

/**
 * Unit Test for the SchemaManager schema lock file handling
 * (lib/classes/db/SchemaManager.php): the default /db/build/ location, the
 * optional static lock directory override (used by the test suite to keep the
 * test lock files under db/build/test/) and the on-demand directory creation.
 */
class SchemaManagerTestCase extends LucidFrameTestCase
{
    /** @var string Temporary override lock directory */
    private $lockDir;

    public function setUp()
    {
        parent::setUp();

        // Start from the default location regardless of the test execution order
        SchemaManager::setLockDir(null);

        // A unique directory per test instance avoids the Windows
        // delete-pending window on a directory recreated right after removal
        $this->lockDir = sys_get_temp_dir() . _DS_ . 'lc_schema_lock_test_' . getmypid() . '_' . spl_object_id($this);
        $this->removeDir($this->lockDir);
    }

    public function tearDown()
    {
        SchemaManager::setLockDir(null);
        $this->removeDir($this->lockDir);

        parent::tearDown();
    }

    private function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) glob($dir . _DS_ . '*') as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    public function testForDefaultLockFileName()
    {
        // Without an override the lock files live in the default /db/build/
        $this->assertEqual(
            SchemaManager::getSchemaLockFileName('lc_lock_test'),
            DB . _DS_ . 'build' . _DS_ . 'schema.lc_lock_test.lock'
        );

        $this->assertEqual(
            SchemaManager::getSchemaLockFileName(),
            DB . _DS_ . 'build' . _DS_ . 'schema.lock'
        );
    }

    public function testForBackupLockFileName()
    {
        $this->assertEqual(
            SchemaManager::getSchemaLockFileName('lc_lock_test', true),
            DB . _DS_ . 'build' . _DS_ . '~schema.lc_lock_test.lock'
        );
    }

    public function testForLockDirOverride()
    {
        SchemaManager::setLockDir($this->lockDir);

        // Trailing separators are normalized on the given directory
        $this->assertEqual(
            SchemaManager::getSchemaLockFileName('lc_lock_test'),
            $this->lockDir . _DS_ . 'schema.lc_lock_test.lock'
        );

        $this->assertEqual(
            SchemaManager::getSchemaLockFileName(),
            $this->lockDir . _DS_ . 'schema.lock'
        );

        // Resetting restores the default /db/build/ location
        SchemaManager::setLockDir(null);
        $this->assertEqual(
            SchemaManager::getSchemaLockFileName('lc_lock_test'),
            DB . _DS_ . 'build' . _DS_ . 'schema.lc_lock_test.lock'
        );
    }

    public function testForBuildCreatesTheOverrideLockDir()
    {
        SchemaManager::setLockDir($this->lockDir);

        $this->assertFalse(is_dir($this->lockDir));

        $sm = new SchemaManager(array('_options' => array('timestamps' => true)));
        $this->assertTrue($sm->build('lc_lock_test'));

        // The override directory was created on demand and holds the lock file
        $this->assertTrue(is_dir($this->lockDir));
        $this->assertTrue(is_file($this->lockDir . _DS_ . 'schema.lc_lock_test.lock'));

        // The lock definition is read back from the override directory
        $definition = SchemaManager::getSchemaLockDefinition('lc_lock_test');
        $this->assertIsArray($definition);
        $this->assertArrayHasKey('_options', $definition);
        $this->assertTrue(isset($definition['_options']['timestamps']));

        // No lock file must leak into the default /db/build/
        $this->assertFalse(is_file(DB . _DS_ . 'build' . _DS_ . 'schema.lc_lock_test.lock'));
    }

    public function testForDefaultBuildLocationUnchanged()
    {
        // Without an override the lock file is written to the default /db/build/
        $sm = new SchemaManager(array('_options' => array('timestamps' => true)));
        $this->assertTrue($sm->build('lc_lock_test'));

        $file = DB . _DS_ . 'build' . _DS_ . 'schema.lc_lock_test.lock';
        $this->assertTrue(is_file($file));

        $definition = SchemaManager::getSchemaLockDefinition('lc_lock_test');
        $this->assertIsArray($definition);
        $this->assertArrayHasKey('_options', $definition);

        @unlink($file);
    }
}
