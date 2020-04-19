<?php
/**
 * This file is part of the PHPLucidFrame library.
 * QueryBuilder class is responsible to dynamically create SQL queries.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.9.0
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
 * QueryBuilder class is responsible to dynamically create SQL queries.
 */
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
    /** @var array The values to sql to bind */
    protected static $bindValues = array();
    /** @var array Collection of SQL operators allowed */
    private static $operators = array(
        '=', '>=', '<=', '>', '<', '!=', '<>',
        'not',
        'between', 'nbetween',
        'like', 'like%%', 'like%~', 'like~%',
        'nlike', 'nlike%%', 'nlike%~', 'nlike~%'
    );
    private static $eqs = array(
        'eq'    => '=',
        'neq'   => '!=',
        'lt'    => '<',
        'lte'   => '<=',
        'gt'    => '>',
        'gte'   => '>=',
    );
    /** @var array Collection of LIKE expressions */
    private static $likes = array(
        'like'      => 'LIKE CONCAT("%", :placeholder, "%")',
        'like%~'    => 'LIKE CONCAT("%", :placeholder)',
        'like~%'    => 'LIKE CONCAT(:placeholder, "%")',
        'nlike'     => 'NOT LIKE CONCAT("%", :placeholder, "%")',
        'nlike%~'   => 'NOT LIKE CONCAT("%", :placeholder)',
        'nlike~%'   => 'NOT LIKE CONCAT(:placeholder, "%")',
    );
    /** @var array Collection of BETWEEN operator mapping */
    private static $betweens = array(
        'between' => 'BETWEEN',
        'nbetween' => 'NOT BETWEEN',
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
        self::clearBindValues();

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

        $this->table = db_table($table);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Add fields to SELECT
     *
     * @param string $alias The table alias
     * @param array $fields Array of field names
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
     * @param string $field The field name
     * @param array $alias The alias for the field name
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
     * @param string $type INNER, LEFT, RIGHT or OUTER
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
            'table'     => db_table($table),
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
     * Create WHERE ... AND condition
     *
     * @param array|null $condition The array of conditions
     * @return object QueryBuilder
     */
    public function where($condition = null)
    {
        return $this->andWhere($condition);
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
            $this->where['AND'][] = self::buildCondition($condition, 'AND');
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
            $this->where['OR'][] = self::buildCondition($condition, 'OR');
        }
        $this->whereType = 'OR';

        return $this;
    }

    /**
     * Create simple WHERE condition with field/value assignment
     *
     * @param string $field The field name
     * @param mixed $value The value to check against the field name
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
            $field .= uniqid('__' . trim(__METHOD__, 'LucidFrame\Core') . '__');
        }
        $this->where[$this->whereType][$field] = $value;

        return $this;
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $field The field name to sort
     * @param string $sort ASC or DESC
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
    public function having(array $condition)
    {
        return $this->andHaving($condition);
    }

    /**
     * Create AND HAVING ... condition
     * @param array $condition The array of conditions
     * @return object QueryBuilder
     * @see having()
     */
    public function andHaving(array $condition)
    {
        return $this->addHaving($condition, 'AND');
    }

    /**
     * Create OR HAVING ... condition
     * @param array $condition The array of conditions
     * @return object QueryBuilder
     * @see having()
     */
    public function orHaving(array $condition = array())
    {
        return $this->addHaving($condition, 'OR');
    }

    /**
     * @internal
     * Create AND/OR HAVING ... condition
     * @param array $condition  The array of conditions
     * @param string $type AND|OR
     * @return object QueryBuilder
     */
    private function addHaving(array $condition, $type)
    {
        list($clause, $values) = self::buildCondition($condition, $type);

        $this->having = $clause;
        self::addBindValues($values);

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
     * @return object QueryBuilder
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
     * @return object QueryBuilder
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
     * @return object QueryBuilder
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
     * @return object QueryBuilder
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
     * @return object QueryBuilder
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
     * @return object QueryBuilder
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
                $join = (object)$join;
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
                    list($clause, $values) = self::buildCondition($this->where['AND'], 'AND');
                    $sql .= ' WHERE ' . $clause;
                    self::addBindValues($values);
                } elseif (array_key_exists('OR', $this->where)) {
                    list($clause, $values) = self::buildCondition($this->where['OR'], 'OR');
                    $sql .= ' WHERE ' . $clause;
                    self::addBindValues($values);
                } elseif (array_key_exists('NOT', $this->where)) {
                    list($clause, $values) = self::buildCondition($this->where['NOT'], 'NOT');
                    $sql .= ' WHERE ' . $clause;
                    self::addBindValues($values);
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
     * @return bool|resource The result
     */
    public function execute()
    {
        $this->buildSQL();

        if ($this->sql) {
            $this->result = db_query($this->sql, self::$bindValues);
        }

        self::clearBindValues();

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
                    return (object)$row;
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
     * Get the built SQL with the values replaced
     * @return string
     */
    public function getReadySQL() {
        $sql = $this->getSQL();

        foreach (QueryBuilder::getBindValues() as $key => $value) {
            $sql = preg_replace('/' . $key . '\b/', $value, $sql);
        }

        return $sql;
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
     * @return array The built condition WHERE AND/OR
     *     [0] string The built condition WHERE AND/OR clause
     *     [1] array The values to bind in the condition
     */
    public static function buildCondition($cond = array(), $type = 'AND')
    {
        if (!is_array($cond)) {
            return $cond;
        }

        if (empty($cond)) {
            return array('', array());
        }

        $type = strtoupper($type);
        $condition = array();

        foreach ($cond as $field => $value) {
            $field = trim($field);

            if (in_array(strtoupper($field), array('AND', 'OR', 'NOT'))) {
                if (strtoupper($field) == 'NOT') {
                    list($nestedClause, $values) = self::buildCondition($value, 'AND');
                    $condition[] = 'NOT (' . $nestedClause . ')';
                } else {
                    list($nestedClause, $values) = self::buildCondition($value, $field);
                    $condition[] = '(' . $nestedClause . ')';
                }
                self::addBindValues($values);
                continue;
            }

            $fieldOpr = explode(' ', $field);
            $field = trim($fieldOpr[0]);

            if (strpos($field, '__QueryBuilder::condition__') !== false) {
                $field = substr($field, 0, strpos($field, '__QueryBuilder::condition__'));
            }

            $opr = count($fieldOpr) === 2 ? trim($fieldOpr[1]) : '=';

            # check if any operator is given in the field
            if (!in_array($opr, self::$operators)) {
                $opr = '=';
            }

            if (is_numeric($field)) {
                # if the field is array index,
                # assuming that is a condition built by db_or() or db_and();
                list($nestedClause, $values) = $value;
                $condition[] = '( ' . $nestedClause . ' )';
                self::addBindValues($values);
            } else {
                # if the operator is "between", the value must be array
                # otherwise force to "="
                if (in_array($opr, array('between', 'nbetween')) && !is_array($value)) {
                    $opr = '=';
                }

                $opr = strtolower($opr);
                $key = $field;
                $placeholder = self::getPlaceholder($key, self::$bindValues);
                $field = self::quote($field);

                if (array_key_exists($opr, self::$likes)) {
                    $condition[] = $field . ' ' . str_replace(':placeholder', $placeholder, self::$likes[$opr]);
                    self::setBindValue($placeholder, $value);
                    continue;
                }

                if (is_null($value)) {
                    if (in_array($opr, array('!=', '<>'))) {
                        $condition[] = $field . ' IS NOT NULL';
                    } else {
                        $condition[] = $field . ' IS NULL';
                    }
                    continue;
                }

                if (is_array($value) && count($value)) {
                    if ($opr === 'between' || $opr === 'nbetween') {
                        $condition[] = sprintf(
                            '(%s %s :%s_from AND :%s_to)',
                            $field,
                            self::$betweens[$opr],
                            $key,
                            $key
                        );

                        self::setBindValue($placeholder . '_from', $value[0]);
                        self::setBindValue($placeholder . '_to', $value[1]);
                    } else {
                        $inPlaceholders = array();
                        foreach ($value as $i => $val) {
                            $placeholder = ':' . $key . $i;
                            $inPlaceholders[] = $placeholder;
                            self::setBindValue($placeholder, $val);
                        }

                        $condition[] = sprintf(
                            '%s%sIN (%s)',
                            $field,
                            $opr === '!=' ? ' NOT ' : ' ',
                            implode(', ', $inPlaceholders)
                        );
                    }
                    continue;
                }

                $condition[] = "{$field} {$opr} {$placeholder}";
                self::setBindValue($placeholder, $value);
            }
        }

        if (count($condition)) {
            return array(
                implode(" {$type} ", $condition),
                self::$bindValues,
            );
        }

        return array('', array());
    }

    private static function getPlaceholder($key, $values = array())
    {
        $placeholders = array_filter($values, function ($placeholder) use ($key) {
            return stripos($placeholder, $key) === 1;
        }, ARRAY_FILTER_USE_KEY);

        if (!count($placeholders)) {
            return ':' . $key;
        }

        $placeholders = array_keys($placeholders);
        rsort($placeholders);

        $index = '';
        if (preg_match('/:' . $key . '(\d)*/', $placeholders[0], $matches)) {
            $index = isset($matches[1]) ? $matches[1] + 1 : 0;
        }

        return ':' . $key . $index;
    }

    /**
     * Bind values for query arguments
     * @param array $values
     */
    private static function addBindValues(array $values)
    {
        self::$bindValues = array_merge(self::$bindValues, $values);
    }

    /**
     * Bind value for query argument by key
     * @param string $key
     * @param mixed $value
     */
    private static function setBindValue($key, $value)
    {
        self::$bindValues[$key] = $value;
    }

    /**
     * Clear bind values
     */
    public static function clearBindValues()
    {
        self::$bindValues = array();
    }

    /**
     * Get bind values
     * @return array
     */
    public static function getBindValues()
    {
        return self::$bindValues;
    }
}
