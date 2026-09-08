<?php

namespace LucidFrame\Test;

use LucidFrame\Core\db\DatabaseException;

/**
 * Unit Test for DatabaseException class (lib/classes/db/DatabaseException.php)
 * Covers the standard codes, the getters, the exception code normalization,
 * the named constructors and the driver error-code mapping.
 */
class DatabaseExceptionTest extends LucidFrameTestCase
{
    public function testForDefaultState()
    {
        $e = new DatabaseException();

        $this->assertEqual($e->getMessage(), '');
        $this->assertEqual($e->getCode(), 0);
        $this->assertEqual($e->getStandardCode(), DatabaseException::DB_UNKNOWN_ERROR);
        $this->assertNull($e->getDriverCode());
        $this->assertEqual($e->getDriverMessage(), '');
    }

    public function testForGetters()
    {
        $e = new DatabaseException(
            'Duplicate entry for key',
            1062,
            null,
            1062,
            "Duplicate entry 'x' for key 'PRIMARY'",
            DatabaseException::DB_DUPLICATE_KEY_ERROR
        );

        $this->assertEqual($e->getMessage(), 'Duplicate entry for key');
        $this->assertEqual($e->getCode(), 1062);
        $this->assertEqual($e->getStandardCode(), DatabaseException::DB_DUPLICATE_KEY_ERROR);
        $this->assertEqual($e->getDriverCode(), 1062);
        $this->assertEqual($e->getDriverMessage(), "Duplicate entry 'x' for key 'PRIMARY'");
    }

    public function testForExceptionCodeNormalization()
    {
        // Integer codes are kept
        $this->assertEqual((new DatabaseException('m', 5))->getCode(), 5);

        // Numeric strings are cast to int
        $this->assertEqual((new DatabaseException('m', '123'))->getCode(), 123);

        // Non-numeric strings (e.g. SQLSTATE "42P16") are normalized to 0
        $this->assertEqual((new DatabaseException('m', '42P16'))->getCode(), 0);
    }

    public function testForStandardCodeConstants()
    {
        $constants = array(
            'DB_CONNECTION_ERROR',
            'DB_SYNTAX_ERROR',
            'DB_CONSTRAINT_ERROR',
            'DB_DUPLICATE_KEY_ERROR',
            'DB_FOREIGN_KEY_ERROR',
            'DB_NOT_NULL_ERROR',
            'DB_UNKNOWN_ERROR',
            'DB_DRIVER_ERROR',
            'DB_CONFIG_ERROR',
        );

        foreach ($constants as $constant) {
            $this->assertTrue(defined('LucidFrame\Core\db\DatabaseException::' . $constant), $constant . ' must be defined');
            $this->assertEqual(constant('LucidFrame\Core\db\DatabaseException::' . $constant), $constant);
        }
    }

    public function testForNamedConstructors()
    {
        $cases = array(
            array('connectionError', DatabaseException::DB_CONNECTION_ERROR),
            array('syntaxError', DatabaseException::DB_SYNTAX_ERROR),
            array('constraintError', DatabaseException::DB_CONSTRAINT_ERROR),
            array('duplicateKeyError', DatabaseException::DB_DUPLICATE_KEY_ERROR),
            array('driverError', DatabaseException::DB_DRIVER_ERROR),
        );

        foreach ($cases as $case) {
            list($method, $standardCode) = $case;

            $e = DatabaseException::$method('Some message', 12345, 'Driver message');

            $this->assertIsA($e, 'LucidFrame\Core\db\DatabaseException');
            $this->assertEqual($e->getMessage(), 'Some message');
            $this->assertEqual($e->getStandardCode(), $standardCode);
            $this->assertEqual($e->getDriverCode(), 12345);
            $this->assertEqual($e->getDriverMessage(), 'Driver message');
        }
    }

    public function testForConfigErrorNamedConstructor()
    {
        $e = DatabaseException::configError('Bad configuration');

        $this->assertEqual($e->getMessage(), 'Bad configuration');
        $this->assertEqual($e->getStandardCode(), DatabaseException::DB_CONFIG_ERROR);
        $this->assertNull($e->getDriverCode());
    }

    public function testForMapDriverErrorWithMySQLCodes()
    {
        $cases = array(
            1062 => DatabaseException::DB_DUPLICATE_KEY_ERROR,
            1586 => DatabaseException::DB_DUPLICATE_KEY_ERROR,
            1452 => DatabaseException::DB_FOREIGN_KEY_ERROR,
            1216 => DatabaseException::DB_FOREIGN_KEY_ERROR,
            1217 => DatabaseException::DB_FOREIGN_KEY_ERROR,
            1048 => DatabaseException::DB_NOT_NULL_ERROR,
            1364 => DatabaseException::DB_NOT_NULL_ERROR,
            1064 => DatabaseException::DB_SYNTAX_ERROR,
            1149 => DatabaseException::DB_SYNTAX_ERROR,
            1146 => DatabaseException::DB_SYNTAX_ERROR,
            1054 => DatabaseException::DB_SYNTAX_ERROR,
            1051 => DatabaseException::DB_SYNTAX_ERROR,
            2002 => DatabaseException::DB_CONNECTION_ERROR,
            2003 => DatabaseException::DB_CONNECTION_ERROR,
            2005 => DatabaseException::DB_CONNECTION_ERROR,
            1045 => DatabaseException::DB_CONNECTION_ERROR,
            1044 => DatabaseException::DB_CONNECTION_ERROR,
            1213 => DatabaseException::DB_CONSTRAINT_ERROR,
            99999 => DatabaseException::DB_UNKNOWN_ERROR,
        );

        foreach ($cases as $driverCode => $standardCode) {
            $this->assertEqual(
                DatabaseException::mapDriverError($driverCode, 'mysql'),
                $standardCode,
                'MySQL code ' . $driverCode
            );
        }
    }

    public function testForMapDriverErrorWithPostgreSQLCodes()
    {
        $cases = array(
            '23505' => DatabaseException::DB_DUPLICATE_KEY_ERROR,
            '23503' => DatabaseException::DB_FOREIGN_KEY_ERROR,
            '23504' => DatabaseException::DB_FOREIGN_KEY_ERROR,
            '23502' => DatabaseException::DB_NOT_NULL_ERROR,
            '23514' => DatabaseException::DB_NOT_NULL_ERROR,
            '42601' => DatabaseException::DB_SYNTAX_ERROR,
            '42000' => DatabaseException::DB_SYNTAX_ERROR,
            '42703' => DatabaseException::DB_SYNTAX_ERROR,
            '42P01' => DatabaseException::DB_SYNTAX_ERROR,
            '08006' => DatabaseException::DB_CONNECTION_ERROR,
            '08001' => DatabaseException::DB_CONNECTION_ERROR,
            '08003' => DatabaseException::DB_CONNECTION_ERROR,
            '08004' => DatabaseException::DB_CONNECTION_ERROR,
            '28000' => DatabaseException::DB_CONNECTION_ERROR,
            '28P01' => DatabaseException::DB_CONNECTION_ERROR,
            '40001' => DatabaseException::DB_CONSTRAINT_ERROR,
            '40P01' => DatabaseException::DB_CONSTRAINT_ERROR,
            '23000' => DatabaseException::DB_CONSTRAINT_ERROR,
            '99999' => DatabaseException::DB_UNKNOWN_ERROR,
        );

        foreach ($cases as $driverCode => $standardCode) {
            $this->assertEqual(
                DatabaseException::mapDriverError($driverCode, 'pgsql'),
                $standardCode,
                'PostgreSQL code ' . $driverCode
            );
        }
    }

    public function testForMapDriverErrorWithUnknownDriver()
    {
        $this->assertEqual(
            DatabaseException::mapDriverError(1062, 'sqlite'),
            DatabaseException::DB_UNKNOWN_ERROR
        );
    }

    public function testForIsThrowableAndCatchable()
    {
        $thrown = false;
        try {
            throw DatabaseException::connectionError('Cannot connect');
        } catch (DatabaseException $e) {
            $thrown = true;
            $this->assertEqual($e->getStandardCode(), DatabaseException::DB_CONNECTION_ERROR);
        }
        $this->assertTrue($thrown);
    }
}