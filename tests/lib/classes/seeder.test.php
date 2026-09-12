<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Seeder;

/**
 * Unit Test for Seeder class (lib/classes/Seeder.php)
 * Covers namespace handling, the reference key registry and a full run()
 * against the test database with a temporary seed definition directory that
 * is created in setUp and removed in tearDown.
 */
class SeederTest extends \LucidFrame\Test\LucidFrameDatabaseTestCase
{
    /** @var string Temporary seed directory under tests/db/seed/ */
    private $seedDir;
    /** @var string Directory path passed to the Seeder path override */
    private $seedPath;

    public function setUp()
    {
        parent::setUp();

        // The temporary seed definition lives under tests/db/seed/, never db/seed/
        $this->seedPath = TEST_DIR . 'db' . _DS_ . 'seed' . _DS_;
        $this->seedDir  = $this->seedPath . 'lc_seeder_test';
        $this->writeSeedFixture();
    }

    /**
     * Write the temporary seed definition file and verify it is readable
     *
     * Under filesystem pressure (real-time scanning, delete-pending
     * directories) a just-written file can transiently fail to stat; retry
     * the write+verify cycle a few times before giving up.
     *
     * @return void
     */
    private function writeSeedFixture()
    {
        $definition = $this->tagSeedDefinition();
        $file = $this->seedDir . _DS_ . 'tag.php';

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (!is_dir($this->seedDir)) {
                @mkdir($this->seedDir, 0777, true);
            }

            clearstatcache(true, $file);

            if (file_put_contents($file, $definition) !== false
                && is_file($file)) {
                return;
            }

            usleep(200000);
        }
    }

    public function tearDown()
    {
        // Remove the fixture file but KEEP the directory: on Windows a
        // just-removed directory stays delete-pending for a while, so a
        // recreate in the next setUp can transiently fail. The empty
        // directory leaves no trace in the repository.
        @unlink($this->seedDir . _DS_ . 'tag.php');

        parent::tearDown();
    }

    /**
     * Create a Seeder pointed at the test seed definition directory
     * @param string $namespace The database namespace
     * @return Seeder
     */
    private function newSeeder($namespace)
    {
        $seeder = new Seeder($namespace);
        $seeder->setPath($this->seedPath);

        return $seeder;
    }

    private function tagSeedDefinition()
    {
        return <<<'PHP'
<?php

return array(
    'order' => 1,
    'lc-seeder-tag-1' => array(
        'slug' => 'lc-seeder-php',
        'name' => 'PHP',
    ),
    'lc-seeder-tag-2' => array(
        'slug' => 'lc-seeder-mysql',
        'name' => 'MySQL',
    ),
);
PHP;
    }

    public function testForDefaultNamespace()
    {
        $seeder = new Seeder();

        $this->assertEqual($seeder->getDbNamespace(), 'default');
    }

    public function testForCustomNamespace()
    {
        // The active namespace is resolved from the config, never a literal
        $source = _cfg('defaultDbSource');

        $seeder = new Seeder($source);

        $this->assertEqual($seeder->getDbNamespace(), $source);

        $seeder->setDbNamespace('other');
        $this->assertEqual($seeder->getDbNamespace(), 'other');
    }

    public function testForDefaultPathIsDbSeed()
    {
        // The default seed path stays /db/seed/
        $this->assertEqual((new Seeder())->getPath(), DB . 'seed' . _DS_);
    }

    public function testForSetPath()
    {
        $seeder = new Seeder();

        $seeder->setPath(TEST_DIR . 'db' . _DS_ . 'seed');
        $this->assertEqual($seeder->getPath(), TEST_DIR . 'db' . _DS_ . 'seed' . _DS_);

        // Trailing separators are normalized
        $seeder->setPath('/tmp/lc_seed_test/');
        $this->assertEqual($seeder->getPath(), '/tmp/lc_seed_test' . _DS_);

        // NULL restores the default /db/seed/
        $seeder->setPath(null);
        $this->assertEqual($seeder->getPath(), DB . 'seed' . _DS_);
    }

    public function testForConstructorPathArgument()
    {
        $seeder = new Seeder('lc_seeder_test', TEST_DIR . 'db' . _DS_ . 'seed');

        $this->assertEqual($seeder->getPath(), TEST_DIR . 'db' . _DS_ . 'seed' . _DS_);
        $this->assertEqual($seeder->getDbNamespace(), 'lc_seeder_test');
    }

    public function testForGetReference()
    {
        $this->assertEqual(Seeder::getReference('post'), 'LucidFrame\Core\Seeder::post');
    }

    public function testForGetReferenceValueForUnknownKey()
    {
        $this->assertNull(Seeder::getReferenceValue('no-such-reference-key'));
    }

    public function testForRun()
    {
        $seeder = $this->newSeeder('lc_seeder_test');

        ob_start();
        $this->assertTrue($seeder->run());
        ob_end_clean();

        // The two seeded tags must exist in the database
        $tag1 = db_findOneBy('tag', array('slug' => 'lc-seeder-php'));
        $this->assertNotNull($tag1);
        $this->assertEqual($tag1->name, 'PHP');

        $tag2 = db_findOneBy('tag', array('slug' => 'lc-seeder-mysql'));
        $this->assertNotNull($tag2);
        $this->assertEqual($tag2->name, 'MySQL');

        // The insert IDs must be registered as references
        $this->assertEqual(Seeder::getReferenceValue('lc-seeder-tag-1'), $tag1->id);
        $this->assertEqual(Seeder::getReferenceValue('lc-seeder-tag-2'), $tag2->id);

        // Any pre-existing tag rows must have been purged by the seeding
        $this->assertEqual(count(db_findAll('tag')), 2);
    }

    public function testForRunWithUnknownEntities()
    {
        $seeder = $this->newSeeder('lc_seeder_test');

        // Only 'category' is requested, which has no seed definition here
        $this->assertFalse($seeder->run(array('category')));
    }

    public function testForRunWithEmptyNamespace()
    {
        $seeder = new Seeder('lc_seeder_empty_namespace');

        $this->assertFalse($seeder->run());
    }
}
