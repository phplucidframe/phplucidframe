<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Database driver interface for multi-driver support
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

namespace LucidFrame\Core\db\drivers;

/**
 * Interface for database drivers
 * Defines the contract that all database drivers must implement
 */
interface DriverInterface
{
    /**
     * Establish database connection
     *
     * @param array $config Database configuration array
     * @return mixed Database connection object
     * @throws \Exception If connection fails
     */
    public function connect(array $config);

    /**
     * Execute a database query
     *
     * @param string $sql SQL query string
     * @param array $args Array of parameters for prepared statements
     * @return mixed Query result
     * @throws \Exception If query execution fails
     */
    public function query($sql, array $args = []);

    /**
     * Get the last inserted ID
     *
     * @return int|string Last inserted ID
     */
    public function getLastInsertId();

    /**
     * Begin a database transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction();

    /**
     * Commit the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit();

    /**
     * Rollback the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback();

    /**
     * Get the last error message
     *
     * @return string Error message
     */
    public function getError();

    /**
     * Get the last error code
     *
     * @return int|string Error code
     */
    public function getErrorCode();

    /**
     * Quote an identifier (table name, column name, etc.)
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    public function quote($identifier);

    /**
     * Get driver-specific data types mapping
     *
     * @return array Array of data type mappings
     */
    public function getDataTypes();

    /**
     * Get the schema manager for this driver
     *
     * @return mixed Schema manager instance
     */
    public function getSchemaManager();

    /**
     * Get the database connection object
     *
     * @return mixed Database connection
     */
    public function getConnection();

    /**
     * Close the database connection
     *
     * @return void
     */
    public function close();

    /**
     * Fetch a result row as an associative array
     *
     * @param mixed $stmt Statement result
     * @return array|false Associative array or false
     */
    public function fetchAssoc($stmt);

    /**
     * Fetch a result row as a numeric array
     *
     * @param mixed $stmt Statement result
     * @return array|false Numeric array or false
     */
    public function fetchArray($stmt);

    /**
     * Fetch a result row as an object
     *
     * @param mixed $stmt Statement result
     * @return object|false Object or false
     */
    public function fetchObject($stmt);

    /**
     * Get the number of rows in a result set
     *
     * @param mixed $stmt Statement result
     * @return int Number of rows
     */
    public function getNumRows($stmt);

    /**
     * Fetch all results as an array
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @param int $resultType Result type constant
     * @return array|false Array of results or false
     */
    public function fetchAll($sql, array $args = [], $resultType = LC_FETCH_OBJECT);

    /**
     * Fetch a single column value
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return mixed Column value or false
     */
    public function fetchColumn($sql, array $args = []);

    /**
     * Fetch a single result row
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return object|false Result object or false
     */
    public function fetchResult($sql, array $args = []);

    /**
     * Get driver-specific connection pool configuration
     *
     * @return array Connection pool settings
     */
    public function getConnectionPoolConfig();

    /**
     * Execute optimized query with driver-specific optimizations
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return mixed Query result
     */
    public function optimizedQuery($sql, array $args = []);

    /**
     * Check if the connection is active
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected();

    /**
     * Get database server version
     *
     * @return string Server version
     */
    public function getServerVersion();
}
