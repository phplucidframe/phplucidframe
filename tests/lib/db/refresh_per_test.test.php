<?php

namespace LucidFrame\Test;

/**
 * Unit Test for the once-per-namespace schema import + the light per-test
 * cleanup of LucidFrameDatabaseTestCase (§13.3 of the unit tests refactor
 * plan): the schema + seed load once per namespace per test process (the
 * suite default) and each test case runs the light per-test cleanup
 * (schema-table truncation + re-seeding), which restores the seeded baseline
 * between the test cases.
 */
class RefreshPerTestOptInTestCase extends LucidFrameDatabaseTestCase
{
    /** @var boolean Opt in to the once-per-class schema + seed loading */
    protected $refreshPerTest = false;

    public function testForSeededBaseline()
    {
        $this->assertEqual(count(db_findAll('user')), 1);

        $user = db_findOneBy('user', array('username' => 'admin'));
        $this->assertNotNull($user);
        $this->assertEqual($user->full_name, 'Administrator');
    }

    public function testForMutation()
    {
        // Mutate the seeded row; the light per-test cleanup must restore it
        db_update('user', array('full_name' => 'Changed'), false, array('username' => 'admin'));

        $user = db_findOneBy('user', array('username' => 'admin'));
        $this->assertEqual($user->full_name, 'Changed');
    }

    public function testForLightCleanupRestoresSeed()
    {
        $user = db_findOneBy('user', array('username' => 'admin'));
        $this->assertNotNull($user);
        $this->assertEqual($user->full_name, 'Administrator');
    }
}
