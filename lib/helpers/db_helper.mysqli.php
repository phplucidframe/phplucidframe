<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for the database layer. Basic functioning of the database system.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Core\SchemaManager;

/**
 * @internal
 * Return the database configuration of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_config($namespace = 'default')
{
    if (!isset($GLOBALS['lc_databases'][$namespace])) {
        die('Database configuration error for '.$namespace.'!');
    }
    return $GLOBALS['lc_databases'][$namespace];
}
/**
 * Return the database engine of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_engine($namespace = 'default')
{
    $conf = db_config($namespace);

    if ($conf['engine'] === 'mysql') {
        $conf['engine'] = 'mysqli';
    }

    return $conf['engine'];
}
/**
 * Return the database host name of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_host($namespace = 'default')
{
    $conf = db_config($namespace);
    return $conf['host'];
}
/**
 * Return the database name of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_name($namespace = 'default')
{
    $conf = db_config($namespace);
    if (!isset($conf['database'])) {
        die('Database name is not set.');
    }
    return $conf['database'];
}
/**
 * Return the database user name of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_user($namespace = 'default')
{
    $conf = db_config($namespace);
    if (!isset($conf['username'])) {
        die('Database username is not set.');
    }
    return $conf['username'];
}
/**
 * Return the database table prefix of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_prefix($namespace = 'default')
{
    $conf = $GLOBALS['lc_databases'][$namespace];
    return isset($conf['prefix']) ? $conf['prefix'] : '';
}
/**
 * Return the database collation of the given namespace
 * @param string $namespace Namespace of the configuration to read from
 */
function db_collation($namespace = 'default')
{
    $conf = db_config($namespace);
    return isset($conf['collation']) ? $conf['collation'] : '';
}
/**
 * @internal
 * Check and get the database configuration settings
 * @param string $namespace Namespace of the configuration to read from
 */
function db_prerequisite($namespace = 'default')
{
    if (db_host($namespace) && db_user($namespace) && db_name($namespace)) {
        return db_config($namespace);
    } else {
        $error = new stdClass();
        $error->message = 'Required to configure <code class="inline">db</code> in "/inc/parameters/'._cfg('env').'.php".';
        $error->message = array(function_exists('_t') ? _t($error->message) : $error->message);
        $error->type    = 'sitewide-message error';
        include( _i('inc/tpl/site.error.php') );
        exit;
    }
}
/**
 * Establish a new database connection to the MySQL server
 * @param string $namespace Namespace of the configuration.
 */
function db_connect($namespace = 'default')
{
    global $_conn;
    global $_DB;
    $conf = db_config($namespace);
    # Connection
    $_conn = mysqli_connect($conf['host'], $conf['username'], $conf['password']);
    if (!$_conn) {
        die('Not connected mysqli!');
    }
    # Force MySQL to use the UTF-8 character set. Also set the collation, if a certain one has been set;
    # otherwise, MySQL defaults to 'utf8_general_ci' # for UTF-8.
    if (!db_setCharset('utf8')) {
        printf("Error loading character set utf8: %s", mysqli_error($_conn));
    }
    if (db_collation()) {
        db_query('SET NAMES utf8 COLLATE ' . db_collation());
    }
    # Select DB
    if (!mysqli_select_db($_conn, $conf['database'])) {
        die('Can\'t use  : ' . $conf['database'] .' - '. mysqli_error($_conn));
    }

    $_DB = new stdClass();
    $_DB->name = $conf['database'];
    $_DB->namespace = $namespace;

    # Load the schema of the currently connected database
    $schema = _schema($namespace, true);
    $_DB->schemaManager = new SchemaManager($schema);
    if (!$_DB->schemaManager->isLoaded()) {
        $_DB->schemaManager->build($namespace);
    }
}
/**
 * Switch to the given database from the currently active database
 * @param string $namespace Namespace of the configuration to read from
 * @return void
 */
function db_switch($namespace = 'default')
{
    global $_conn;
    db_close();
    db_connect($namespace);
}
/**
 * Sets the default client character set
 *
 * @param  string $charset The charset to be set as default.
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function db_setCharset($charset)
{
    global $_conn;
    return mysqli_set_charset($_conn, $charset);
}
/**
 * Closes a previously opened database connection
 * @return boolean Returns TRUE on success or FALSE on failure.
 */
function db_close()
{
    global $_conn;
    return $_conn ? mysqli_close($_conn) : true;
}
/**
 * Make the generated query returned from the query executing functions
 * such as db_query, db_update, db_delete, etc. without executing the query
 * especially for debugging and testing. Call `db_prq(true)` before and `db_prq(false)` after.
 * `db_queryStr()` is same purpose but after executing the query.
 *
 * @param bool $enable Enable to return the query built; defaults to `true`.
 */
function db_prq($enable = true)
{
    _g('db_printQuery', $enable);
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
 * @return boolean Returns TRUE on success or FALSE on failure
 */
function db_query($sql, $args = array())
{
    global $_conn;
    global $db_builtQueries;

    if (count($args)) {
        foreach ($args as $key => $value) {
            if (strpos($key, ':') === false) {
                $key = ':'.$key;
            }
            if (is_array($value)) {
                $value = array_map('db_escapeStringMulti', $value);
                $value = implode(',', $value);
                $regex = '/'.$key.'\b/i';
                $sql = preg_replace($regex, $value, $sql);
            } else {
                $regex = '/'.$key.'\b/i';
                $sql = preg_replace($regex, db_escapeString($value), $sql);
            }
        }
    }

    $db_builtQueries[] = $sql;

    if (_g('db_printQuery')) {
        return $sql;
    }

    if ($result = mysqli_query($_conn, $sql)) {
        return $result;
    } else {
        echo 'Invalid query: ' . db_error();
        return false;
    }
}
/**
 * Get the last executed SQL string or one of the executed SQL strings by prividing the index
 *
 * @param  int The index number of the query returned; if not given, the last query is returned
 * @return string Return the built and executed SQL string
 */
function db_queryStr()
{
    global $db_builtQueries;
    $arg = func_get_args();
    $index = (count($arg) == 0) ? count($db_builtQueries)-1 : 0;
    return (isset($db_builtQueries[$index])) ? $db_builtQueries[$index] : '';
}
/**
 * Escapes special characters in a string for use in a SQL statement,
 * taking into account the current charset of the connection
 *
 * @param  string $str An escaped string
 * @return string
 */
function db_escapeString($str)
{
    global $_conn;
    if (get_magic_quotes_gpc()) {
        return mysqli_real_escape_string($_conn, stripslashes($str));
    } else {
        return mysqli_real_escape_string($_conn, $str);
    }
}
/**
 * @internal
 *
 * Quote and return the value if it is string; otherwise return as it is
 * This is used for array_map()
 */
function db_escapeStringMulti($val)
{
    $val = db_escapeString($val);
    return is_numeric($val) ? $val : '"'.$val.'"';
}
/**
 * Returns a string description of the last error
 * @return string
 */
function db_error()
{
    global $_conn;
    return mysqli_error($_conn);
}
/**
 * Returns the error code for the most recent MySQLi function call
 * @return int
 */
function db_errorNo()
{
    global $_conn;
    return mysqli_errno($_conn);
}
/**
 * Gets the number of rows in a result
 * @param  resource $result MySQLi result resource
 * @return int Returns the number of rows in the result set.
 */
function db_numRows($result)
{
    return mysqli_num_rows($result);
}
/**
 * Fetch a result row as an associative, a numeric array, or both
 * @param  resource $result MySQLi result resource
 * @return array An array that corresponds to the fetched row or
 *   NULL if there are no more rows for the resultset represented by the result parameter.
 */
function db_fetchArray($result)
{
    return mysqli_fetch_array($result);
}
/**
 * Fetch a result row as an associative array
 * @param  resource $result MySQLi result resource
 * @return array An associative array that corresponds to the fetched row or NULL if there are no more rows.
 */
function db_fetchAssoc($result)
{
    return mysqli_fetch_assoc($result);
}
/**
 * Returns the current row of a result set as an object
 * @param  resource $result MySQLi result resource
 * @return object An object that corresponds to the fetched row or NULL if there are no more rows in resultset.
 */
function db_fetchObject($result)
{
    return mysqli_fetch_object($result);
}
/**
 * Returns the auto generated id used in the last query
 * @return int The value of the `AUTO_INCREMENT` field that was updated by the previous query;
 *  `0` if there was no previous query on the connection or if the query did not update an `AUTO_INCREMENT` value.
 */
function db_insertId()
{
    global $_conn;
    return mysqli_insert_id($_conn);
}
/**
 * Returns the generated slug used in the last query
 * @return string The last inserted slug
 */
function db_insertSlug()
{
    return session_get('lastInsertSlug');
}
/**
 * Initialize a query builder to perform a SELECT query on the database
 *
 * @param string $table The table name
 * @param string $alias The optional table alias
 *
 * @return object QueryBuilder
 */
function db_select($table, $alias = null)
{
    return new QueryBuilder($table, $alias);
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
 * @return int|object The result count or QueryBuilder
 */
function db_count($arg1, $arg2 = null, $arg3 = null)
{
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
        if ($result = db_fetch($sql, $args)) {
            return $result;
        }
    }

    return 0;
}
/**
 * Initialize a query builder to perform a MAX query on the database
 *
 * @param string $table The table name
 * @param string $field The field name to find max
 * @param string $alias The optional field alias; defaults to "max"
 *
 * @return object QueryBuilder
 */
function db_max($table, $field, $alias = null)
{
    $qb = new QueryBuilder($table);
    return $qb->max($field, $alias ? $alias : 'max');
}
/**
 * Initialize a query builder to perform a MIN query on the database
 *
 * @param string $table The table name
 * @param string $field The field name to find min
 * @param string $alias The optional field alias; defaults to "min"
 *
 * @return object QueryBuilder
 */
function db_min($table, $field, $alias = null)
{
    $qb = new QueryBuilder($table);
    return $qb->min($field, $alias ? $alias : 'min');
}
/**
 * Initialize a query builder to perform a SUM query on the database
 *
 * @param string $table The table name
 * @param string $field The field name to find sum
 * @param string $alias The optional field alias; defaults to "sum"
 *
 * @return object QueryBuilder
 */
function db_sum($table, $field, $alias = null)
{
    $qb = new QueryBuilder($table);
    return $qb->sum($field, $alias ? $alias : 'sum');
}
/**
 * Initialize a query builder to perform an AVG query on the database
 *
 * @param string $table The table name
 * @param string $field The field name to find average
 * @param string $alias The optional field alias; defaults to "avg"
 *
 * @return object QueryBuilder
 */
function db_avg($table, $field, $alias = null)
{
    $qb = new QueryBuilder($table);
    return $qb->avg($field, $alias ? $alias : 'avg');
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
function db_fetch($sql, $args = array())
{
    if (! preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql)) {
        $sql .= ' LIMIT 1';
    }
    if ($result = db_query($sql, $args)) {
        if ($row = db_fetchArray($result)) {
            return $row[0];
        }
    }
    return false;
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
 * @return object The result object
 */
function db_fetchResult($sql, $args = array())
{
    if (! preg_match('/LIMIT\s+[0-9]{1,}\b/i', $sql)) {
        $sql .= ' LIMIT 1';
    }
    if ($result = db_query($sql, $args)) {
        if ($row = db_fetchObject($result)) {
            return $row;
        }
    }
    return false;
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
function db_extract($sql, $args = array(), $resultType = LC_FETCH_OBJECT)
{
    if (is_numeric($args)) {
        if (in_array($args, array(LC_FETCH_OBJECT, LC_FETCH_ASSOC, LC_FETCH_ARRAY))) {
            $resultType = $args;
        }
        $args = array();
    }
    $data = array();
    if ($result = db_query($sql, $args)) {
        while ($row = db_fetchAssoc($result)) {
            if (count($row) == 2 && array_keys($row) === array('key', 'value')) {
                $data[$row['key']] = $row['value'];
            } else {
                if ($resultType == LC_FETCH_ARRAY) {
                    $data[] = array_values($row);
                } elseif ($resultType == LC_FETCH_OBJECT) {
                    $data[] = (object) $row;
                } else {
                    $data[] = $row;
                }
            }
        }
    }
    return count($data) ? $data : false;
}

if (!function_exists('db_insert')) {
    /**
     * Handy MYSQL insert operation
     *
     * @param string $table The table name without prefix
     * @param array $data The array of data field names and values
     *
     *      array(
     *          'fieldNameToSlug' => $valueToSlug, # if $lc_useDBAutoFields is enabled
     *          'fieldName1' => $fieldValue1,
     *          'fieldName2' => $fieldValue2
     *      )
     *
     * @param boolean $useSlug True to include the slug field or False to not exclude it
     * @return mixed Returns inserted id on success or FALSE on failure
     */
    function db_insert($table, $data = array(), $useSlug = true)
    {
        if (count($data) == 0) {
            return;
        }

        global $_DB;
        global $_conn;
        global $lc_useDBAutoFields;

        $table = ltrim($table, db_prefix());
        $table = db_prefix().$table;

        # Invoke the hook db_insert_[table_name] if any
        $hook = 'db_insert_' . strtolower($table);
        if (function_exists($hook)) {
            return call_user_func_array($hook, array($table, $data, $useSlug));
        }

        # if slug is already provided in the data array, use it
        if (array_key_exists('slug', $data)) {
            $slug = db_escapeString($data['slug']);
            $slug = _slug($slug);
            $data['slug'] = $slug;
            session_set('lastInsertSlug', $slug);
            $useSlug = false;
        }

        $fields = array_keys($data);
        $data   = array_values($data);

        # $lc_useDBAutoFields and $useSlug are still used for backward compatibility
        # TODO: $lc_useDBAutoFields and $useSlug to be removed in future versions
        if (($_DB->schemaManager->hasSlug($table) && $useSlug) || ($lc_useDBAutoFields && $useSlug)) {
            $fields[] = 'slug';
        }

        if ($_DB->schemaManager->hasTimestamps($table) || $lc_useDBAutoFields) {
            $fields[] = 'created';
            $fields[] = 'updated';
        }

        $sqlFields = implode(', ', $fields);
        $values = array();
        $i = 0;

        # escape the data
        foreach ($data as $val) {
            if ($i == 0 && $useSlug) {
                $slug = db_escapeString($val);
            }
            if (is_null($val)) {
                $values[] = 'NULL';
            } else {
                $values[] = '"'.db_escapeString($val).'"';
            }
            $i++;
        }

        # $lc_useDBAutoFields and $useSlug are still used for backward compatibility
        # TODO: $lc_useDBAutoFields and $useSlug to be removed in future versions
        if (($_DB->schemaManager->hasSlug($table) && $useSlug) || ($lc_useDBAutoFields && $useSlug)) {
            $slug = _slug($slug, $table);
            session_set('lastInsertSlug', $slug);
            $values[] = '"'.$slug.'"';
        }

        if ($_DB->schemaManager->hasTimestamps($table) || $lc_useDBAutoFields) {
            $values[] = '"'.date('Y-m-d H:i:s').'"';
            $values[] = '"'.date('Y-m-d H:i:s').'"';
        }

        $sqlValues = implode(', ', $values);

        $sql = 'INSERT INTO '.$table.' ('.$sqlFields.')
                VALUES ( '.$sqlValues.' )';
        return db_query($sql) ? db_insertId() : false;
    }
}

if (!function_exists('db_update')) {
    /**
     * Handy MYSQL update operation
     *
     * @param string $table The table name without prefix
     * @param array $data The array of data field names and values
     *   The first field/value pair will be used as condition if you did not provide the fourth argument
     *
     *     array(
     *       'conditionField'  => $conditionFieldValue, <===
     *       'fieldNameToSlug' => $valueToSlug,  <=== if $lc_useDBAutoFields is enabled
     *       'fieldName1'      => $value1,
     *       'fieldName2'      => $value2
     *     )
     *
     * @param boolean $useSlug TRUE to include the slug field or FALSE to not exclude it
     *   The fourth argument can be provided here if you want to omit this.
     * @param array|string $condition The condition for the UPDATE query. If you provide this,
     *   the first field of `$data` will not be built for condition
     *
     * ### Example
     *
     *     array(
     *       'fieldName1' => $value1,
     *       'fieldName2' => $value2
     *     )
     *
     * OR
     *
     *     db_or(array(
     *       'fieldName1' => $value1,
     *       'fieldName2' => $value2
     *     ))
     *
     * @return boolean Returns TRUE on success or FALSE on failure
     */
    function db_update($table, $data = array(), $useSlug = true, $condition = null)
    {
        if (count($data) == 0) {
            return;
        }

        global $_DB;
        global $_conn;
        global $lc_useDBAutoFields;

        if (func_num_args() === 3 && (gettype($useSlug) === 'string' || is_array($useSlug))) {
            $condition = $useSlug;
            $useSlug = true;
        }

        $table  = ltrim($table, db_prefix());
        $table  = db_prefix().$table;

        # Invoke the hook db_update_[table_name] if any
        $hook = 'db_update_' . strtolower($table);
        if (function_exists($hook)) {
            return call_user_func_array($hook, array($table, $data, $useSlug, $condition));
        }

        # if slug is already provided in the data array, use it
        if (array_key_exists('slug', $data)) {
            $slug = db_escapeString($data['slug']);
            $slug = _slug($slug);
            $data['slug'] = $slug;
            session_set('lastInsertSlug', $slug);
            $useSlug = false;
        }

        $fields     = array();
        $slug       = '';
        $cond       = '';
        $notCond    = '';
        $i          = 0;
        $slugIndex  = 1;
        if ($condition) {
            $slugIndex = 0;
        }
        foreach ($data as $field => $value) {
            if ($i === 0 && !$condition) {
                # $data[0] is for PK condition, but only if $condition is not provided
                $cond = array($field => db_escapeString($value)); # for PK condition
                $i++;
                continue;
            }

            if (is_null($value)) {
                $fields[] = QueryBuilder::quote($field) . ' = NULL';
            } else {
                $fields[] = QueryBuilder::quote($field) . ' = "' . db_escapeString($value) . '"';
            }

            if ($i === $slugIndex && $useSlug === true) {
                # $data[1] is slug
                $slug = db_escapeString($value);
            }
            $i++;
        }

        # must have condition
        # this prevents unexpected update happened to all records
        if ($cond || $condition) {
            if ($cond && is_array($cond)) {
                $cond = db_condition($cond);
            } elseif ($condition && is_array($condition)) {
                $cond = db_condition($condition);
            } elseif ($condition && is_string($condition)) {
                $cond = $condition;
            }

            if (empty($cond)) {
                return false;
            }
            $notCond = 'NOT ( ' . $cond . ' )';

            # $lc_useDBAutoFields and $useSlug are still used for backward compatibility
            # TODO: $lc_useDBAutoFields and $useSlug to be removed in future versions
            if (($_DB->schemaManager->hasSlug($table) && $useSlug) || ($lc_useDBAutoFields && $useSlug)) {
                $slug = _slug($slug, $table, $notCond);
                session_set('lastInsertSlug', $slug);
                $fields[] = '`slug` = "'.$slug.'"';
            }

            if ($_DB->schemaManager->hasTimestamps($table) || $lc_useDBAutoFields) {
                $fields[] = '`updated` = "' . date('Y-m-d H:i:s') . '"';
            }

            $fields = implode(', ', $fields);

            $sql = 'UPDATE ' . QueryBuilder::quote($table) . '
                    SET ' . $fields . ' WHERE ' . $cond;
            return db_query($sql);
        } else {
            return false;
        }
    }
}

if (!function_exists('db_delete')) {
    /**
     * Handy MYSQL delete operation for single record.
     * It checks FK delete RESTRICT constraint, then SET deleted if it cannot be deleted
     *
     * @param string $table Table name without prefix
     * @param string|array $condition The array of condition for delete - field names and values, for example
     *
     *     array(
     *       'fieldName1'    => $value1,
     *       'fieldName2 >=' => $value2,
     *       'fieldName3     => NULL
     *     )
     *
     *   The built condition string, for example,
     *
     *     db_or(array(
     *       'fieldName1'    => $value1,
     *       'fieldName2 >=' => $value2,
     *       'fieldName3     => NULL
     *     ))
     *
     * @return boolean Returns TRUE on success or FALSE on failure
     */
    function db_delete($table, $condition = null)
    {
        $table = ltrim($table, db_prefix());
        $table = db_prefix().$table;

        # Invoke the hook db_delete_[table_name] if any
        $hook = 'db_delete_' . strtolower($table);
        if (function_exists($hook)) {
            return call_user_func_array($hook, array($table, $cond));
        }

        if (is_array($condition)) {
            $condition = db_condition($condition);
        }
        if ($condition) {
            $condition = ' WHERE '.$condition;
        }

        $sql = 'DELETE FROM ' . QueryBuilder::quote($table) . $condition . ' LIMIT 1';
        if (_g('db_printQuery')) {
            return $sql;
        }

        ob_start(); # to capture error return
        db_query($sql);
        $return = ob_get_clean();
        if ($return) {
            # If there is FK delete RESTRICT constraint
            if (db_errorNo() == 1451) {
                # $lc_useDBAutoFields is still used for backward compatibility
                # TODO: $lc_useDBAutoFields to be removed in future versions
                if ($_DB->schemaManager->hasTimestamps($table) || $lc_useDBAutoFields) {
                    $sql = 'UPDATE '. QueryBuilder::quote($table) . '
                            SET `deleted` = "'.date('Y-m-d H:i:s').'" '.$condition.'
                            LIMIT 1';
                    return db_query($sql);
                } else {
                    return false;
                }
            } else {
                echo $return;
                return false;
            }
        }
        return (db_errorNo() == 0) ? true : false;
    }
}
if (!function_exists('db_delete_multi')) {
    /**
     * Handy MYSQL delete operation for multiple records
     *
     * @param string $table Table name without prefix
     * @param string|array $condition The array of condition for delete - field names and values, for example
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL
     *    )
     *
     *   The built condition string, for example,
     *
     *    db_or(array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL
     *    ))
     *
     * @return boolean Returns TRUE on success or FALSE on failure
     */
    function db_delete_multi($table, $condition = null)
    {
        $table = ltrim($table, db_prefix());
        $table = db_prefix().$table;

        # Invoke the hook db_delete_[table_name] if any
        $hook = 'db_delete_multi_' . strtolower($table);
        if (function_exists($hook)) {
            return call_user_func_array($hook, array($table, $cond));
        }

        if (is_array($condition)) {
            $condition = db_condition($condition);
        }
        if ($condition) {
            $condition = ' WHERE '.$condition;
        }

        $sql = 'DELETE FROM ' . QueryBuilder::quote($table) . $condition;
        if (_g('db_printQuery')) {
            return $sql;
        }

        ob_start(); # to capture error return
        db_query($sql);
        $return = ob_get_clean();

        if ($return && db_errorNo() > 0) {
            # if there is any error
            return false;
        }
        return (db_errorNo() == 0) ? true : false;
    }
}
/**
 * @internal
 * Build the SQL WHERE clause from the various condition arrays
 *
 * @param array $cond The condition array, for example
 *
 *    array(
 *      'fieldName1'    => $value1,
 *      'fieldName2 >=' => $value2,
 *      'fieldName3     => NULL
 *    )
 *
 * @param string $type The condition type "AND" or "OR"; Default is "AND"
 *
 * @return string The built condition WHERE clause
 */
function db_condition($cond = array(), $type = 'AND')
{
    return QueryBuilder::buildCondition($cond, $type);
}

/**
 * Build the SQL WHERE clause AND condition from the various condition arrays
 * Alias of `db_conditionAND()`
 *
 * @param array $cond [$cond1,$cond2,$cond3,...] The condition array(s), for example
 *
 *     array(
 *       'fieldName1'    => $value1,
 *       'fieldName2 >=' => $value2,
 *       'fieldName3     => NULL
 *     )
 *
 * ### Operators allowed in condition array
 *     >, >=, <, <=, !=, between, nbetween, like, like%%, like%~, like~%, nlike, nlike%%, nlike%~, nlike~%
 *
 * @return string The built condition WHERE clause
 */
function db_and($cond = array())
{
    $conditions = func_get_args();
    $builtCond = array();
    foreach ($conditions as $c) {
        $builtCond[] = db_condition($c, 'AND');
    }
    return implode(' AND ', $builtCond);
}

/**
 * Build the SQL WHERE clause OR condition from the various condition arrays
 * Alias of `db_conditionOR()`
 *
 * @param array $cond [,$cond2,$cond3,...] The condition array(s), for example
 *
 *     array(
 *       'fieldName1'    => $value1,
 *       'fieldName2 >=' => $value2,
 *       'fieldName3     => NULL
 *     )
 *
 * ### Operators allowed in condition array
 *     >, >=, <, <=, !=, between, nbetween, like, like%%, like%~, like~%, nlike, nlike%%, nlike%~, nlike~%
 *
 * @return string The built condition WHERE clause
 */
function db_or($cond = array())
{
    $conditions = func_get_args();
    $builtCond = array();
    foreach ($conditions as $c) {
        $builtCond[] = db_condition($c, 'OR');
    }
    return implode(' OR ', $builtCond);
}
/**
 * @internal
 *
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
function db_exp($field, $value, $exp = '')
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
