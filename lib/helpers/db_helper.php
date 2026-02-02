<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Backward compatibility layer for database operations
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 3.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\Database;
use LucidFrame\Core\QueryBuilder;

/**
 * Backward compatibility layer for database operations
 * This file ensures that existing MySQL applications work without code changes
 * when switching between MySQL and PostgreSQL drivers.
 */

/**
 * Initialize database connection and load appropriate helper functions
 * This function is called automatically when the Database class is instantiated
 */
function db_init_backward_compatibility()
{
    $db = _app('db');
    if (!$db) {
        return;
    }

    $driver = $db->getDriver();

    // Load driver-specific helper functions
    $helperFile = HELPER . 'db_helper.' . $driver . '.php';
    if (file_exists($helperFile)) {
        require_once $helperFile;
    } else {
        // Fallback to MySQL helper for backward compatibility
        if (file_exists(HELPER . 'db_helper.mysql.php')) {
            require_once HELPER . 'db_helper.mysql.php';
        }
    }
}

/**
 * Ensure backward compatibility for framework-specific features
 * This includes slug generation, timestamps, and soft deletes
 */

/**
 * Generate a unique slug for the given string and table
 * This function works consistently across both MySQL and PostgreSQL
 *
 * @param string $string Text to slug
 * @param string $table Table name to check uniqueness
 * @param array $condition Additional conditions for uniqueness check
 * @return string The generated unique slug
 */
function db_generate_slug($string, $table = '', array $condition = array())
{
    // Use the existing _slug function which already handles database operations
    return _slug($string, $table, $condition);
}

/**
 * Add timestamp fields to data array if table supports timestamps
 * This function works consistently across both MySQL and PostgreSQL
 *
 * @param string $table Table name without prefix
 * @param array $data Data array to modify
 * @param string $operation 'insert' or 'update'
 * @return array Modified data array with timestamps
 */
function db_add_timestamps($table, array $data, $operation = 'insert')
{
    $db = _app('db');

    if (!$db->hasTimestamps($table)) {
        return $data;
    }

    $now = date('Y-m-d H:i:s');

    if ($operation === 'insert') {
        if (!array_key_exists('created', $data)) {
            $data['created'] = $now;
        }
        if (!array_key_exists('updated', $data)) {
            $data['updated'] = $now;
        }
    } elseif ($operation === 'update') {
        if (!array_key_exists('updated', $data)) {
            $data['updated'] = $now;
        }
    }

    return $data;
}

/**
 * Handle soft delete operations consistently across drivers
 *
 * @param string $table Table name without prefix
 * @param array $condition Delete condition
 * @return bool Success status
 */
function db_soft_delete($table, array $condition = array())
{
    $db = _app('db');
    $driver = $db->getDriver();

    // Use driver-specific implementation if available
    $driverFunction = $driver . '_db_delete';
    if (function_exists($driverFunction)) {
        return call_user_func($driverFunction, $table, $condition, true);
    }

    // Fallback implementation
    QueryBuilder::clearBindValues();

    $table = db_table($table);
    $values = array();

    if (is_array($condition)) {
        list($condition, $values) = db_condition($condition);
    }

    if ($condition) {
        $condition = ' WHERE ' . $condition;
    }

    $sql = 'UPDATE ' . db_quote_identifier($table) . '
            SET ' . db_quote_identifier('deleted') . ' = :deleted ' . $condition;
    $values[':deleted'] = date('Y-m-d H:i:s');

    return db_query($sql, $values) ? true : false;
}

/**
 * Quote database identifiers (table names, column names) based on driver
 *
 * @param string $identifier The identifier to quote
 * @return string Quoted identifier
 */
function db_quote_identifier($identifier)
{
    $db = _app('db');
    $driver = $db->getDriver();

    // Use driver-specific quoting
    $driverFunction = $driver . '_db_quote';
    if (function_exists($driverFunction)) {
        return call_user_func($driverFunction, $identifier);
    }

    // Default to MySQL-style backticks for backward compatibility
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * Generate LIKE clause based on driver
 *
 * @param string $field Field name
 * @param string $value Value to search for
 * @param string $type Type of LIKE ('left', 'right', 'both')
 * @return string LIKE clause
 */
function db_like_clause($field, $value, $type = 'both')
{
    $db = _app('db');
    $driver = $db->getDriver();

    // Use driver-specific LIKE implementation
    $driverFunction = $driver . '_db_like';
    if (function_exists($driverFunction)) {
        return call_user_func($driverFunction, $field, $value, $type);
    }

    // Default MySQL implementation
    $quotedField = db_quote_identifier($field);
    switch ($type) {
        case 'left':
            return sprintf('%s LIKE CONCAT("%%", :%s)', $quotedField, $field);
        case 'right':
            return sprintf('%s LIKE CONCAT(:%s, "%%")', $quotedField, $field);
        case 'both':
        default:
            return sprintf('%s LIKE CONCAT("%%", :%s, "%%")', $quotedField, $field);
    }
}

/**
 * Generate LIMIT clause based on driver
 *
 * @param int $limit Number of records to limit
 * @param int $offset Number of records to skip
 * @return string LIMIT clause
 */
function db_limit_clause($limit, $offset = 0)
{
    $db = _app('db');
    $driver = $db->getDriver();

    // Use driver-specific LIMIT implementation
    $driverFunction = $driver . '_db_limit';
    if (function_exists($driverFunction)) {
        return call_user_func($driverFunction, $limit, $offset);
    }

    // Default MySQL implementation
    if ($offset > 0) {
        return sprintf('LIMIT %d, %d', $offset, $limit);
    }
    return sprintf('LIMIT %d', $limit);
}

/**
 * Handle boolean values consistently across drivers
 *
 * @param mixed $value Boolean value to convert
 * @param string $driver Database driver name
 * @return mixed Converted boolean value
 */
function db_convert_boolean($value, $driver = null)
{
    if ($driver === null) {
        $db = _app('db');
        $driver = $db->getDriver();
    }

    if ($driver === 'pgsql') {
        // PostgreSQL uses true/false strings
        return $value ? 'true' : 'false';
    } else {
        // MySQL uses 1/0 integers
        return $value ? 1 : 0;
    }
}

/**
 * Handle JSON values consistently across drivers
 *
 * @param mixed $value Value to convert to JSON
 * @param string $driver Database driver name
 * @return string|null JSON string or null
 */
function db_convert_json($value, $driver = null)
{
    if ($driver === null) {
        $db = _app('db');
        $driver = $db->getDriver();
    }

    if (is_array($value) || is_object($value)) {
        $jsonValue = json_encode($value);
        return $jsonValue ? $jsonValue : null;
    }

    return $value;
}

/**
 * Handle array values consistently across drivers
 *
 * @param mixed $value Value to convert for array storage
 * @param string $driver Database driver name
 * @return string|null Serialized array or null
 */
function db_convert_array($value, $driver = null)
{
    if (is_array($value)) {
        return serialize($value);
    }

    return $value;
}

/**
 * Process data values based on field types for consistent storage
 *
 * @param string $table Table name
 * @param array $data Data array to process
 * @return array Processed data array
 */
function db_process_data_types($table, array $data)
{
    $db = _app('db');
    $dsm = $db->schemaManager;

    if (!is_object($dsm) || !$dsm->isLoaded()) {
        return $data;
    }

    $driver = $db->getDriver();

    foreach ($data as $field => $value) {
        $fieldType = $dsm->getFieldType($table, $field);

        switch ($fieldType) {
            case 'boolean':
                $data[$field] = db_convert_boolean($value, $driver);
                break;
            case 'json':
                $data[$field] = db_convert_json($value, $driver);
                break;
            case 'array':
                $data[$field] = db_convert_array($value, $driver);
                break;
        }
    }

    return $data;
}

/**
 * Ensure transaction methods work consistently across drivers
 */

/**
 * Begin database transaction with backward compatibility
 *
 * @return bool Success status
 */
function db_begin_transaction()
{
    return _app('db')->beginTransaction();
}

/**
 * Commit database transaction with backward compatibility
 *
 * @return bool Success status
 */
function db_commit_transaction()
{
    return _app('db')->commit();
}

/**
 * Rollback database transaction with backward compatibility
 *
 * @return bool Success status
 */
function db_rollback_transaction()
{
    return _app('db')->rollback();
}

/**
 * Execute a transaction with automatic rollback on failure
 *
 * @param callable $callback Function to execute within transaction
 * @return mixed Result of callback or false on failure
 */
function db_transaction($callback)
{
    if (!is_callable($callback)) {
        return false;
    }

    $db = _app('db');

    try {
        $db->beginTransaction();
        $result = call_user_func($callback);
        $db->commit();
        return $result;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Migration helper functions for switching between drivers
 */

/**
 * Check if current driver supports a specific feature
 *
 * @param string $feature Feature name ('foreign_keys', 'json', 'boolean', etc.)
 * @return bool Whether feature is supported
 */
function db_supports_feature($feature)
{
    $db = _app('db');
    $driver = $db->getDriver();

    $features = array(
        'mysql' => array(
            'foreign_keys' => true,
            'json' => true,
            'boolean' => false, // Uses TINYINT
            'sequences' => false,
            'returning' => false,
        ),
        'pgsql' => array(
            'foreign_keys' => true,
            'json' => true,
            'boolean' => true,
            'sequences' => true,
            'returning' => true,
        ),
    );

    return isset($features[$driver][$feature]) ? $features[$driver][$feature] : false;
}

/**
 * Get driver-specific data type mapping
 *
 * @param string $frameworkType Framework data type
 * @param string $driver Database driver
 * @return string Database-specific data type
 */
function db_get_data_type($frameworkType, $driver = null)
{
    if ($driver === null) {
        $db = _app('db');
        $driver = $db->getDriver();
    }

    $typeMap = array(
        'mysql' => array(
            'int' => 'INT',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'boolean' => 'TINYINT(1)',
            'datetime' => 'DATETIME',
            'decimal' => 'DECIMAL',
            'json' => 'JSON',
            'array' => 'TEXT',
        ),
        'pgsql' => array(
            'int' => 'INTEGER',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'boolean' => 'BOOLEAN',
            'datetime' => 'TIMESTAMP',
            'decimal' => 'NUMERIC',
            'json' => 'JSONB',
            'array' => 'TEXT',
        ),
    );

    return isset($typeMap[$driver][$frameworkType]) ? $typeMap[$driver][$frameworkType] : $frameworkType;
}

// Initialize backward compatibility when this file is loaded
if (function_exists('_app') && _app('db')) {
    db_init_backward_compatibility();
}