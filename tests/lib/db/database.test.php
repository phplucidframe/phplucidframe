<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\Database;
use LucidFrame\Core\db\DatabaseException;
use LucidFrame\Core\db\SchemaManager;
use LucidFrame\Test\LucidFrameDatabaseTestCase;

/**
 * Unit Test for Database class (lib/classes/db/Database.php)
 * Covers connection, namespace handling, getters, the schema manager and the
 * driver instance. The LucidFrameDatabaseTestCase base restores the shared DB
 * instance after each test, so creating Database instances here is safe.
 */
class DatabaseTest extends LucidFrameDatabaseTestCase
{
    public function testForDefaultNamespace()
    {
        // The active namespace is resolved from the config, never a literal
        $source = _cfg('defaultDbSource');

        $db = new Database($source);

        $this->assertEqual($db->getNamespace(), $source);
        $this->assertEqual($db->getNamespace('default'), 'default');
    }

    public function testForConfigurationGetters()
    {
        $db = _app('db');
        $source = _cfg('defaultDbSource');

        $driver = $db->getDriver();
        $this->assertEqual($db->getHost(), _env("test.db.{$source}.{$driver}.host"));
        $this->assertEqual($db->getName(), _env("test.db.{$source}.{$driver}.database"));
        $this->assertEqual($db->getUser(), _env("test.db.{$source}.{$driver}.username"));
    }

    public function testForGetConnection()
    {
        $db = _app('db');

        $connection = $db->getConnection();

        $this->assertTrue(is_object($connection) || is_resource($connection));
    }

    public function testForDriverInstance()
    {
        $db = _app('db');

        $driver = $db->getDriverInstance();

        $this->assertIsA($driver, 'LucidFrame\Core\db\drivers\DriverInterface');
    }

    public function testForSchemaManager()
    {
        $db = _app('db');

        $manager = $db->getSchemaManager();

        $this->assertIsA($manager, 'LucidFrame\Core\db\SchemaManager');

        // The sample schema is loaded
        $this->assertTrue($manager->hasTable('post'));
        $this->assertTrue($manager->hasTable('user'));
        $this->assertFalse($manager->hasTable('no_such_table_xyz'));
    }

    public function testForSetSchemaManager()
    {
        $db = _app('db');
        $original = $db->getSchemaManager();

        $manager = new SchemaManager(array(), _cfg('defaultDbSource'));
        $db->setSchemaManager($manager);
        $this->assertIdentical($db->getSchemaManager(), $manager);

        // Restore the shared instance so other tests are unaffected
        $db->setSchemaManager($original);
    }

    public function testForGetTableWithPrefix()
    {
        $db = _app('db');

        // Without prefix the table name is unchanged
        $this->assertEqual($db->getTable('post'), 'post');
    }

    public function testForHasSlugAndTimestamps()
    {
        $db = _app('db');

        $this->assertTrue($db->hasSlug('post'));
        $this->assertFalse($db->hasSlug('post', false));
        $this->assertTrue($db->hasTimestamps('post'));
    }

    public function testForExp()
    {
        $db = _app('db');

        $exp = $db->exp('id', 5, '>');
        $this->assertEqual($exp['value'], 5);
        $this->assertEqual($exp['exp'], '>');
        $this->assertEqual($exp['field'], 'ID(5)');
    }

    public function testForFetchHelpers()
    {
        $db = _app('db');

        $rows = $db->fetchAll('SELECT * FROM user WHERE username = ?', array('admin'), LC_FETCH_OBJECT);
        $this->assertCount(1, $rows);
        $this->assertEqual($rows[0]->username, 'admin');

        $name = $db->fetchColumn('SELECT username FROM user WHERE username = ?', array('admin'));
        $this->assertEqual($name, 'admin');
    }

    public function testForConnectWithUnknownNamespaceThrows()
    {
        $thrown = false;
        try {
            $db = new Database('no_such_namespace');
            $db->connect('no_such_namespace');
        } catch (DatabaseException $e) {
            $thrown = true;
        } catch (\Exception $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, 'Connecting to an unknown namespace must fail');
    }
}
