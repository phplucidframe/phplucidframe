<?php
/**
 * This file is part of the PHPLucidFrame library.
 * MySQL-specific database helper functions
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
 * MySQL-specific implementation of db_insert
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 * @param boolean $useSlug True to include the slug field or False to not exclude it
 * @return mixed Returns inserted id on success or FALSE on failure
 */
function mysql_db_insert($table, $data = array(), $useSlug = true)
{
    QueryBuilder::clearBindValues();

    if (count($data) == 0) {
        return false;
    }

    $db = _app('db');
    $table = db_table($table);

    # Invoke the hook db_insert_[table_name] if any
    $hook = 'db_insert_' . strtolower($table);
    if (function_exists($hook)) {
        return call_user_func_array($hook, array($table, $data, $useSlug));
    }

    # if slug is already provided in the data array, use it
    if (array_key_exists('slug', $data)) {
        $slug = _slug($data['slug']);
        $data['slug'] = $slug;
        session_set('lastInsertSlug', $slug);
        $useSlug = false;
    }

    $dsm = $db->schemaManager;
    if (is_object($dsm) && $dsm->isLoaded()) {
        foreach ($data as $field => $value) {
            $fieldType = $db->schemaManager->getFieldType($table, $field);
            if (is_array($value) && $fieldType == 'array') {
                $data[$field] = serialize($value);
            } elseif (is_array($value) && $fieldType == 'json') {
                $jsonValue = json_encode($value);
                $data[$field] = $jsonValue ? $jsonValue : null;
            } elseif ($fieldType == 'boolean') {
                $data[$field] = $value ? 1 : 0;
            }
        }
    }

    $fields = array_keys($data);
    $dataValues = array_values($data);

    if (db_tableHasSlug($table, $useSlug)) {
        $fields[] = 'slug';
    }

    if (db_tableHasTimestamps($table)) {
        if (!array_key_exists('created', $data)) {
            $fields[] = 'created';
        }
        if (!array_key_exists('updated', $data)) {
            $fields[] = 'updated';
        }
    }

    $fields = array_unique($fields);

    $sqlFields = implode(', ', $fields);
    $placeHolders = implode(', ', array_fill(0, count($fields), '?'));
    $values = array();
    $i = 0;

    # escape the data
    foreach ($dataValues as $val) {
        if ($i == 0 && $useSlug) {
            $slug = $val;
        }

        $values[] = is_null($val) ? null : $val;
        $i++;
    }

    if (db_tableHasSlug($table, $useSlug)) {
        $slug = _slug($slug, $table);
        session_set('lastInsertSlug', $slug);
        $values[] = $slug;
    }

    if (db_tableHasTimestamps($table)) {
        if (!array_key_exists('created', $data)) {
            $values[] = date('Y-m-d H:i:s');
        }
        if (!array_key_exists('updated', $data)) {
            $values[] = date('Y-m-d H:i:s');
        }
    }

    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', QueryBuilder::quote($table), $sqlFields, $placeHolders);

    return db_query($sql, $values) ? db_insertId() : false;
}

/**
 * MySQL-specific implementation of db_update
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 * @param boolean $useSlug TRUE to include the slug field or FALSE to not exclude it
 * @param array $condition The condition for the UPDATE query
 * @return boolean Returns TRUE on success or FALSE on failure
 */
function mysql_db_update($table, $data = array(), $useSlug = true, array $condition = array())
{
    QueryBuilder::clearBindValues();

    if (count($data) == 0) {
        return false;
    }

    $db = _app('db');

    if (func_num_args() === 3 && (gettype($useSlug) === 'string' || is_array($useSlug))) {
        $condition = $useSlug;
        $useSlug = true;
    }

    $table = db_table($table);

    # Invoke the hook db_update_[table_name] if any
    $hook = 'db_update_' . strtolower($table);
    if (function_exists($hook)) {
        return call_user_func_array($hook, array($table, $data, $useSlug, $condition));
    }

    # if slug is already provided in the data array, use it
    if (array_key_exists('slug', $data)) {
        $slug = _slug($data['slug']);
        $data['slug'] = $slug;
        session_set('lastInsertSlug', $slug);
        $useSlug = false;
    }

    $fields = array();
    $slug = '';
    $cond = '';
    $i = 0;
    $slugIndex = 1;

    if ($condition) {
        $slugIndex = 0;
    }

    $dsm = $db->schemaManager;
    foreach ($data as $field => $value) {
        if ($i === 0 && !$condition) {
            # $data[0] is for PK condition, but only if $condition is not provided
            $cond = array($field => $value); # for PK condition
            $i++;
            continue;
        }

        if (is_object($dsm) && $dsm->isLoaded()) {
            $fieldType = $dsm->getFieldType($table, $field);
            if (is_array($value) && $fieldType == 'array') {
                $value = serialize($value);
            } elseif (is_array($value) && $fieldType == 'json') {
                $jsonValue = json_encode($value);
                $value = $jsonValue ? $jsonValue : null;
            } elseif ($fieldType == 'boolean') {
                $value = $value ? 1 : 0;
            }
        }

        $fields[$field] = is_null($value) ? null : $value;

        if ($i === $slugIndex && $useSlug === true) {
            # $data[1] is slug
            $slug = $value;
        }

        $i++;
    }

    # must have condition
    # this prevents unexpected update happened to all records
    if ($cond || $condition) {
        $clause = '';
        $notCond = array();
        $values = array();

        if ($cond && is_array($cond) && count($cond)) {
            QueryBuilder::clearBindValues();
            list($clause, $values) = db_condition($cond);
            $notCond = array(
                '$not' => $cond
            );
        } elseif ($condition && is_array($condition) && count($condition)) {
            QueryBuilder::clearBindValues();
            list($clause, $values) = db_condition($condition);
            $notCond = array(
                '$not' => $condition
            );
        }

        if (empty($clause)) {
            return false;
        }

        if (db_tableHasSlug($table, $useSlug)) {
            $slug = _slug($slug, $table, $notCond);
            session_set('lastInsertSlug', $slug);
            $fields['slug'] = $slug;
        }

        if (db_tableHasTimestamps($table)) {
            $fields['updated'] = date('Y-m-d H:i:s');
        }

        $sql = 'UPDATE ' . QueryBuilder::quote($table) . ' SET ';
        foreach ($fields as $key => $value) {
            $placeholder = ':upd_' . $key;
            $sql .= sprintf('`%s` = %s, ', $key, $placeholder);
            $values[$placeholder] = $value;
        }
        $sql = rtrim($sql, ', ');
        $sql .= ' WHERE ' . $clause;

        return db_query($sql, $values) ? true : false;
    }

    return false;
}

/**
 * MySQL-specific implementation of db_delete
 *
 * @param string $table Table name without prefix
 * @param array $condition The array of condition for delete
 * @param boolean $softDelete Soft delete or not
 * @return boolean Returns TRUE on success or FALSE on failure
 */
function mysql_db_delete($table, array $condition = array(), $softDelete = false)
{
    QueryBuilder::clearBindValues();

    $table = db_table($table);

    # Invoke the hook db_delete_[table_name] if any
    $hook = 'db_delete_' . strtolower($table);
    if (function_exists($hook)) {
        return call_user_func_array($hook, array($table, $condition));
    }

    $values = array();

    if (is_array($condition)) {
        list($condition, $values) = db_condition($condition);
    }

    if ($condition) {
        $condition = ' WHERE '.$condition;
    }

    if ($softDelete) {
        $sql = 'UPDATE '. QueryBuilder::quote($table) . '
                SET `deleted` = :deleted ' . $condition . '
                LIMIT 1';
        $values[':deleted'] = date('Y-m-d H:i:s');
        if (_g('db_printQuery')) {
            return $sql;
        }

        return db_query($sql, $values) ? true : false;
    }

    $sql = 'DELETE FROM ' . QueryBuilder::quote($table) . $condition . ' LIMIT 1';
    if (_g('db_printQuery')) {
        return $sql;
    }

    ob_start(); # to capture error return
    db_query($sql, $values);
    $return = ob_get_clean();
    if ($return) {
        # If there is FK delete RESTRICT constraint, make soft delete
        if (db_errorNo() == 1451) {
            if (db_tableHasTimestamps($table)) {
                $sql = 'UPDATE '. QueryBuilder::quote($table) . '
                        SET `deleted` = :deleted ' . $condition . '
                        LIMIT 1';
                $values[':deleted'] = date('Y-m-d H:i:s');

                return db_query($sql, $values);
            }

            return false;
        } else {
            echo $return;

            return false;
        }
    }

    return db_errorNo() == 0;
}

/**
 * MySQL-specific foreign key check functions
 */
function mysql_db_setForeignKeyCheck($flag)
{
    db_query('SET FOREIGN_KEY_CHECKS =' . $flag);
}

function mysql_db_enableForeignKeyCheck()
{
    mysql_db_setForeignKeyCheck(1);
}

function mysql_db_disableForeignKeyCheck()
{
    mysql_db_setForeignKeyCheck(0);
}

/**
 * MySQL-specific truncate function
 */
function mysql_db_truncate($table)
{
    $table = db_table($table);
    db_query('TRUNCATE ' . QueryBuilder::quote($table));
}

/**
 * MySQL-specific identifier quoting
 */
function mysql_db_quote($identifier)
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * MySQL-specific LIKE clause generation
 */
function mysql_db_like($field, $value, $type = 'both')
{
    switch ($type) {
        case 'left':
            return sprintf('`%s` LIKE CONCAT("%%", :%s)', $field, $field);
        case 'right':
            return sprintf('`%s` LIKE CONCAT(:%s, "%%")', $field, $field);
        case 'both':
        default:
            return sprintf('`%s` LIKE CONCAT("%%", :%s, "%%")', $field, $field);
    }
}

/**
 * MySQL-specific LIMIT clause generation
 */
function mysql_db_limit($limit, $offset = 0)
{
    if ($offset > 0) {
        return sprintf('LIMIT %d, %d', $offset, $limit);
    }
    return sprintf('LIMIT %d', $limit);
}