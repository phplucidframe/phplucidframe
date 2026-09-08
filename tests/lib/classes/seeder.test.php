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
    /** @var string Temporary seed directory under db/seed/ */
    private $seedDir;

    public function setUp()
    {
        parent::setUp();

        $this->seedDir = DB . 'seed' . _DS_ . 'lc_seeder_test';
        if (!is_dir($this->seedDir)) {
            mkdir($this->seedDir, 0777, true);
        }

        file_put_contents($this->seedDir . '/tag.php', $this->tagSeedDefinition());
    }

    public function tearDown()
    {
        if (is_dir($this->seedDir)) {
            @unlink($this->seedDir . '/tag.php');
            @rmdir($this->seedDir);
        }

        parent::tearDown();
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
        $seeder = new Seeder('sample');

        $this->assertEqual($seeder->getDbNamespace(), 'sample');

        $seeder->setDbNamespace('other');
        $this->assertEqual($seeder->getDbNamespace(), 'other');
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
        $seeder = new Seeder('lc_seeder_test');

        $this->assertTrue($seeder->run());

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
        $seeder = new Seeder('lc_seeder_test');

        // Only 'category' is requested, which has no seed definition here
        $this->assertFalse($seeder->run(array('category')));
    }

    public function testForRunWithEmptyNamespace()
    {
        $seeder = new Seeder('lc_seeder_empty_namespace');

        $this->assertFalse($seeder->run());
    }
}