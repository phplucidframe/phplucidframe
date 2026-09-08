<?php
/**
 * This file is part of the PHPLucidFrame library.
 * This class is a base class for all unit test cases.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

/**
 * Pure unit test base class: no database required.
 * For tests that need the sample database, extend LucidFrameDatabaseTestCase instead.
 */
class LucidFrameTestCase extends \UnitTestCase
{
    public function setUp()
    {
        // Defensive reset: a test that enables print-query mode (db_prq(true))
        // must never leak it into subsequent tests
        _g('db_printQuery', false);
    }

    public function tearDown()
    {
    }

    public static function oneline($string)
    {
        return preg_replace('/\s{2,}/u', ' ', trim($string));
    }

    public static function toSql($clause, $values = array())
    {
        foreach ($values as $key => $value) {
            $clause = preg_replace('/' . $key . '\b/', $value, $clause);
        }

        return $clause;
    }

    /* =====================================================================
     * Assertion compatibility layer: PHPUnit-style assertion names built on
     * top of native SimpleTest assertions only. This keeps the suite running
     * unchanged on PHP 7.4 through the latest PHP 8.x under SimpleTest 1.3.
     * ===================================================================== */

    public function assertNotFalse($value, $message = '%s')
    {
        return $this->assertTrue($value !== false, $message);
    }

    public function assertNotTrue($value, $message = '%s')
    {
        return $this->assertFalse($value === true, $message);
    }

    public function assertEquals($expected, $actual, $message = '%s')
    {
        return $this->assertEqual($expected, $actual, $message);
    }

    public function assertNotEquals($expected, $actual, $message = '%s')
    {
        return $this->assertNotEqual($expected, $actual, $message);
    }

    public function assertGreaterThan($expected, $actual, $message = '%s')
    {
        return $this->assertTrue($actual > $expected, $message);
    }

    public function assertGreaterThanOrEqual($expected, $actual, $message = '%s')
    {
        return $this->assertTrue($actual >= $expected, $message);
    }

    public function assertLessThan($expected, $actual, $message = '%s')
    {
        return $this->assertTrue($actual < $expected, $message);
    }

    public function assertLessThanOrEqual($expected, $actual, $message = '%s')
    {
        return $this->assertTrue($actual <= $expected, $message);
    }

    public function assertIsArray($value, $message = '%s')
    {
        return $this->assertTrue(is_array($value), $message);
    }

    public function assertIsString($value, $message = '%s')
    {
        return $this->assertTrue(is_string($value), $message);
    }

    public function assertIsInt($value, $message = '%s')
    {
        return $this->assertTrue(is_int($value), $message);
    }

    public function assertIsBool($value, $message = '%s')
    {
        return $this->assertTrue(is_bool($value), $message);
    }

    public function assertIsNumeric($value, $message = '%s')
    {
        return $this->assertTrue(is_numeric($value), $message);
    }

    public function assertCount($expectedCount, $haystack, $message = '%s')
    {
        if (!is_countable($haystack)) {
            return $this->fail($message . ' [count() expects a countable value, ' . gettype($haystack) . ' given]');
        }

        return $this->assertTrue(count($haystack) === $expectedCount, $message);
    }

    public function assertEmpty($value, $message = '%s')
    {
        return $this->assertTrue(empty($value), $message);
    }

    public function assertNotEmpty($value, $message = '%s')
    {
        return $this->assertFalse(empty($value), $message);
    }

    public function assertStringContainsString($needle, $haystack, $message = '%s')
    {
        return $this->assertTrue(strpos($haystack, $needle) !== false, $message);
    }

    public function assertStringNotContainsString($needle, $haystack, $message = '%s')
    {
        return $this->assertFalse(strpos($haystack, $needle) !== false, $message);
    }

    public function assertStringStartsWith($prefix, $string, $message = '%s')
    {
        return $this->assertTrue(strpos($string, $prefix) === 0, $message);
    }

    public function assertStringEndsWith($suffix, $string, $message = '%s')
    {
        return $this->assertTrue($suffix === '' || substr($string, -strlen($suffix)) === $suffix, $message);
    }

    public function assertArrayHasKey($key, $array, $message = '%s')
    {
        return $this->assertTrue(is_array($array) && array_key_exists($key, $array), $message);
    }

    public function assertArrayNotHasKey($key, $array, $message = '%s')
    {
        return $this->assertFalse(is_array($array) && array_key_exists($key, $array), $message);
    }

    public function assertContains($needle, $haystack, $message = '%s')
    {
        if (is_string($haystack)) {
            return $this->assertTrue(strpos($haystack, $needle) !== false, $message);
        }

        return $this->assertTrue(in_array($needle, (array) $haystack), $message);
    }

    public function assertNotContains($needle, $haystack, $message = '%s')
    {
        if (is_string($haystack)) {
            return $this->assertFalse(strpos($haystack, $needle) !== false, $message);
        }

        return $this->assertFalse(in_array($needle, (array) $haystack), $message);
    }
}
