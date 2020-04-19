<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package     PHPLucidFrame\File
 * @since       PHPLucidFrame v 2.2.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for file processing system
 */
class Database
{
    private $namespace = 'default';

    private $config = array();

    private $driver = 'mysql';
    private $host;
    private $port;
    private $username;
    private $password;
    private $name;
    private $charset = 'utf8';
    private $collation = 'utf8_unicode_ci';
    private $prefix = '';

    private $connection;

    public $schemaManager;

    private static $queries = array();
    private static $bindParams = array();

    private $errorCode;
    private $error;

    private static $FETCH_MODE_MAP = array(
        LC_FETCH_OBJECT => \PDO::FETCH_OBJ,
        LC_FETCH_ASSOC  => \PDO::FETCH_ASSOC,
        LC_FETCH_ARRAY  => \PDO::FETCH_NUM
    );

    public function __construct($namespace = null)
    {
        $this->config = _cfg('databases');
        if ($namespace === null) {
            $this->namespace = _cfg('defaultDbSource');
        }

        $this->connect();
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return mixed
     */
    public function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * @param mixed $schemaManager
     */
    public function setSchemaManager($schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * Start database connection
     * @param string $namespace
     * @return \PDO or PDOException
     */
    public function connect($namespace = null)
    {
        if ($namespace) {
            $this->namespace = $namespace;
        }

        $this->driver       = $this->getDriver();
        $this->host         = $this->getHost();
        $this->port         = $this->getPort();
        $this->username     = $this->getUser();
        $this->password     = $this->getPassword();
        $this->name         = $this->getName();
        $this->prefix       = $this->getPrefix();
        $this->charset      = $this->getCharset();
        $this->collation    = $this->getCollation();

        if ($this->driver) {
            if ($file = _i('helpers' . _DS_ . 'db_helper.php', false)) {
                include $file;
            }

            if ($this->driver === 'mysql') {
                require HELPER . 'db_helper.mysqli.php';
            } else {
                require HELPER . 'db_helper.' . $this->driver . '.php';
            }

            if ($this->getHost() && $this->getUser() && $this->getName()) {
                # Start DB connection
                $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s', $this->driver, $this->host, $this->name, $this->charset);
                $options  = array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => sprintf('SET NAMES %s COLLATE %s', $this->charset, $this->collation)
                );

                $this->connection = new \PDO($dsn, $this->username, $this->password, $options);

                # Load the schema of the currently connected database
                $schema = _schema($this->namespace, true);
                $this->schemaManager = new SchemaManager($schema);
                if (!$this->schemaManager->isLoaded()) {
                    $this->schemaManager->build($namespace);
                }
            }
        }

        return $this->connection;
    }

    /**
     * Return the current database namespace
     * if $namespace is not provided, $lc_defaultDbSource will be returned
     * if $lc_defaultDbSource is empty, `default` will be returned
     *
     * @param string $namespace The given namespace
     * @return string The database namespace
     */
    protected function getNamespace($namespace = null)
    {
        if (!empty($namespace)) {
            return $namespace;
        }

        return $this->namespace;
    }

    /**
     * Return the database configuration of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return array The array of database configuration
     */
    protected function getConfig($namespace = null)
    {
        $namespace = $this->getNamespace($namespace);

        if (!isset($this->config[$namespace])) {
            die('Database configuration error for ' . $namespace . '!');
        }

        return $this->config[$namespace];
    }

    /**
     * Return the database driver of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database driver name
     */
    public function getDriver($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));
        // @TODO: to remove backward compatible "engine"
        $driver = isset($conf['driver']) ? $conf['driver'] : $conf['engine'];

        return $driver;
    }

    /**
     * Return the database host name of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database host name
     */
    public function getHost($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));

        return $conf['host'];
    }

    /**
     * Return the database port of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database port
     */
    public function getPort($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));

        return $conf['port'];
    }

    /**
     * Return the database name of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database name
     */
    public function getName($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));
        if (!isset($conf['database'])) {
            die('Database name is not set.');
        }

        return $conf['database'];
    }

    /**
     * Return the database user name of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database username
     */
    public function getUser($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));
        if (!isset($conf['username'])) {
            die('Database username is not set.');
        }

        return $conf['username'];
    }

    /**
     * Return the database password of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database password
     */
    private function getPassword($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));
        if (!isset($conf['password'])) {
            die('Database password is not set.');
        }

        return $conf['password'];
    }

    /**
     * Return the database table prefix of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string The table prefix
     */
    public function getPrefix($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));

        return isset($conf['prefix']) ? $conf['prefix'] : $this->prefix;
    }

    /**
     * Return the database charset of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database charset
     */
    public function getCharset($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));

        return isset($conf['charset']) ? $conf['charset'] : $this->charset;
    }

    /**
     * Return the database collation of the given namespace
     * @param string $namespace Namespace of the configuration to read from
     * @return string Database collation
     */
    public function getCollation($namespace = null)
    {
        $conf = $this->getConfig($this->getNamespace($namespace));

        return isset($conf['collation']) ? $conf['collation'] : $this->collation;
    }

    /**
     * Perform a query on the database
     *
     * @param string $sql SQL query string
     * @param array $args Array of placeholders and their values
     *     array(
     *       ':placeholder1' => $value1,
     *       ':placeholder2' => $value2
     *     )
     * The prefix colon ":" for placeholder is optional
     *
     * @return mixed PDOStatement|boolean
     */
    public function query($sql, $args = array())
    {
        if (!is_array($args)) {
            $args = array();
        }

        $params = array();
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
                // _pr($params);
                $stmt->execute($params);
                self::$queries[] = $sql;
                self::$bindParams = $params;
            }

            if (_g('db_printQuery')) {
                return $this->getQueryStr();
            }
        } catch (\PDOException $e) {
            $this->errorCode = $e->getCode();
            $this->error = $e->getMessage();

            throw $e;
        }

        return $stmt;
    }

    /**
     * Get the last executed SQL string or one of the executed SQL strings by providing the index
     *
     * @param  int The index number of the query returned; if not given, the last query is returned
     * @return string Return the built and executed SQL string
     */
    public function getQueryStr()
    {
        $arg = func_get_args();
        $index = count($arg) == 0 ? count(self::$queries) - 1 : 0;

        $sql = isset(self::$queries[$index]) ? self::$queries[$index] : '';

        if ($sql && count(self::$bindParams)) {
            foreach (self::$bindParams as $key => $value) {
                if (strpos($key, ':') === false) {
                    $key = ':'.$key;
                }

                if (is_array($value)) {
                    $value = implode(',', $value);
                    $regex = '/'.$key.'\b/i';
                    $sql = preg_replace($regex, $value, $sql);
                } else {
                    $regex = '/'.$key.'\b/i';
                    $sql = preg_replace($regex, $value, $sql);
                }
            }
        }

        return $sql;
    }

    /**
     * Fetch a result row as an associative array
     * @param  \PDOStatement $stmt
     * @return array|false An associative array that corresponds to the fetched row or NULL if there are no more rows.
     */
    public function fetchAssoc($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;
    }

    /**
     * Fetch a result row as an associative, a numeric array, or both
     * @param  \PDOStatement $stmt
     * @return array|false An array that corresponds to the fetched row or
     *   NULL if there are no more rows for the result set represented by the result parameter.
     */
    public function fetchArray($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_NUM) : false;
    }

    /**
     * Returns the current row of a result set as an object
     * @param  \PDOStatement $stmt
     * @return object|false An object that corresponds to the fetched row or NULL if there are no more rows in result set.
     */
    public function fetchObject($stmt)
    {
        return $stmt ? $stmt->fetch(\PDO::FETCH_OBJ) : false;
    }

    /**
     * Perform a query on the database and return the array of all results
     *
     * @param string $sql The SQL query string
     * @param array $args The array of placeholders and their values
     * @param int $resultType The optional constant indicating what type of array should be produced.
     *   The possible values for this parameter are the constants
     *   **LC_FETCH_OBJECT**, **LC_FETCH_ASSOC**, or **LC_FETCH_ARRAY**.
     *   Default to **LC_FETCH_OBJECT**.
     *
     * @return array|boolean The result array of objects or associated arrays or index arrays.
     *   If the result not found, return false.
     */
    public function fetchAll($sql, $args = array(), $resultType = LC_FETCH_OBJECT)
    {
        if (is_numeric($args)) {
            if (in_array($args, array(LC_FETCH_OBJECT, LC_FETCH_ASSOC, LC_FETCH_ARRAY))) {
                $resultType = $args;
            }
            $args = array();
        }

        $stmt = $this->query($sql, $args);
        $data = $stmt->fetchAll(self::$FETCH_MODE_MAP[$resultType]);

        return count($data) ? $data : false;
    }

    /**
     * Perform a query on the database and return the first result row as object
     *
     * It adds the `LIMIT 1` clause if the query has no record limit
     * This is useful for one-row fetching. No need explicit `db_query()` call as this invokes it internally.
     *
     * @param string $sql The SQL query string
     * @param array $args The array of placeholders and their values
     *
     *      array(
     *          ':placeholder1' => $value1,
     *          ':placeholder2' => $value2
     *      )
     *
     * @return object|boolean The result object
     */
    function fetchResult($sql, $args = array())
    {
        $sql = $this->appendLimit($sql);

        if ($result = $this->query($sql, $args)) {
            if ($row = $this->fetchObject($result)) {
                return $row;
            }
        }

        return false;
    }

    /**
     * Perform a query on the database and return the first field value only.
     *
     * It adds the `LIMIT 1` clause if the query has no record limit
     * This will be useful for `COUNT()`, `MAX()`, `MIN()` queries
     *
     * @param string $sql The SQL query string
     * @param array $args The array of placeholders and their values
     *
     *      array(
     *          ':placeholder1' => $value1,
     *          ':placeholder2' => $value2
     *      )
     *
     * @return mixed The value of the first field
     */
    public function fetchColumn($sql, $args = array())
    {
        $sql = $this->appendLimit($sql);

        if ($result = $this->query($sql, $args)) {
            return $result->fetchColumn();
        }

        return false;
    }

    /**
     * Gets the number of rows in a result
     * @param  \PDOStatement $stmt
     * @return int Returns the number of rows in the result set.
     */
    public function getNumRows($stmt)
    {
        return $stmt->rowCount();
    }

    /**
     * Perform a count query on the database and return the count
     *
     * @param string        $arg1 The SQL query string or table name
     * @param string|array  $arg2 The field name to count on
     *   or the array of placeholders and their values if the first argument is SQL
     *
     *      array(
     *          ':placeholder1' => $value1,
     *          ':placeholder2' => $value2
     *      )
     *
     * @param string|null   $arg3 The field alias if the first argument is table name
     *   or the second argument is field name
     *
     * @return int|QueryBuilder The result count
     */
    public function getCount($arg1, $arg2 = null, $arg3 = null)
    {
        QueryBuilder::clearBindValues();

        if ($arg1 && QueryBuilder::validateName($arg1)) {
            $table = $arg1;
            $alias = 'count';

            $qb = new QueryBuilder($table);

            if ($arg3 && QueryBuilder::validateName($arg3)) {
                $alias = $arg3;
            }

            if ($arg2 && QueryBuilder::validateName($arg2)) {
                $field = $arg2;
                $qb->count($field, $alias);
            } else {
                $qb->count('*', 'count');
            }

            return $qb;
        } else {
            $sql = $arg1;
            $args = $arg2;

            if ($result = $this->fetchColumn($sql, $args)) {
                return $result;
            }
        }

        return 0;
    }

    /**
     * Returns the auto generated id used in the last query
     * @return int The value of the `AUTO_INCREMENT` field that was updated by the previous query;
     *  `0` if there was no previous query on the connection or if the query did not update an `AUTO_INCREMENT` value.
     */
    public function getInsertId()
    {
        return $this->connection ? $this->connection->lastInsertId() : 0;
    }

    /**
     * Returns a string description of the last error
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the error code for the most recent query function call
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Closes a previously opened database connection
     * @return void
     */
    public function close()
    {
        $this->connection = null;
    }

    /**
     * Get the full table name with prefix
     * @param string $table The table name with or without prefix
     * @return string The table name with prefix
     */
    public function getTable($table)
    {
        $prefix = $this->getPrefix();

        if (empty($prefix)) {
            return $table;
        }

        if ($prefix == substr($table, 0, strlen($prefix))) {
            return $table;
        }

        return $prefix . $table;
    }

    /**
     * Check the table has slug field
     *
     * @param string  $table    The table name without prefix
     * @param boolean $useSlug  True to include the slug field or False to not exclude it
     * @return boolean true or false
     */
    public function hasSlug($table, $useSlug = true)
    {
        if ($useSlug == false) {
            return false;
        }

        return $this->schemaManager->hasSlug($table);
    }

    /**
     * Check the table has timestamp fields
     *
     * @param string  $table    The table name without prefix
     * @return boolean true or false
     */
    public function hasTimestamps($table)
    {
        return $this->schemaManager->hasTimestamps($table);
    }

    /**
     * Build the SQL expression like SUM, MAX, AVG, etc
     *
     * @param string $field The field name
     * @param mixed $value The value for the field
     * @param string $exp The SQL expression
     * @return array The condition array, for example
     *
     *     array(
     *       'value'  => $value,
     *       'exp >=' => $exp,
     *       'field   => $field
     *     )
     *
     */
    public function exp($field, $value, $exp = '')
    {
        if ($exp) {
            $field = strtoupper($field) . '(' . $value . ')';
        } else {
            $field = '';
        }

        return array(
            'value' => $value,
            'exp' => $exp,
            'field' => $field
        );
    }

    /**
     * Append LIMIT clause to the SQL statement
     * @param string $sql The SQL statement
     * @return string
     */
    private function appendLimit($sql)
    {
        if (! preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql)) {
            $sql .= ' LIMIT 1';
        }

        return $sql;
    }
}
