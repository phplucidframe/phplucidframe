<?php

/**
 * This file is part of the PHPLucidFrame library.
 * PostgreSQL database driver implementation
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
 * PostgreSQL database driver implementation
 * Handles PostgreSQL-specific database operations and connection management
 */
class PostgreSQLDriver implements DriverInterface
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
     * Establish PostgreSQL database connection with optimizations
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
                throw new \InvalidArgumentException("PostgreSQL configuration missing required key: {$key}");
            }
        }

        // Set PostgreSQL-specific defaults with optimizations
        extract($config);

        try {
            // Build optimized DSN with PostgreSQL-specific options
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $host,
                $port,
                $database
            );

            // Add PostgreSQL-specific connection options to DSN
            $this->addPostgreSQLDsnOptions($dsn, $config);

            // PostgreSQL-specific optimized PDO options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                \PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
                \PDO::ATTR_STRINGIFY_FETCHES => false, // Keep data types
                \PDO::ATTR_TIMEOUT => $timeout,
            ];

            // Enable persistent connections if configured
            if ($persistent) {
                $options[\PDO::ATTR_PERSISTENT] = true;
            }

            if (!extension_loaded('pdo_pgsql')) {
                throw new DatabaseException(
                    'PostgreSQL PDO driver is not installed. ' .
                    'Please enable pdo_pgsql extension in php.ini or use a different database driver.'
                );
            }

            $this->connection = new \PDO($dsn, $username, $password, $options);

            // Apply PostgreSQL-specific post-connection optimizations
            $this->applyPostgreSQLOptimizations($charset, $schema, $config);

            return $this->connection;

        } catch (\PDOException $e) {
            $this->errorCode = $e->getCode();
            $this->error = $e->getMessage();

            $standardCode = DatabaseException::mapDriverError($e->getCode(), 'pgsql');
            throw new DatabaseException(
                "PostgreSQL connection failed: " . $e->getMessage(),
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

            $standardCode = DatabaseException::mapDriverError($e->getCode(), 'pgsql');
            throw new DatabaseException(
                "PostgreSQL query failed: " . $e->getMessage(),
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
     * PostgreSQL uses sequences for auto-increment, so we need to specify the sequence name
     *
     * @param string $sequenceName Optional sequence name for PostgreSQL
     * @return int Last inserted ID
     */
    public function getLastInsertId($sequenceName = null)
    {
        return $this->connection ? $this->connection->lastInsertId($sequenceName) : 0;
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
     * PostgreSQL uses double quotes for identifier quoting
     *
     * @param string $identifier The identifier to quote
     * @return string Quoted identifier
     */
    public function quote($identifier)
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Get PostgreSQL-specific data types mapping
     *
     * @return array Array of data type mappings
     */
    public function getDataTypes()
    {
        return [
            'int' => 'INTEGER',
            'integer' => 'INTEGER',
            'bigint' => 'BIGINT',
            'smallint' => 'SMALLINT',
            'tinyint' => 'SMALLINT', // PostgreSQL doesn't have TINYINT, use SMALLINT
            'string' => 'VARCHAR',
            'varchar' => 'VARCHAR',
            'char' => 'CHAR',
            'text' => 'TEXT',
            'longtext' => 'TEXT', // PostgreSQL TEXT can handle large content
            'mediumtext' => 'TEXT',
            'boolean' => 'BOOLEAN',
            'bool' => 'BOOLEAN',
            'datetime' => 'TIMESTAMP',
            'timestamp' => 'TIMESTAMP',
            'date' => 'DATE',
            'time' => 'TIME',
            'decimal' => 'NUMERIC',
            'numeric' => 'NUMERIC',
            'float' => 'REAL',
            'double' => 'DOUBLE PRECISION',
            'json' => 'JSONB', // Use JSONB for better performance
            'array' => 'TEXT',
            'blob' => 'BYTEA',
            'binary' => 'BYTEA',
            'varbinary' => 'BYTEA'
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
     * Get PostgreSQL-specific SQL syntax for auto-increment
     * PostgreSQL uses SERIAL or IDENTITY columns
     *
     * @return string Auto-increment syntax
     */
    public function getAutoIncrementSyntax()
    {
        return 'SERIAL';
    }

    /**
     * Get PostgreSQL-specific LIMIT syntax
     * PostgreSQL uses LIMIT count OFFSET offset
     *
     * @param int $limit Number of records to limit
     * @param int $offset Number of records to skip
     * @return string LIMIT clause
     */
    public function getLimitSyntax($limit, $offset = 0)
    {
        if ($offset > 0) {
            return "LIMIT {$limit} OFFSET {$offset}";
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
     * Get PostgreSQL server version
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
     * PostgreSQL doesn't have a direct equivalent to MySQL's FOREIGN_KEY_CHECKS
     * This method provides a no-op for compatibility
     *
     * @param int $flag 0 to disable, 1 to enable (ignored in PostgreSQL)
     * @return bool Always returns true for PostgreSQL
     */
    public function setForeignKeyCheck($flag)
    {
        // PostgreSQL doesn't have a global foreign key check setting
        // Foreign key constraints are always enforced unless deferred
        return true;
    }

    /**
     * Enable foreign key checks (no-op for PostgreSQL compatibility)
     *
     * @return bool Always returns true for PostgreSQL
     */
    public function enableForeignKeyCheck()
    {
        return $this->setForeignKeyCheck(1);
    }

    /**
     * Disable foreign key checks (no-op for PostgreSQL compatibility)
     *
     * @return bool Always returns true for PostgreSQL
     */
    public function disableForeignKeyCheck()
    {
        return $this->setForeignKeyCheck(0);
    }

    /**
     * Get PostgreSQL-specific boolean value representation
     *
     * @param mixed $value The value to convert
     * @return string PostgreSQL boolean representation
     */
    public function getBooleanValue($value)
    {
        return $value ? 'TRUE' : 'FALSE';
    }

    /**
     * Get PostgreSQL-specific sequence name for a table's auto-increment column
     *
     * @param string $table Table name
     * @param string $column Column name (default: 'id')
     * @return string Sequence name
     */
    public function getSequenceName($table, $column = 'id')
    {
        return $table . '_' . $column . '_seq';
    }

    /**
     * Execute PostgreSQL-specific commands to reset sequence
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return bool True on success, false on failure
     */
    public function resetSequence($table, $column = 'id')
    {
        try {
            $sequenceName = $this->getSequenceName($table, $column);
            $sql = "SELECT setval('{$sequenceName}', COALESCE((SELECT MAX({$column}) FROM {$table}), 1), false)";
            $this->query($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get PostgreSQL-specific UPSERT syntax (INSERT ... ON CONFLICT)
     *
     * @param string $table Table name
     * @param array $data Data to insert/update
     * @param array $conflictColumns Columns that define the conflict
     * @param array $updateColumns Columns to update on conflict
     * @return string UPSERT SQL statement
     */
    public function getUpsertSyntax($table, array $data, array $conflictColumns, array $updateColumns = [])
    {
        $columns = array_keys($data);
        $placeholders = array_map(function ($col) {
            return ':' . $col;
        }, $columns);

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        if (!empty($conflictColumns)) {
            $sql .= " ON CONFLICT (" . implode(', ', $conflictColumns) . ")";

            if (!empty($updateColumns)) {
                $updates = array_map(function ($col) {
                    return "{$col} = EXCLUDED.{$col}";
                }, $updateColumns);
                $sql .= " DO UPDATE SET " . implode(', ', $updates);
            } else {
                $sql .= " DO NOTHING";
            }
        }

        return $sql;
    }

    /**
     * Add PostgreSQL-specific options to DSN
     *
     * @param string &$dsn DSN string (passed by reference)
     * @param array $config Configuration array
     * @return void
     */
    private function addPostgreSQLDsnOptions(&$dsn, array $config)
    {
        // SSL configuration
        if (isset($config['sslmode'])) {
            $dsn .= ';sslmode=' . $config['sslmode'];
        }

        if (isset($config['sslcert'])) {
            $dsn .= ';sslcert=' . $config['sslcert'];
        }

        if (isset($config['sslkey'])) {
            $dsn .= ';sslkey=' . $config['sslkey'];
        }

        if (isset($config['sslrootcert'])) {
            $dsn .= ';sslrootcert=' . $config['sslrootcert'];
        }

        if (isset($config['sslcrl'])) {
            $dsn .= ';sslcrl=' . $config['sslcrl'];
        }

        // Connection optimization options
        if (isset($config['connect_timeout'])) {
            $dsn .= ';connect_timeout=' . $config['connect_timeout'];
        }

        if (isset($config['application_name'])) {
            $dsn .= ';application_name=' . $config['application_name'];
        }

        // Client encoding
        if (isset($config['client_encoding'])) {
            $dsn .= ';client_encoding=' . $config['client_encoding'];
        }
    }

    /**
     * Apply PostgreSQL-specific post-connection optimizations
     *
     * @param string $charset Character encoding
     * @param string $schema Default schema
     * @param array $config Configuration array
     * @return void
     */
    private function applyPostgreSQLOptimizations($charset, $schema, array $config)
    {
        try {
            $optimizations = [];

            // Set client encoding
            $optimizations[] = "SET NAMES '{$charset}'";

            // Set search path to schema(s)
            $searchPath = is_array($schema) ? implode(',', $schema) : $schema;
            $optimizations[] = "SET search_path TO {$searchPath}";

            // Set timezone if specified
            if (isset($config['timezone'])) {
                $optimizations[] = "SET TIME ZONE '{$config['timezone']}'";
            }

            // Set statement timeout for query optimization
            if (isset($config['statement_timeout'])) {
                $optimizations[] = "SET statement_timeout = '{$config['statement_timeout']}'";
            }

            // Set lock timeout
            if (isset($config['lock_timeout'])) {
                $optimizations[] = "SET lock_timeout = '{$config['lock_timeout']}'";
            }

            // Set idle in transaction timeout
            if (isset($config['idle_in_transaction_session_timeout'])) {
                $optimizations[] = "SET idle_in_transaction_session_timeout = '{$config['idle_in_transaction_session_timeout']}'";
            }

            // Work memory optimization for complex queries
            if (isset($config['work_mem'])) {
                $optimizations[] = "SET work_mem = '{$config['work_mem']}'";
            }

            // Maintenance work memory for maintenance operations
            if (isset($config['maintenance_work_mem'])) {
                $optimizations[] = "SET maintenance_work_mem = '{$config['maintenance_work_mem']}'";
            }

            // Random page cost for query planner optimization
            if (isset($config['random_page_cost'])) {
                $optimizations[] = "SET random_page_cost = {$config['random_page_cost']}";
            }

            // Effective cache size for query planner
            if (isset($config['effective_cache_size'])) {
                $optimizations[] = "SET effective_cache_size = '{$config['effective_cache_size']}'";
            }

            // Default transaction isolation level
            if (isset($config['default_transaction_isolation'])) {
                $optimizations[] = "SET default_transaction_isolation = '{$config['default_transaction_isolation']}'";
            }

            // Enable/disable JIT compilation
            if (isset($config['jit'])) {
                $optimizations[] = "SET jit = " . ($config['jit'] ? 'on' : 'off');
            }

            // Execute all optimizations
            foreach ($optimizations as $sql) {
                $this->connection->exec($sql);
            }

        } catch (\PDOException $e) {
            // Log optimization failures but don't fail the connection
            error_log("PostgreSQL optimization warning: " . $e->getMessage());
        }
    }

    /**
     * Get PostgreSQL-specific connection pool configuration
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
            'validation_timeout' => 3,
            'prepared_statement_cache_queries' => $this->config['prepared_statement_cache_queries'] ?? 256,
            'prepared_statement_cache_size' => $this->config['prepared_statement_cache_size'] ?? '10MB'
        ];
    }

    /**
     * Optimize query execution for PostgreSQL
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @return \PDOStatement Optimized query result
     */
    public function optimizedQuery($sql, array $args = [])
    {
        // Add PostgreSQL-specific query optimizations
        $sql = $this->addPostgreSQLQueryOptimizations($sql);

        // Use prepared statement caching for better performance
        if (isset($this->config['use_prepared_cache']) && $this->config['use_prepared_cache']) {
            return $this->cachedPreparedQuery($sql, $args);
        }

        return $this->query($sql, $args);
    }

    /**
     * Add PostgreSQL-specific query optimizations
     *
     * @param string $sql Original SQL query
     * @return string SQL with optimization hints
     */
    private function addPostgreSQLQueryOptimizations($sql)
    {
        // Add query hints for PostgreSQL optimizer
        if (preg_match('/SELECT/i', $sql)) {
            // For large result sets, consider using cursor
            if (strpos($sql, 'LIMIT') === false && strpos($sql, 'COUNT(') === false) {
                // This is a potential large result set query
                // Could add hints or modify for cursor usage
            }

            // Optimize JOIN queries with explicit join conditions
            if (preg_match_all('/JOIN\s+(\w+)/i', $sql, $matches)) {
                // PostgreSQL benefits from explicit join order hints in complex queries
                // This could be enhanced based on table statistics
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
     * Vacuum table for better performance
     *
     * @param string $table Table name
     * @param bool $full Whether to perform full vacuum
     * @return bool True on success, false on failure
     */
    public function vacuumTable($table, $full = false)
    {
        try {
            $sql = $full ? "VACUUM FULL {$table}" : "VACUUM {$table}";
            $this->query($sql);
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
            $this->query("ANALYZE {$table}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reindex table for better performance
     *
     * @param string $table Table name
     * @return bool True on success, false on failure
     */
    public function reindexTable($table)
    {
        try {
            $this->query("REINDEX TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get PostgreSQL-specific query execution plan
     *
     * @param string $sql SQL query
     * @param array $args Query parameters
     * @param bool $analyze Whether to execute and analyze
     * @return array Query execution plan
     */
    public function explainQuery($sql, array $args = [], $analyze = false)
    {
        try {
            $explainSql = $analyze ? "EXPLAIN (ANALYZE, BUFFERS) " . $sql : "EXPLAIN " . $sql;
            $stmt = $this->query($explainSql, $args);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get PostgreSQL server configuration parameter
     *
     * @param string $parameter Parameter name
     * @return string Parameter value
     */
    public function getServerParameter($parameter)
    {
        try {
            $stmt = $this->query("SHOW {$parameter}");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? reset($result) : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if PostgreSQL extension is available
     *
     * @param string $extension Extension name
     * @return bool True if available, false otherwise
     */
    public function isExtensionAvailable($extension)
    {
        try {
            $stmt = $this->query("SELECT 1 FROM pg_available_extensions WHERE name = ?", [$extension]);
            return $stmt->fetchColumn() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enable PostgreSQL extension
     *
     * @param string $extension Extension name
     * @return bool True on success, false on failure
     */
    public function enableExtension($extension)
    {
        try {
            $this->query("CREATE EXTENSION IF NOT EXISTS {$extension}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
