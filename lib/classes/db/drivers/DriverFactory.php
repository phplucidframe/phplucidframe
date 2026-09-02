<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Database driver factory for multi-driver support
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

use LucidFrame\Core\db\DatabaseException;

/**
 * Factory class for creating database drivers
 * Handles driver instantiation and validation
 */
abstract class DriverFactory
{
    /**
     * Supported database drivers
     */
    public const SUPPORTED_DRIVERS = [
        'mysql' => 'MySQLDriver',
        'pgsql' => 'PostgreSQLDriver',
        // TODO: more drivers
    ];

    /**
     * Create a database driver instance
     *
     * @param array $config Database configuration
     * @return DriverInterface Driver instance
     * @throws DatabaseException If driver is not supported or cannot be created
     */
    public static function create(array $config)
    {
        if (!isset($config['driver'])) {
            throw new DatabaseException('Database driver not specified in configuration');
        }

        $driverName = strtolower($config['driver']);

        if (!self::isSupported($driverName)) {
            throw new DatabaseException(
                sprintf('Unsupported database driver: %s. Supported drivers: %s',
                    $driverName,
                    implode(', ', array_keys(self::SUPPORTED_DRIVERS))
                )
            );
        }

        $driverClass = self::getDriverClass($driverName);

        if (!class_exists($driverClass)) {
            throw new DatabaseException(
                sprintf('Driver class %s not found for driver %s', $driverClass, $driverName)
            );
        }

        $driver = new $driverClass();

        if (!$driver instanceof DriverInterface) {
            throw new DatabaseException(
                sprintf('Driver class %s must implement DriverInterface', $driverClass)
            );
        }

        return $driver;
    }

    /**
     * Check if a driver is supported
     *
     * @param string $driverName Driver name
     * @return bool True if supported, false otherwise
     */
    public static function isSupported($driverName)
    {
        return array_key_exists(strtolower($driverName), self::SUPPORTED_DRIVERS);
    }

    /**
     * Get the full class name for a driver
     *
     * @param string $driverName Driver name
     * @return string Full class name with namespace
     */
    public static function getDriverClass($driverName)
    {
        $className = self::SUPPORTED_DRIVERS[strtolower($driverName)];
        return __NAMESPACE__ . '\\' . $className;
    }

    /**
     * Get list of supported drivers
     *
     * @return array Array of supported driver names
     */
    public static function getSupportedDrivers()
    {
        return array_keys(self::SUPPORTED_DRIVERS);
    }

    /**
     * Validate driver configuration
     *
     * @param array $config Database configuration
     * @throws DatabaseException If configuration is invalid
     */
    public static function validateConfig(array $config)
    {
        $requiredFields = ['driver', 'host', 'database', 'username'];

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new DatabaseException(
                    sprintf('Required database configuration field "%s" is missing or empty', $field)
                );
            }
        }

        // Driver-specific validation
        $driverName = strtolower($config['driver']);

        if (!self::isSupported($driverName)) {
            throw new DatabaseException(
                sprintf('Unsupported database driver: %s', $driverName)
            );
        }

        // Validate driver-specific requirements
        switch ($driverName) {
            case 'mysql':
                self::validateMySQLConfig($config);
                break;
            case 'pgsql':
                self::validatePostgreSQLConfig($config);
                break;
        }
    }

    /**
     * Apply default values to configuration based on driver
     *
     * @param array $config Database configuration
     * @return array Configuration with defaults applied
     * @throws DatabaseException
     */
    public static function applyDefaults(array $config)
    {
        if (!isset($config['driver'])) {
            throw new DatabaseException('Database driver not specified in configuration');
        }

        $driverName = strtolower($config['driver']);

        // Apply driver-specific defaults
        switch ($driverName) {
            case 'mysql':
                $config = self::applyMySQLDefaults($config);
                break;
            case 'pgsql':
                $config = self::applyPostgreSQLDefaults($config);
                break;
            // TODO: more drivers
        }

        // Apply common defaults
        if (empty($config['prefix'])) {
            $config['prefix'] = '';
        }

        return $config;
    }

    /**
     * Validate MySQL-specific configuration
     *
     * @param array $config Configuration array
     * @throws DatabaseException If MySQL configuration is invalid
     */
    private static function validateMySQLConfig(array $config)
    {
        // Validate port if provided
        if (!empty($config['port']) && !is_numeric($config['port'])) {
            throw new DatabaseException('MySQL port must be numeric');
        }

        // Validate charset if provided
        if (!empty($config['charset'])) {
            $validCharsets = ['utf8', 'utf8mb4', 'latin1', 'ascii'];
            if (!in_array(strtolower($config['charset']), $validCharsets)) {
                throw new DatabaseException(
                    sprintf('Invalid MySQL charset "%s". Valid charsets: %s',
                        $config['charset'],
                        implode(', ', $validCharsets)
                    )
                );
            }
        }

        // Validate engine if provided
        if (!empty($config['engine'])) {
            $validEngines = ['InnoDB', 'MyISAM', 'Memory', 'Archive'];
            if (!in_array($config['engine'], $validEngines)) {
                throw new DatabaseException(
                    sprintf('Invalid MySQL engine "%s". Valid engines: %s',
                        $config['engine'],
                        implode(', ', $validEngines)
                    )
                );
            }
        }
    }

    /**
     * Validate PostgreSQL-specific configuration
     *
     * @param array $config Configuration array
     * @throws DatabaseException If PostgreSQL configuration is invalid
     */
    private static function validatePostgreSQLConfig(array $config)
    {
        // Validate port if provided
        if (!empty($config['port']) && !is_numeric($config['port'])) {
            throw new DatabaseException('PostgreSQL port must be numeric');
        }

        // Validate schema if provided
        if (isset($config['schema']) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $config['schema'])) {
            throw new DatabaseException(
                'PostgreSQL schema name must be a valid identifier (letters, numbers, underscores, starting with letter or underscore)'
            );
        }

        // Validate charset if provided
        if (!empty($config['charset'])) {
            $validCharsets = ['utf8', 'utf-8', 'latin1', 'iso-8859-1'];
            if (!in_array(strtolower($config['charset']), $validCharsets)) {
                throw new DatabaseException(
                    sprintf('Invalid PostgreSQL charset "%s". Valid charsets: %s',
                        $config['charset'],
                        implode(', ', $validCharsets)
                    )
                );
            }
        }

        // Validate SSL mode if provided
        if (!empty($config['sslmode'])) {
            $validSslModes = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
            if (!in_array($config['sslmode'], $validSslModes)) {
                throw new DatabaseException(
                    sprintf('Invalid PostgreSQL SSL mode "%s". Valid modes: %s',
                        $config['sslmode'],
                        implode(', ', $validSslModes)
                    )
                );
            }
        }
    }

    /**
     * Apply MySQL-specific default values
     *
     * @param array $config Configuration array
     * @return array Configuration with MySQL defaults applied
     */
    private static function applyMySQLDefaults(array $config)
    {
        // Set default port for MySQL
        if (empty($config['port'])) {
            $config['port'] = '3306';
        }

        // Set default charset for MySQL
        if (empty($config['charset'])) {
            $config['charset'] = 'utf8mb4';
        }

        // Set default collation for MySQL
        if (empty($config['collation'])) {
            $config['collation'] = 'utf8mb4_unicode_ci';
        }

        // Set default engine for MySQL
        if (empty($config['engine'])) {
            $config['engine'] = 'InnoDB';
        }

        return $config;
    }

    /**
     * Apply PostgreSQL-specific default values
     *
     * @param array $config Configuration array
     * @return array Configuration with PostgreSQL defaults applied
     */
    private static function applyPostgreSQLDefaults(array $config)
    {
        // Set default port for PostgreSQL
        if (empty($config['port'])) {
            $config['port'] = '5432';
        }

        // Set default charset for PostgreSQL
        if (empty($config['charset'])) {
            $config['charset'] = 'utf8';
        }

        // Set default schema for PostgreSQL
        if (empty($config['schema'])) {
            $config['schema'] = 'public';
        }

        // Set default SSL mode for PostgreSQL
        if (empty($config['sslmode'])) {
            $config['sslmode'] = 'prefer';
        }

        // Set default timeout for PostgreSQL
        if (empty($config['timeout'])) {
            $config['timeout'] = 30;
        }

        // Set default persistent for PostgreSQL
        if (!isset($config['persistent'])) {
            $config['persistent'] = false;
        }

        return $config;
    }
}
