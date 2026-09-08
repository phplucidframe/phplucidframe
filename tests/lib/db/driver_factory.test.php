<?php

use LucidFrame\Core\db\DatabaseException;
use LucidFrame\Core\db\drivers\DriverFactory;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for DriverFactory (lib/classes/db/drivers/DriverFactory.php)
 * Covers the driver infrastructure: supported drivers, class resolution,
 * configuration validation and default application.
 * DriverInterface is exercised through the driver implementations.
 */
class DriverFactoryTestCase extends LucidFrameTestCase
{
    public function testForSupportedDrivers()
    {
        $supportedDrivers = DriverFactory::getSupportedDrivers();

        $this->assertIsArray($supportedDrivers);
        $this->assertContains('mysql', $supportedDrivers);
        $this->assertContains('pgsql', $supportedDrivers);
    }

    public function testForIsSupported()
    {
        $this->assertTrue(DriverFactory::isSupported('mysql'));
        $this->assertTrue(DriverFactory::isSupported('pgsql'));
        // driver name check is case-insensitive
        $this->assertTrue(DriverFactory::isSupported('MySQL'));
        $this->assertTrue(DriverFactory::isSupported('PGSQL'));
        $this->assertFalse(DriverFactory::isSupported('sqlite'));
        $this->assertFalse(DriverFactory::isSupported(''));
    }

    public function testForGetDriverClass()
    {
        $this->assertEqual(DriverFactory::getDriverClass('mysql'), 'LucidFrame\Core\db\drivers\MySQLDriver');
        $this->assertEqual(DriverFactory::getDriverClass('pgsql'), 'LucidFrame\Core\db\drivers\PostgreSQLDriver');
    }

    public function testForCreateWithMissingDriver()
    {
        $thrown = false;
        try {
            DriverFactory::create(array());
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('driver not specified', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Missing driver key should throw DatabaseException');
    }

    public function testForCreateWithUnsupportedDriver()
    {
        $thrown = false;
        try {
            DriverFactory::create(array('driver' => 'sqlite'));
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('Unsupported database driver', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Unsupported driver should throw DatabaseException');
    }

    public function testForValidateConfigWithEmptyConfig()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array());
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertEqual($e->getStandardCode(), DatabaseException::DB_UNKNOWN_ERROR);
            $this->assertStringContainsString('Required database configuration field', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Empty config should throw DatabaseException');
    }

    public function testForValidateConfigWithMissingFields()
    {
        $fields = array('driver', 'host', 'database', 'username');

        foreach ($fields as $field) {
            $config = array(
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
            );
            unset($config[$field]);

            $thrown = false;
            try {
                DriverFactory::validateConfig($config);
            } catch (DatabaseException $e) {
                $thrown = true;
                $this->assertStringContainsString('"' . $field . '"', $e->getMessage());
            }
            $this->assertTrue($thrown, 'Missing "' . $field . '" should throw DatabaseException');
        }
    }

    public function testForValidateConfigWithUnsupportedDriver()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array(
                'driver'   => 'sqlite',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
            ));
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('Unsupported database driver', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Unsupported driver should throw DatabaseException');
    }

    public function testForValidateConfigWithValidMySQLConfig()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array(
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
            ));
        } catch (DatabaseException $e) {
            $thrown = true;
        }
        $this->assertFalse($thrown, 'Valid MySQL config should not throw DatabaseException');
    }

    public function testForValidateConfigWithValidPostgreSQLConfig()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array(
                'driver'   => 'pgsql',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
            ));
        } catch (DatabaseException $e) {
            $thrown = true;
        }
        $this->assertFalse($thrown, 'Valid PostgreSQL config should not throw DatabaseException');
    }

    public function testForValidateMySQLConfigWithInvalidCharset()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array(
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
                'charset'  => 'bogus-charset',
            ));
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('charset', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Invalid MySQL charset should throw DatabaseException');
    }

    public function testForValidatePostgreSQLConfigWithInvalidSslMode()
    {
        $thrown = false;
        try {
            DriverFactory::validateConfig(array(
                'driver'   => 'pgsql',
                'host'     => 'localhost',
                'database' => 'test',
                'username' => 'user',
                'sslmode'  => 'bogus',
            ));
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('SSL mode', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Invalid PostgreSQL SSL mode should throw DatabaseException');
    }

    public function testForApplyMySQLDefaults()
    {
        $config = DriverFactory::applyDefaults(array('driver' => 'mysql'));

        $this->assertEqual($config['port'], '3306');
        $this->assertEqual($config['charset'], 'utf8mb4');
        $this->assertEqual($config['collation'], 'utf8mb4_unicode_ci');
        $this->assertEqual($config['engine'], 'InnoDB');
        $this->assertEqual($config['prefix'], '');
    }

    public function testForApplyPostgreSQLDefaults()
    {
        $config = DriverFactory::applyDefaults(array('driver' => 'pgsql'));

        $this->assertEqual($config['port'], '5432');
        $this->assertEqual($config['charset'], 'utf8');
        $this->assertEqual($config['schema'], 'public');
        $this->assertEqual($config['sslmode'], 'prefer');
        $this->assertEqual($config['prefix'], '');
    }

    public function testForApplyDefaultsWithoutDriver()
    {
        $thrown = false;
        try {
            DriverFactory::applyDefaults(array());
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertStringContainsString('driver not specified', $e->getMessage());
        }
        $this->assertTrue($thrown, 'applyDefaults() without driver should throw DatabaseException');
    }
}
