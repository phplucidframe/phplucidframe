<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Database exception class for standardized error handling
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 4.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core\db;

/**
 * Database exception class for standardized error handling across drivers
 */
class DatabaseException extends \Exception
{
    /**
     * Driver-specific error code
     * @var mixed
     */
    private $driverCode;

    /**
     * Driver-specific error message
     * @var string
     */
    private $driverMessage;

    /**
     * Standardized error code
     * @var string
     */
    private $standardCode;

    /**
     * Standard error codes
     */
    const DB_CONNECTION_ERROR = 'DB_CONNECTION_ERROR';
    const DB_SYNTAX_ERROR = 'DB_SYNTAX_ERROR';
    const DB_CONSTRAINT_ERROR = 'DB_CONSTRAINT_ERROR';
    const DB_DUPLICATE_KEY_ERROR = 'DB_DUPLICATE_KEY_ERROR';
    const DB_FOREIGN_KEY_ERROR = 'DB_FOREIGN_KEY_ERROR';
    const DB_NOT_NULL_ERROR = 'DB_NOT_NULL_ERROR';
    const DB_UNKNOWN_ERROR = 'DB_UNKNOWN_ERROR';
    const DB_DRIVER_ERROR = 'DB_DRIVER_ERROR';
    const DB_CONFIG_ERROR = 'DB_CONFIG_ERROR';

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param \Exception $previous Previous exception
     * @param mixed $driverCode Driver-specific error code
     * @param string $driverMessage Driver-specific error message
     * @param string $standardCode Standardized error code
     */
    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null,
        $driverCode = null,
        $driverMessage = '',
        $standardCode = self::DB_UNKNOWN_ERROR
    ) {
        parent::__construct($message, $this->normalizeExceptionCode($code), $previous);

        $this->driverCode = $driverCode;
        $this->driverMessage = $driverMessage;
        $this->standardCode = $standardCode;
    }

    /**
     * Normalize exception code for PHP's base Exception constructor.
     * SQLSTATE values are strings (e.g. "42P16"), but Exception requires int.
     *
     * @param mixed $code
     * @return int
     */
    private function normalizeExceptionCode($code)
    {
        if (is_int($code)) {
            return $code;
        }

        if (is_numeric($code)) {
            return (int) $code;
        }

        return 0;
    }

    /**
     * Get driver-specific error code
     *
     * @return mixed Driver error code
     */
    public function getDriverCode()
    {
        return $this->driverCode;
    }

    /**
     * Get driver-specific error message
     *
     * @return string Driver error message
     */
    public function getDriverMessage()
    {
        return $this->driverMessage;
    }

    /**
     * Get standardized error code
     *
     * @return string Standard error code
     */
    public function getStandardCode()
    {
        return $this->standardCode;
    }

    /**
     * Create a connection error exception
     *
     * @param string $message Error message
     * @param mixed $driverCode Driver-specific code
     * @param string $driverMessage Driver-specific message
     * @return DatabaseException
     */
    public static function connectionError($message, $driverCode = null, $driverMessage = '')
    {
        return new self(
            $message,
            0,
            null,
            $driverCode,
            $driverMessage,
            self::DB_CONNECTION_ERROR
        );
    }

    /**
     * Create a syntax error exception
     *
     * @param string $message Error message
     * @param mixed $driverCode Driver-specific code
     * @param string $driverMessage Driver-specific message
     * @return DatabaseException
     */
    public static function syntaxError($message, $driverCode = null, $driverMessage = '')
    {
        return new self(
            $message,
            0,
            null,
            $driverCode,
            $driverMessage,
            self::DB_SYNTAX_ERROR
        );
    }

    /**
     * Create a constraint violation exception
     *
     * @param string $message Error message
     * @param mixed $driverCode Driver-specific code
     * @param string $driverMessage Driver-specific message
     * @return DatabaseException
     */
    public static function constraintError($message, $driverCode = null, $driverMessage = '')
    {
        return new self(
            $message,
            0,
            null,
            $driverCode,
            $driverMessage,
            self::DB_CONSTRAINT_ERROR
        );
    }

    /**
     * Create a duplicate key exception
     *
     * @param string $message Error message
     * @param mixed $driverCode Driver-specific code
     * @param string $driverMessage Driver-specific message
     * @return DatabaseException
     */
    public static function duplicateKeyError($message, $driverCode = null, $driverMessage = '')
    {
        return new self(
            $message,
            0,
            null,
            $driverCode,
            $driverMessage,
            self::DB_DUPLICATE_KEY_ERROR
        );
    }

    /**
     * Create a driver error exception
     *
     * @param string $message Error message
     * @param mixed $driverCode Driver-specific code
     * @param string $driverMessage Driver-specific message
     * @return DatabaseException
     */
    public static function driverError($message, $driverCode = null, $driverMessage = '')
    {
        return new self(
            $message,
            0,
            null,
            $driverCode,
            $driverMessage,
            self::DB_DRIVER_ERROR
        );
    }

    /**
     * Create a configuration error exception
     *
     * @param string $message Error message
     * @return DatabaseException
     */
    public static function configError($message)
    {
        return new self(
            $message,
            0,
            null,
            null,
            '',
            self::DB_CONFIG_ERROR
        );
    }

    /**
     * Map driver-specific error to standard error code
     *
     * @param mixed $driverCode Driver error code
     * @param string $driverName Driver name (mysql, pgsql, etc.)
     * @return string Standard error code
     */
    public static function mapDriverError($driverCode, $driverName)
    {
        switch (strtolower($driverName)) {
            case 'mysql':
                return self::mapMySQLError($driverCode);
            case 'pgsql':
                return self::mapPostgreSQLError($driverCode);
            default:
                return self::DB_UNKNOWN_ERROR;
        }
    }

    /**
     * Map MySQL error codes to standard codes
     *
     * @param mixed $errorCode MySQL error code
     * @return string Standard error code
     */
    private static function mapMySQLError($errorCode)
    {
        switch ($errorCode) {
            case 1062: // Duplicate entry
            case 1586: // Duplicate entry for key
                return self::DB_DUPLICATE_KEY_ERROR;
            case 1452: // Foreign key constraint fails
            case 1216: // Cannot add or update a child row
            case 1217: // Cannot delete or update a parent row
                return self::DB_FOREIGN_KEY_ERROR;
            case 1048: // Column cannot be null
            case 1364: // Field doesn't have a default value
                return self::DB_NOT_NULL_ERROR;
            case 1064: // SQL syntax error
            case 1149: // SQL syntax error or access denied
                return self::DB_SYNTAX_ERROR;
            case 2002: // Connection refused
            case 2003: // Can't connect to server
            case 2005: // Unknown MySQL server host
            case 1045: // Access denied for user
            case 1044: // Access denied for user to database
                return self::DB_CONNECTION_ERROR;
            case 1146: // Table doesn't exist
            case 1054: // Unknown column
            case 1051: // Unknown table
                return self::DB_SYNTAX_ERROR;
            case 1213: // Deadlock found when trying to get lock
                return self::DB_CONSTRAINT_ERROR;
            default:
                return self::DB_UNKNOWN_ERROR;
        }
    }

    /**
     * Map PostgreSQL error codes to standard codes
     *
     * @param mixed $errorCode PostgreSQL error code
     * @return string Standard error code
     */
    private static function mapPostgreSQLError($errorCode)
    {
        switch ($errorCode) {
            case '23505': // Unique violation
                return self::DB_DUPLICATE_KEY_ERROR;
            case '23503': // Foreign key violation
            case '23504': // Foreign key violation
                return self::DB_FOREIGN_KEY_ERROR;
            case '23502': // Not null violation
            case '23514': // Check violation
                return self::DB_NOT_NULL_ERROR;
            case '42601': // Syntax error
            case '42000': // Syntax error or access rule violation
            case '42703': // Undefined column
            case '42P01': // Undefined table
                return self::DB_SYNTAX_ERROR;
            case '08006': // Connection failure
            case '08001': // Unable to connect
            case '08003': // Connection does not exist
            case '08004': // Server rejected the connection
            case '28000': // Invalid authorization specification
            case '28P01': // Invalid password
                return self::DB_CONNECTION_ERROR;
            case '40001': // Serialization failure
            case '40P01': // Deadlock detected
                return self::DB_CONSTRAINT_ERROR;
            case '23000': // Integrity constraint violation
                return self::DB_CONSTRAINT_ERROR;
            default:
                return self::DB_UNKNOWN_ERROR;
        }
    }
}
