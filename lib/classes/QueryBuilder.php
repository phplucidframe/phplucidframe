<?php
/**
 * This file is part of the PHPLucidFrame library.
 * QueryBuilder class is responsible to dynamically create SQL queries.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.9.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

class QueryBuilder
{
    /** @var string The table name */
    protected $table;
    /** @var string The alias for the table */
    protected $alias;
    /** @var array Collections of tables to join */
    protected $joins;
    /** @var array Collections of fields to select */
    protected $fields;
    /** @var array Collection of conditions */
    protected $where;
    /** @var array Collection of fields to order */
    protected $orderBy;
    /** @var array Collection of fields to group by */
    protected $groupBy;
    /** @var array Collection of fields for having conditions */
    protected $having;
    /** @var int The offset for LIMIT */
    protected $offset;
    /** @var int The row count for LIMIT */
    protected $limit;
    /** @var string The built SQL */
    protected $sql;
    /** @var array Collection of aggregates */
    protected $aggregates = array();
    /** @var resource The MySQL result resource */
    private $result;
    /** @var string AND/OR */
    private $whereType = 'AND';
    /** @var array Collection of SQL operators allowed */
    private static $operators = array(
        '=', '>=', '<=', '>', '<', '!=', '<>',
        'between', 'nbetween',
        'like', 'like%%', 'like%~', 'like~%',
        'nlike', 'nlike%%', 'nlike%~', 'nlike~%'
    );
    /** @var array Collection of LIKE expressions */
    private static $likes = array(
        'like'    => 'LIKE "%:likeValue%"',
        'like%%'  => 'LIKE "%:likeValue%"',
        'like%~'  => 'LIKE "%:likeValue"',
        'like~%'  => 'LIKE ":likeValue%"',
        'nlike'   => 'NOT LIKE "%:likeValue%"',
        'nlike%%' => 'NOT LIKE "%:likeValue%"',
        'nlike%~' => 'NOT LIKE "%:likeValue"',
        'nlike~%' => 'NOT LIKE ":likeValue%"',
    );
    /** @var array Collection of join types allowed */
    private static $joinTypes = array('INNER', 'LEFT', 'RIGHT', 'OUTER');
    /** @var array Collection of SQL functions allowed */
    private static $functions = array(
        'ABS', 'ADDDATE', 'ADDTIME', 'AVG',
        'CONCAT', 'COUNT', 'CUR_DATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP',
        'DATE', 'DATE_ADD', 'DATE_FORMAT', 'DATE_SUB', 'DATEDIFF',
        'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR',
        'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD', 'LTRIM', 'MAX', 'MIN', 'MOD', 'MONTH', 'MONTHNAME', 'NOW',
        'RIGHT', 'RPAD', 'RTRIM', 'SIZE', 'SQRT', 'SUBDATE', 'SUBSTR', 'SUBSTRING', 'SUBTIME', 'SUM',
        'TRIM', 'TIME', 'TIMEDIFF', 'TIMESTAMP', 'TIMESTAMPADD', 'TIMESTAMPDIFF',
        'UPPER', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR'
    );

    /**
     * Constructor
     *
     * @param string $table The base table to select from
     * @param string $alias The alias for the table
     * @return void
     */
    public function __construct($table = null, $alias = null)
    {
        $this->from($table, $alias);
    }

    /**
     * Table to SELECT
     *
     * @param string $table The table name
     * @param string $alias The table alias
     *
     * @return object QueryBuilder
     */
    public function from($table, $alias = null)
    {
        if (self::validateName($table) === false) {
            return $this;
        }

        if ($this->alias && self::validateName($alias) === false) {
            $alias = $table;
        }

        if ($alias === null) {
            $alias = $table;
        }

        $this->table = db_prefix() . $table;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Add fields to SELECT
     *
     * @param string $alias The table alias
     * @param array  $fields Array of field names
     *
     * @return object QueryBuilder
     */
    public function fields($alias, array $fields = array())
    {
        if (!$fields || count($fields) === 0) {
            $fields = array('*');
        }
        $this->fields[$alias] = $fields;

        return $this;
    }
    /**
     * Add field to SELECT
     *
     * @param string $field  The field name
     * @param array  $alias  The alias for the field name
     *
     * @return object QueryBuilder
     */
    public function field($field, $alias = null)
    {
        $this->fields['*'][] = $alias ? array($field, $alias) : $field;

        return $this;
    }

    /**
     * Prepare field name ready for SELECT
     *
     * @param string $table The table alias
     * @param string $field The field name or array of field name and field alias
     *
     * @return string|null
     */
    private function prepareField($table, $field)
    {
        if ($table === '*') {
            return is_array($field) ? $field[0] . ' ' . $field[1] : $field;
        }

        if ($field === '*') {
            return self::quote($table) . '.' . $field;
        } else {
            if (is_array($field)) {
                if (count($field) != 2) {
                    return null;
                }
                # field with alias
                $f = self::quote($field[0]);
                if (substr($f, 0, 1) !== '`') {
                    return $f . ' ' . $field[1];
                } else {
                    return self::quote($table) . '.' . $f . ' ' . self::quote($field[1]);
                }
            } else {
                # field without alias
                $f = self::quote($field);
                if (substr($f, 0, 1) !== '`') {
                    return $f;
                } else {
                    return self::quote($table) . '.' . $f;
                }
            }
        }
    }

    /**
     * Add a table to join
     *
     * @param string $table The table name
     * @param string $alias The alias for the table
     * @param string $condition The join condition e.g., t1.pk = t2.fk
     * @param string $type  INNER, LEFT, RIGHT or OUTER
     *
     * @return object QueryBuilder
     */
    public function join($table, $alias, $condition, $type = 'INNER')
    {
        if (self::validateName($table) === false || self::validateName($alias) === false) {
            return $this;
        }

        $type = strtoupper($type);

        if (!in_array($type, self::$joinTypes)) {
            $type = 'INNER';
        }

        $this->joins[] = array(
            'table'     => db_prefix() . $table,
            'alias'     => $alias === null ? $table : $alias,
            'condition' => $condition,
            'type'      => $type
        );

        return $this;
    }

    /**
     * Add a table to perform left join
     *
     * @param string $table The table name
     * @param string $alias The alias for the table
     * @param string $condition The join condition e.g., t1.pk = t2.fk
     *
     * @return object QueryBuilder
     */
    public function leftJoin($table, $alias, $condition)
    {
        $this->join($table, $alias, $condition, 'left');

        return $this;
    }

    /**
     * Add a table to perform right join
     *
     * @param string $table The table name
     * @param string $alias The alias for the table
     * @param string $condition The join condition e.g., t1.pk = t2.fk
     *
     * @return object QueryBuilder
     */
    public function rightJoin($table, $alias, $condition)
    {
        $this->join($table, $alias, $condition, 'right');

        return $this;
    }

    /**
     * Add a table to perform outer join
     *
     * @param string $table The table name
     * @param string $alias The alias for the table
     * @param string $condition The join condition e.g., t1.pk = t2.fk
     *
     * @return object QueryBuilder
     */
    public function outerJoin($table, $alias, $condition)
    {
        $this->join($table, $alias, $condition, 'outer');

        return $this;
    }

    /**
     * Alias of `andWhere()`
     */
    public function where($condition = null)
    {
        if ($condition === null) {
            $this->where['AND'] = array();
        } else {
            $this->where = self::buildCondition($condition, 'AND');
        }
        $this->whereType = 'AND';

        return $this;
    }

    /**
     * Create WHERE ... AND condition
     *
     * @param array|null $condition The array of conditions
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL
     *    )
     *
     *  OR
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL,
     *      db_or(array(
     *          'fieldName4'    => array(1, 2, 3)
     *          'fieldName4 <'  => 10
     *      ))
     *    )
     *
     * @return object QueryBuilder
     */
    public function andWhere($condition = null)
    {
        if ($condition === null) {
            $this->where['AND'] = array();
        } else {
            $this->where = $this->where($condition);
        }
        $this->whereType = 'AND';

        return $this;
    }

    /**
     * Create WHERE ... OR condition
     *
     * @param array|null $condition The array of conditions
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL
     *    )
     *
     *  OR
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL,
     *      db_and(array(
     *          'fieldName4'    => array(1, 2, 3)
     *          'fieldName4 <'  => 10
     *      ))
     *    )
     *
     * @return object QueryBuilder
     */
    public function orWhere($condition = null)
    {
        if ($condition === null) {
            $this->where['OR'] = array();
        } else {
            $this->where = self::buildCondition($condition, 'OR');
        }
        $this->whereType = 'OR';

        return $this;
    }

    /**
     * Create simple WHERE condition with field/value assignment
     *
     * @param string $field The field name
     * @param mixed  $value The value to check against the field name
     *
     *    $qb = db_select('post', 'p')
     *        ->orWhere()
     *        ->condition('catId', 1)
     *        ->condition('catId', 2);
     *
     * @return object QueryBuilder
     */
    public function condition($field, $value)
    {
        if (isset($this->where[$this->whereType][$field])) {
            $field .= uniqid('__'.trim(__METHOD__, 'LucidFrame\Core').'__');
        }
        $this->where[$this->whereType][$field] = $value;

        return $this;
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $field The field name to sort
     * @param string $sort  ASC or DESC
     *
     * @return object QueryBuilder
     */
    public function orderBy($field, $sort = 'ASC')
    {
        $sort = strtoupper($sort);
        if (!in_array($sort, array('ASC', 'DESC'))) {
            $sort = 'ASC';
        }
        $this->orderBy[$field] = $sort;

        return $this;
    }

    /**
     * Add GROUP BY clause
     *
     * @param string $field The field name
     *
     * @return object QueryBuilder
     */
    public function groupBy($field)
    {
        $this->groupBy[] = $field;
        $this->groupBy = array_unique($this->groupBy);

        return $this;
    }

    /**
     * Create HAVING ... condition
     *
     * @param array $condition The array of conditions
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL
     *    )
     *
     *  OR
     *
     *    array(
     *      'fieldName1'    => $value1,
     *      'fieldName2 >=' => $value2,
     *      'fieldName3     => NULL,
     *      db_or(array(
     *          'fieldName4'    => array(1, 2, 3)
     *          'fieldName4 <'  => 10
     *      ))
     *    )
     *
     * @return object QueryBuilder
     */
    public function having(array $condition = array())
    {
        $this->andHaving($condition);

        return $this;
    }

    /**
     * Create AND HAVING ... condition
     * @see having()
     */
    public function andHaving(array $condition = array())
    {
        $this->having = self::buildCondition($condition, 'AND');

        return $this;
    }

    /**
     * Create OR HAVING ... condition
     * @see having()
     */
    public function orHaving(array $condition = array())
    {
        $this->having = self::buildCondition($condition, 'OR');

        return $this;
    }

    /**
     * Add LIMIT clause
     * @param int argument1 The offset
     * @param int argument2 The row count
     *
     * OR
     *
     * @param int argument1 The row count
    * @return object QueryBuilder
     */
    public function limit()
    {
        $args = func_get_args();
        if (count($args) === 2 && is_numeric($args[0]) && is_numeric($args[1])) {
            $this->offset = $args[0];
            $this->limit = $args[1];
        } elseif (count($args) === 1 && is_numeric($args[0])) {
            $this->limit = $args[0];
        }

        return $this;
    }

    /**
     * Add COUNT(*) or COUNT(field)
     *
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    public function count($field = null, $alias = null)
    {
        $this->setAggregate('count', $field, $alias);

        return $this;
    }

    /**
     * Add MAX(field)
     *
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    public function max($field, $alias = null)
    {
        $this->setAggregate('max', $field, $alias);

        return $this;
    }

    /**
     * Add MIN(field)
     *
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    public function min($field, $alias = null)
    {
        $this->setAggregate('min', $field, $alias);

        return $this;
    }

    /**
     * Add SUM(field)
     *
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    public function sum($field, $alias = null)
    {
        $this->setAggregate('sum', $field, $alias);

        return $this;
    }

    /**
     * Add AVG(field)
     *
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    public function avg($field, $alias = null)
    {
        $this->setAggregate('avg', $field, $alias);

        return $this;
    }

    /**
     * Aggregation
     *
     * @param string $name The function name COUNT, MAX, MIN, SUM, AVG, etc.
     * @param string $field The field name
     * @param string $alias The alias field name to retrieve
     *
     * @object QueryBuilder
     */
    protected function setAggregate($name, $field = null, $alias = null)
    {
        if (!isset($this->aggregates[$name])) {
            $this->aggregates[$name] = array();
        }
        $field = ($field === null) ? '*' : $field;
        $this->aggregates[$name][$field] = ($alias === null) ? $field : array($field, $alias);

        return $this;
    }

    /**
     * Build SQL
     *
     * @return object QueryBuilder
     */
    protected function buildSQL()
    {
        $sql = 'SELECT ';
        # SELECT fields
        $select = array();
        if ($this->fields) {
            foreach ($this->fields as $tableAlias => $field) {
                foreach ($field as $f) {
                    $readyField = $this->prepareField($tableAlias, $f);
                    if ($readyField) {
                        $select[] = $readyField;
                    }
                }
            }
        }

        if (count($this->aggregates)) {
            foreach ($this->aggregates as $func => $fields) {
                $func = strtoupper($func);
                foreach ($fields as $field) {
                    if (is_array($field)) {
                        $select[] = $func . '(' . self::quote($field[0]) . ') ' . self::quote($field[1]);
                    } else {
                        $select[] = $func . '(' . self::quote($field) . ')';
                    }
                }
            }
        }

        if (count($select) === 0) {
            $select = array(self::quote($this->alias) . '.*');
        }

        $sql .= implode(', ', $select);

        # FROM clause
        $sql .= ' FROM ' . self::quote($this->table) . ' ' . self::quote($this->alias);

        # JOIN clause
        if ($this->joins) {
            $joins = array();
            foreach ($this->joins as $join) {
                $join = (object) $join;
                if (preg_match_all('/([a-z0-9_]+\.[a-z0-9_]+)/i', $join->condition, $matches)) {
                    $matchedFields = array_unique($matches[0]);
                    foreach ($matchedFields as $field) {
                        $join->condition = str_replace($field, self::quote($field), $join->condition);
                    }
                }
                $joins[] = $join->type . ' JOIN '
                    . self::quote($join->table) . ' ' . self::quote($join->alias)
                    . ' ON ' . $join->condition;
            }
            $sql .= ' ' . implode(' ', $joins);
        }

        # WHERE clause
        if ($this->where) {
            if (is_array($this->where)) {
                if (array_key_exists('AND', $this->where)) {
                    $sql .= ' WHERE ' . self::buildCondition($this->where['AND'], 'AND');
                } elseif (array_key_exists('OR', $this->where)) {
                    $sql .= ' WHERE ' . self::buildCondition($this->where['OR'], 'OR');
                }
            } else {
                $sql .= ' WHERE ' . $this->where;
            }
        }

        # ORDER BY clause
        if ($this->orderBy) {
            $orderBy = array();
            foreach ($this->orderBy as $field => $sort) {
                $orderBy[] = self::quote($field) . ' ' . $sort;
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }

        # GROUP BY clause
        if ($this->groupBy) {
            $groupBy = array();
            foreach ($this->groupBy as $field) {
                $groupBy[] = self::quote($field);
            }
            $sql .= ' GROUP BY ' . implode(', ', $groupBy);
        }

        # HAVING clause
        if ($this->having) {
            $sql .= ' HAVING ' . $this->having;
        }

        # LIMIT clause
        if ($this->offset !== null && $this->limit) {
            $sql .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
        } elseif ($this->limit && $this->offset === null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        $this->sql = $sql;

        return $this;
    }

    /**
     * Execute the query
     *
     * @return The result object
     */
    public function execute()
    {
        $this->buildSQL();
        if ($this->sql) {
            $this->result = db_query($this->sql);
        }

        return $this->result;
    }

    /**
     * Get the number of rows in the query result
     * @return int Returns the number of rows in the result set.
     */
    public function getNumRows()
    {
        if ($this->result === null) {
            $this->execute();
        }

        if ($this->result) {
            return db_numRows($this->result);
        }

        return 0;
    }

    /**
     * Fetch a query result row
     *
     * @param int $resultType The optional constant indicating what type of array should be produced.
     *   The possible values for this parameter are the constants
     *   **LC_FETCH_OBJECT**, **LC_FETCH_ASSOC**, or **LC_FETCH_ARRAY**.
     *   Default to **LC_FETCH_OBJECT**.
     *
     * @return mixed
     */
    public function fetchRow($resultType = LC_FETCH_OBJECT)
    {
        if ($this->result === null) {
            $this->execute();
        }

        if ($this->result) {
            if ($row = db_fetchAssoc($this->result)) {
                if ($resultType === LC_FETCH_ARRAY) {
                    return array_values($row);
                } elseif ($resultType === LC_FETCH_OBJECT) {
                    return (object) $row;
                } else {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Perform a query on the database and return the array of all results
     *
     * @return array|null The result array of objects.
     *   If the result not found, return null.
     */
    public function getResult()
    {
        if ($this->result === null) {
            $this->execute();
        }

        $data = null;
        if ($this->result) {
            $data = array();
            while ($row = db_fetchObject($this->result)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Perform a query on the database and return the result object
     *
     * @return object|null The result object
     *   If the result not found, return null.
     */
    public function getSingleResult()
    {
        $this->limit(1);

        if ($this->result === null) {
            $this->execute();
        }

        if ($row = db_fetchObject($this->result)) {
            return $row;
        }

        return null;
    }

    /**
     * Perform a query on the database and fetch one field only
     *
     * @return mixed The result
     *   If the result not found, return null.
     */
    public function fetch()
    {
        $this->limit(1);

        if ($this->result === null) {
            $this->execute();
        }

        if ($this->result && $row = db_fetchArray($this->result)) {
            return $row[0];
        }

        return null;
    }

    /**
     * Get the built SQL
     *
     * @return string
     */
    public function getSQL()
    {
        if ($this->sql === null) {
            $this->buildSQL();
        }

        return $this->sql;
    }

    /**
     * Validate table name or field name
     *
     * @param string $name The table name or field name to be validated
     * @return boolean
     */
    public static function validateName($name)
    {
        if (!is_string($name)) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    /**
     * Quote table name and field name
     *
     * @param string $name The table name or field name or table.field
     * @return string
     */
    public static function quote($name)
    {
        $name = trim($name);

        if ($name === '*') {
            return $name;
        }

        foreach (self::$functions as $func) {
            if (stripos($name, $func) === 0) {
                return $name;
            }
        }

        if (strpos($name, '.') !== false) {
            $name = str_replace('.', '`.`', $name);
        }

        return '`' . $name . '`';
    }

    /**
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
    public static function buildCondition($cond = array(), $type = 'AND')
    {
        if (!is_array($cond)) {
            return $cond;
        }
        if (empty($cond)) {
            return '';
        }
        $type      = strtoupper($type);
        $condition = array();

        foreach ($cond as $field => $value) {
            $field    = trim($field);
            $fieldOpr = explode(' ', $field);
            $field    = trim($fieldOpr[0]);

            if (strpos($field, '__QueryBuilder::condition__') !== false) {
                $field = substr($field, 0, strpos($field, '__QueryBuilder::condition__'));
            }

            $opr = (count($fieldOpr) === 2) ? trim($fieldOpr[1]) : '=';

            # check if any operator is given in the field
            if (!in_array($opr, self::$operators)) {
                $opr = '=';
            }

            if (is_numeric($field)) {
                # if the field is array index,
                # assuming that is a condition built by db_or() or db_and();
                $condition[] = '( ' . $value . ' )';
            } else {
                # if the operator is "between", the value must be array
                # otherwise force to "="
                if (in_array($opr, array('between', 'nbetween')) && !is_array($value)) {
                    $opr = '=';
                }
                $opr = strtolower($opr);
                $field = self::quote($field);

                if (array_key_exists($opr, self::$likes)) {
                    $value = str_replace(':likeValue', db_escapeString($value), self::$likes[$opr]);
                    $condition[] = $field . ' ' . $value;
                } elseif (is_numeric($value)) {
                    $condition[] = $field . ' ' . $opr . ' ' . db_escapeString($value) . '';
                } elseif (is_string($value)) {
                    $condition[] = $field . ' ' . $opr . ' "' . db_escapeString($value) . '"';
                } elseif (is_null($value)) {
                    if (in_array($opr, array('!=', '<>'))) {
                        $condition[] = $field . ' IS NOT NULL';
                    } else {
                        $condition[] = $field . ' IS NULL';
                    }
                } elseif (is_array($value) && count($value)) {
                    $list = array();
                    foreach ($value as $v) {
                        $list[] = (is_numeric($v)) ? db_escapeString($v) : '"' . db_escapeString($v) . '"';
                    }
                    if ($opr === 'between') {
                        $condition[] = '( ' . $field . ' BETWEEN ' . current($list) . ' AND ' . end($list) . ' )';
                    } elseif ($opr === 'nbetween') {
                        $condition[] = '( ' . $field . ' NOT BETWEEN ' . current($list) . ' AND ' . end($list) . ' )';
                    } elseif ($opr === '!=') {
                        $condition[] = $field . ' NOT IN (' . implode(', ', $list) . ')';
                    } else {
                        $condition[] = $field . ' IN (' . implode(', ', $list) . ')';
                    }
                } else {
                    $condition[] = $field . ' ' . $opr . ' ' . db_escapeString($value);
                }
            }
        }
        if (count($condition)) {
            $condition = implode(" {$type} ", $condition);
        } else {
            $condition = '';
        }
        return $condition;
    }
}
