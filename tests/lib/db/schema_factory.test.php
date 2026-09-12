<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\SchemaFactory;
use LucidFrame\Core\db\SchemaInterface;
use LucidFrame\Core\db\SchemaMySQL;
use LucidFrame\Core\db\SchemaPostgreSQL;

/**
 * Unit Test for SchemaFactory class (lib/classes/db/SchemaFactory.php)
 * Covers the schema-driver resolution by name.
 */
class SchemaFactoryTest extends LucidFrameTestCase
{
    public function testForCreateMySQL()
    {
        $driver = SchemaFactory::create('mysql');

        $this->assertIsA($driver, SchemaMySQL::class);
        $this->assertIsA($driver, SchemaInterface::class);
    }

    public function testForCreatePostgreSQL()
    {
        $driver = SchemaFactory::create('pgsql');

        $this->assertIsA($driver, SchemaPostgreSQL::class);
        $this->assertIsA($driver, SchemaInterface::class);
    }

    public function testForCreateIsCaseInsensitive()
    {
        $this->assertIsA(SchemaFactory::create('MySQL'), SchemaMySQL::class);
        $this->assertIsA(SchemaFactory::create('PGSQL'), SchemaPostgreSQL::class);
    }

    public function testForCreateUnknownDriverDefaultsToMySQL()
    {
        $this->assertIsA(SchemaFactory::create('sqlite'), SchemaMySQL::class);
        $this->assertIsA(SchemaFactory::create(''), SchemaMySQL::class);
    }
}