<?php

/**
 * This file is part of the PHPLucidFrame library.
 * MySQL database driver implementation
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 4.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core\drivers;

use LucidFrame\Core\DatabaseException;
use LucidFrame\Core\SchemaManager;

/**
 * MySQL database driver implementation
 * Handles MySQL-specific database operations and connection management
 */
class MySQLDriver implements DriverInterface
{
    /** @var \PDO Database connection */
    private $connection;

    /** @var array Database configuration */
    private $config;

    /** @var string Last error message */
    private $error;

    /** @var int Last error code */
    private $errorCode;

    /** @var SchemaManager Schema manager instance */
    private $schemaManager;

    /** @var array Query history */
    private static $queries = [];

    /** @var array Bind parameters */
    private static $bindParams = [];

    /** @var array PDO fetch mode mapping */
    private static $FETCH_MODE_MAP = [
        LC_FETCH_OBJECT => \PDO::FETCH_OBJ,
        LC_FETCH_ASSOC => \PDO::FETCH_ASSOC,
        LC_FETCH_ARRAY => \PDO::FETCH_NUM
    ];

    /**
     * Establish MySQL database connection with optimizations
     *
     * @param array $config Database configuration array
     * @return \PDO Database connection object
     * @throws \Exception If connection fails
     */
    public function connect(array $config)
    {
        $this->config = $config;

        // Validate required configuration
        $required = ['host', 'database', 'username', 'password'];
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("MySQL configuration missing required key: {$key}");
            }
        }

        // Set MySQL-specific defaults with optimizations
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        $port = $config['port'] ?? 3306;
        $engine = $config['engine'] ?? 'InnoDB';
        $timeout = $config['timeout'] ?? 30;
        $persistent = $config['persistent'] ?? false;

        try {
            // Build optimized DSN
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $port,
                $config['database'],
                $charset
            );

            // MySQL-specific optimized PDO options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
                \PDO::ATTR_STRINGIFY_FETCHES => false, // Keep data types
                \PDO::ATTR_TIMEOUT => $timeout,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Buffer results for better memory usage
                \PDO::MYSQL_ATTR_INIT_COMMAND => $this->buildInitCommand($charset, $collation, $engine, $config),
            ];

            // Enable persistent connections if configured
            if ($persistent) {
                $options[\PDO::ATTR_PERSISTENT] = true;
            }

            // Enhanced SSL configuration
            if (isset($config['ssl']) && $config['ssl']) {
                $this->configureSslOptions($options, $config);
            }

            // Connection compression for better performance over network
            if (isset($config['compress']) && $config['compress']) {
                $options[\PDO::MYSQL_ATTR_COMPRESS] = true;
            }

            // Local infile support if needed
            if (isset($config['local_infile']) && $config['local_infile']) {
                $options[\PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
            }

            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);

            // Post-connection optimizations
            $this->applyPostConnectionOptimizations($config);

            return $this->connection;

        } catch (\PDOException $e) {
            $this->errorCode = $e->getCode();
            $this->error = $e->getMessage();

            $standardCode = DatabaseException::mapDriverError($e->getCode(), 'mysql');
            throw new DatabaseException(
                "MySQL connection failed: " . $e->getMessage(),
                $e->getCode(),
                $e,
                $e->getCode(),
                $e->getMessage(),
                $standardCode
            );
        }
    }

    /**
     * Execute a database query
     *
     * @param string $sql SQL query string
     * @param array $args Array of parameters for prepared statements
     * @return \PDOStatement Query result
     * @throws \Exception If query execution fails
     */
    public function query($sql, array $args = [])
    {
        if (!$this->connection) {
            throw new \Exception("No database connection available");
        }

        // Normalize parameters
        $params = [];
        foreach ($args as $key => $value) {
            if (is_numeric($key)) {
                $params[$key] = $value;
                continue;
            }

            if (strpos($key, ':') === false) {
                $key = ':' . $key;
            }

            $params[$key] = $value;
        }

        try {
            if (empty($params)) {
                $stmt = $this->connection->query($sql);
                self::$queries[] = $sql;
            } else {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                self::$queries[] = $sql;
                self::$bindParams = $params;
            }

            // Handle debug mode
            if (_g('db_printQuery')) {
                return $this->getQueryStr();
            }

            return $stmt;

        } catch (\PDOException $e) {
            $this->errorCode = $e->getCode();
            $this->error = $e->getMessage();

            $standardCode = DatabaseException::mapDriverError($e->getCode(), 'mysql');
            throw new DatabaseException(
                "MySQL query failed: " . $e->getMessage(),
                $e->getCode(),
                $e,
                $e->getCode(),
                $e->getMessage(),
                $standardCode
            );
        }
    }

    /**
     * Get the last inserted ID
     *
     * @return int Last inserted ID
     */
    public function getLastInsertId()
    {
        return $this->connection ? $this->connection->lastInsertId() : 0;
    }

    /**
     * Begin a database transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction()
    {
        return $this->connection ? $this->connection->beginTransaction() : false;
    }

    /**
     * Commit the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit()
    {
        return $this->connection ? $this->connection->commit() : false;
    }

    /**
     * Rollback the current transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback()
    {
        return $this->connection ? $this->connection->rollBack() : false;
    }

    /**
     * Get the last error message
     *
     * @return string Error message
     */
    public function getError()
    {
        return $this->error ?? '';
    }

    /**
     * Get the last error code
     *
     * @return int Error code
     */
    public function getErrorCode()
    {
        return $this->errorCode ?? 0;
    }

    /**
     * Quote an identifier (table name, column name, etc.)
     * MySQL uses backticks for identifier quoting
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    public function quote($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Get MySQL-specific data types mapping
     *
     * @return array Array of data type mappings
     */
    public function getDataTypes()
    {
        return [
            'int' => 'INT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'smallint' => 'SMALLINT',
            'tinyint' => 'TINYINT',
            'string' => 'VARCHAR',
            'varchar' => 'VARCHAR',
            'char' => 'CHAR',
            'text' => 'TEXT',
            'longtext' => 'LONGTEXT',
            'mediumtext' => 'MEDIUMTEXT',
            'boolean' => 'TINYINT(1)',
            'bool' => 'TINYINT(1)',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'date' => 'DATE',
            'time' => 'TIME',
            'decimal' => 'DECIMAL',
            'numeric' => 'DECIMAL',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'json' => 'JSON',
            'array' => 'TEXT',
            'blob' => 'BLOB',
            'binary' => 'BINARY',
            'varbinary' => 'VARBINARY'
        ];
    }

    /**
     * Get the schema manager for this driver
     *
     * @return SchemaManager Schema manager instance
     */
    public function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * Set the schema manager for this driver
     *
     * @param SchemaManager $schemaManager Schema manager instance
     * @return void
     */
    public function setSchemaManager($schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * Get the database connection object
     *
     * @return \PDO Database connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Close the database connection
     *
     * @return void
     */
    public function close()
    {
        $this->connection = null;
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param \PDOStatement $stmt Statement result
     * @return array|false Associative array or false
     */
    public function fetchAssoc($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;
    }

    /**
     * Fetch a result row as a numeric array
     *
     * @param \PDOStatement $stmt Statement result
     * @return array|false Numeric array or false
     */
    public function fetchArray($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_NUM) : false;
    }

    /**
     * Fetch a result row as an object
     *
     * @param \PDOStatement $stmt Statement result
     * @return object|false Object or false
     */
    public function fetchObject($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_OBJ) : false;
    }

    /**
     * Get the number of rows in a result set
     *
     * @param \PDOStatement $stmt Statement result
     * @return int Number of rows
     */
    public function getNumRows($stmt)
    {
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Fetch all results as an array
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @param int $resultType Result type constant
     * @return array|false Array of results or false
     */
    public function fetchAll($sql, array $args = [], $resultType = LC_FETCH_OBJECT)
    {
        if (is_numeric($args)) {
            if (in_array($args, [LC_FETCH_OBJECT, LC_FETCH_ASSOC, LC_FETCH_ARRAY])) {
                $resultType = $args;
            }
            $args = [];
        }

        $stmt = $this->query($sql, $args);
        if (!$stmt) {
            return false;
        }

        $data = $stmt->fetchAll(self::$FETCH_MODE_MAP[$resultType]);

        return count($data) ? $data : false;
    }

    /**
     * Fetch a single column value
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return mixed Column value or false
     */
    public function fetchColumn($sql, array $args = [])
    {
        $sql = $this->appendLimit($sql);

        $stmt = $this->query($sql, $args);
        if (!$stmt) {
            return false;
        }

        return $stmt->fetchColumn();
    }

    /**
     * Fetch a single result row
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return object|false Result object or false
     */
    public function fetchResult($sql, array $args = [])
    {
        $sql = $this->appendLimit($sql);

        $stmt = $this->query($sql, $args);
        if (!$stmt) {
            return false;
        }

        return $this->fetchObject($stmt);
    }

    /**
     * Get the last executed SQL string with bound parameters
     *
     * @return string The built and executed SQL string
     */
    public function getQueryStr()
    {
        $index = count(self::$queries) - 1;
        $sql = isset(self::$queries[$index]) ? self::$queries[$index] : '';

        if ($sql && count(self::$bindParams)) {
            foreach (self::$bindParams as $key => $value) {
                if (strpos($key, ':') === false) {
                    $key = ':' . $key;
                }

                if (is_array($value)) {
                    $value = implode(',', $value);
                    $regex = '/' . preg_quote($key, '/') . '\b/i';
                    $sql = preg_replace($regex, $value, $sql);
                } else {
                    $regex = '/' . preg_quote($key, '/') . '\b/i';
                    $sql = preg_replace($regex, $this->connection->quote($value), $sql);
                }
            }
        }

        return $sql;
    }

    /**
     * Append LIMIT clause to the SQL statement if not present
     *
     * @param string $sql The SQL statement
     * @return string SQL with LIMIT clause
     */
    private function appendLimit($sql)
    {
        if (!preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql)) {
            $sql .= ' LIMIT 1';
        }

        return $sql;
    }

    /**
     * Get MySQL-specific SQL syntax for auto-increment
     *
     * @return string Auto-increment syntax
     */
    public function getAutoIncrementSyntax()
    {
        return 'AUTO_INCREMENT';
    }

    /**
     * Get MySQL-specific LIMIT syntax
     *
     * @param int $limit Number of records to limit
     * @param int $offset Number of records to skip
     * @return string LIMIT clause
     */
    public function getLimitSyntax($limit, $offset = 0)
    {
        if ($offset > 0) {
            return "LIMIT {$offset}, {$limit}";
        }

        return "LIMIT {$limit}";
    }

    /**
     * Check if the connection is active
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected()
    {
        return $this->connection !== null;
    }

    /**
     * Get MySQL server version
     *
     * @return string Server version
     */
    public function getServerVersion()
    {
        if (!$this->connection) {
            return '';
        }

        return $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Execute MySQL-specific foreign key check commands
     *
     * @param int $flag 0 to disable, 1 to enable
     * @return bool True on success, false on failure
     */
    public function setForeignKeyCheck($flag)
    {
        try {
            $this->query('SET FOREIGN_KEY_CHECKS = ' . (int)$flag);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enable foreign key checks
     *
     * @return bool True on success, false on failure
     */
    public function enableForeignKeyCheck()
    {
        return $this->setForeignKeyCheck(1);
    }

    /**
     * Disable foreign key checks
     *
     * @return bool True on success, false on failure
     */
    public function disableForeignKeyCheck()
    {
        return $this->setForeignKeyCheck(0);
    }

    /**
     * Build MySQL initialization command with optimizations
     *
     * @param string $charset Character set
     * @param string $collation Collation
     * @param string $engine Default storage engine
     * @param array $config Full configuration array
     * @return string Initialization command
     */
    private function buildInitCommand($charset, $collation, $engine, array $config)
    {
        $commands = [];

        // Set character set and collation
        $commands[] = "SET NAMES {$charset} COLLATE {$collation}";

        // Set default storage engine
        $commands[] = "SET default_storage_engine = {$engine}";

        // Set SQL mode for better compatibility and performance
        $sqlMode = $config['sql_mode'] ?? 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
        $commands[] = "SET sql_mode = '{$sqlMode}'";

        // Set timezone if specified
        if (isset($config['timezone'])) {
            $commands[] = "SET time_zone = '{$config['timezone']}'";
        }

        // Performance optimizations
        if (isset($config['autocommit']) && !$config['autocommit']) {
            $commands[] = "SET autocommit = 0";
        }

        // Query cache optimization (if enabled)
        if (isset($config['query_cache_type'])) {
            $commands[] = "SET SESSION query_cache_type = {$config['query_cache_type']}";
        }

        return implode('; ', $commands);
    }

    /**
     * Configure SSL options for MySQL connection
     *
     * @param array &$options PDO options array (passed by reference)
     * @param array $config Configuration array
     * @return void
     */
    private function configureSslOptions(array &$options, array $config)
    {
        // Basic SSL configuration
        $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $config['ssl_verify_cert'] ?? false;

        // SSL certificate files
        if (isset($config['ssl_ca'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        }

        if (isset($config['ssl_cert'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
        }

        if (isset($config['ssl_key'])) {
            $options[\PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
        }

        if (isset($config['ssl_capath'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CAPATH] = $config['ssl_capath'];
        }

        if (isset($config['ssl_cipher'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CIPHER] = $config['ssl_cipher'];
        }
    }

    /**
     * Apply post-connection optimizations
     *
     * @param array $config Configuration array
     * @return void
     */
    private function applyPostConnectionOptimizations(array $config)
    {
        try {
            // Set session-specific optimizations
            $optimizations = [];

            // Transaction isolation level
            if (isset($config['isolation_level'])) {
                $optimizations[] = "SET SESSION TRANSACTION ISOLATION LEVEL {$config['isolation_level']}";
            }

            // Read buffer size optimization
            if (isset($config['read_buffer_size'])) {
                $optimizations[] = "SET SESSION read_buffer_size = {$config['read_buffer_size']}";
            }

            // Sort buffer size optimization
            if (isset($config['sort_buffer_size'])) {
                $optimizations[] = "SET SESSION sort_buffer_size = {$config['sort_buffer_size']}";
            }

            // Join buffer size optimization
            if (isset($config['join_buffer_size'])) {
                $optimizations[] = "SET SESSION join_buffer_size = {$config['join_buffer_size']}";
            }

            // Execute optimizations
            foreach ($optimizations as $sql) {
                $this->connection->exec($sql);
            }

        } catch (\PDOException $e) {
            // Log optimization failures but don't fail the connection
            error_log("MySQL optimization warning: " . $e->getMessage());
        }
    }

    /**
     * Get MySQL-specific connection pool configuration
     *
     * @return array Connection pool settings
     */
    public function getConnectionPoolConfig()
    {
        return [
            'max_connections' => $this->config['max_connections'] ?? 100,
            'min_connections' => $this->config['min_connections'] ?? 5,
            'connection_timeout' => $this->config['connection_timeout'] ?? 30,
            'idle_timeout' => $this->config['idle_timeout'] ?? 600,
            'max_lifetime' => $this->config['max_lifetime'] ?? 3600,
            'validation_query' => 'SELECT 1',
            'validation_timeout' => 3
        ];
    }

    /**
     * Optimize query execution for MySQL
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return \PDOStatement Optimized query result
     */
    public function optimizedQuery($sql, array $args = [])
    {
        // Add MySQL-specific query hints for optimization
        $sql = $this->addQueryHints($sql);

        // Use prepared statement caching for better performance
        if (isset($this->config['use_prepared_cache']) && $this->config['use_prepared_cache']) {
            return $this->cachedPreparedQuery($sql, $args);
        }

        return $this->query($sql, $args);
    }

    /**
     * Add MySQL-specific query hints for optimization
     *
     * @param string $sql Original SQL query
     * @return string SQL with optimization hints
     */
    private function addQueryHints($sql)
    {
        // Add USE INDEX hints for common patterns
        if (preg_match('/SELECT.*FROM\s+(\w+)/i', $sql, $matches)) {
            $table = $matches[1];

            // Add hints based on query patterns
            if (strpos($sql, 'ORDER BY') !== false && strpos($sql, 'LIMIT') !== false) {
                // For paginated queries, suggest using covering indexes
                $sql = str_replace("FROM {$table}", "FROM {$table} USE INDEX FOR ORDER BY", $sql);
            }
        }

        return $sql;
    }

    /**
     * Execute query with prepared statement caching
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return \PDOStatement Query result
     */
    private function cachedPreparedQuery($sql, array $args)
    {
        static $stmtCache = [];

        $cacheKey = md5($sql);

        if (!isset($stmtCache[$cacheKey])) {
            $stmtCache[$cacheKey] = $this->connection->prepare($sql);
        }

        $stmt = $stmtCache[$cacheKey];
        $stmt->execute($args);

        return $stmt;
    }

    /**
     * Get MySQL storage engine information
     *
     * @param string $table Table name
     * @return string Storage engine name
     */
    public function getStorageEngine($table = null)
    {
        if ($table) {
            try {
                $stmt = $this->query("SHOW TABLE STATUS LIKE ?", [$table]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $result['Engine'] ?? 'Unknown';
            } catch (\Exception $e) {
                return 'Unknown';
            }
        }

        return $this->config['engine'] ?? 'InnoDB';
    }

    /**
     * Optimize table for better performance
     *
     * @param string $table Table name
     * @return bool True on success, false on failure
     */
    public function optimizeTable($table)
    {
        try {
            $this->query("OPTIMIZE TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Analyze table for query optimization
     *
     * @param string $table Table name
     * @return bool True on success, false on failure
     */
    public function analyzeTable($table)
    {
        try {
            $this->query("ANALYZE TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
