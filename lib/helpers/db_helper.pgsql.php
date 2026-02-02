<?php
/**
 * This file is part of the PHPLucidFrame library.
 * PostgreSQL-specific database helper functions
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
 * PostgreSQL-specific implementation of db_insert
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 * @param boolean $useSlug True to include the slug field or False to not exclude it
 * @return mixed Returns inserted id on success or FALSE on failure
 */
function pgsql_db_insert($table, $data = array(), $useSlug = true)
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
                // PostgreSQL uses true/false for boolean values
                $data[$field] = $value ? 'true' : 'false';
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

    $sqlFields = implode(', ', array_map('pgsql_db_quote', $fields));
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

    // PostgreSQL uses RETURNING clause to get the inserted ID
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING id',
                   pgsql_db_quote($table), $sqlFields, $placeHolders);

    $result = db_query($sql, $values);
    if ($result) {
        $row = db_fetchAssoc($result);
        return $row ? $row['id'] : false;
    }

    return false;
}

/**
 * PostgreSQL-specific implementation of db_update
 *
 * @param string $table The table name without prefix
 * @param array $data The array of data field names and values
 * @param boolean $useSlug TRUE to include the slug field or FALSE to not exclude it
 * @param array $condition The condition for the UPDATE query
 * @return boolean Returns TRUE on success or FALSE on failure
 */
function pgsql_db_update($table, $data = array(), $useSlug = true, array $condition = array())
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
                // PostgreSQL uses true/false for boolean values
                $value = $value ? 'true' : 'false';
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

        $sql = 'UPDATE ' . pgsql_db_quote($table) . ' SET ';
        foreach ($fields as $key => $value) {
            $placeholder = ':upd_' . $key;
            $sql .= sprintf('"%s" = %s, ', $key, $placeholder);
            $values[$placeholder] = $value;
        }
        $sql = rtrim($sql, ', ');
        $sql .= ' WHERE ' . $clause;

        return db_query($sql, $values) ? true : false;
    }

    return false;
}

/**
 * PostgreSQL-specific implementation of db_delete
 *
 * @param string $table Table name without prefix
 * @param array $condition The array of condition for delete
 * @param boolean $softDelete Soft delete or not
 * @return boolean Returns TRUE on success or FALSE on failure
 */
function pgsql_db_delete($table, array $condition = array(), $softDelete = false)
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
        $sql = 'UPDATE '. pgsql_db_quote($table) . '
                SET "deleted" = :deleted ' . $condition . '
                LIMIT 1';
        $values[':deleted'] = date('Y-m-d H:i:s');
        if (_g('db_printQuery')) {
            return $sql;
        }

        return db_query($sql, $values) ? true : false;
    }

    // PostgreSQL doesn't support LIMIT in DELETE, but we can use a subquery
    if (strpos($condition, 'WHERE') !== false) {
        $sql = 'DELETE FROM ' . pgsql_db_quote($table) . $condition . ' AND ctid IN (SELECT ctid FROM ' . pgsql_db_quote($table) . $condition . ' LIMIT 1)';
    } else {
        $sql = 'DELETE FROM ' . pgsql_db_quote($table) . ' WHERE ctid IN (SELECT ctid FROM ' . pgsql_db_quote($table) . ' LIMIT 1)';
    }

    if (_g('db_printQuery')) {
        return $sql;
    }

    ob_start(); # to capture error return
    db_query($sql, $values);
    $return = ob_get_clean();
    if ($return) {
        # If there is FK delete RESTRICT constraint, make soft delete
        if (db_errorNo() == 23503) { // PostgreSQL foreign key violation error code
            if (db_tableHasTimestamps($table)) {
                $sql = 'UPDATE '. pgsql_db_quote($table) . '
                        SET "deleted" = :deleted ' . $condition . '
                        AND ctid IN (SELECT ctid FROM ' . pgsql_db_quote($table) . $condition . ' LIMIT 1)';
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
 * PostgreSQL doesn't have foreign key check disable/enable like MySQL
 * These functions are provided for compatibility but don't do anything
 */
function pgsql_db_setForeignKeyCheck($flag)
{
    // PostgreSQL doesn't support disabling foreign key checks globally
    // This is a no-op for compatibility
}

function pgsql_db_enableForeignKeyCheck()
{
    // No-op for compatibility
}

function pgsql_db_disableForeignKeyCheck()
{
    // No-op for compatibility
}

/**
 * PostgreSQL-specific truncate function
 */
function pgsql_db_truncate($table)
{
    $table = db_table($table);
    // PostgreSQL supports RESTART IDENTITY to reset sequences
    db_query('TRUNCATE ' . pgsql_db_quote($table) . ' RESTART IDENTITY CASCADE');
}

/**
 * PostgreSQL-specific identifier quoting
 */
function pgsql_db_quote($identifier)
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

/**
 * PostgreSQL-specific LIKE clause generation
 */
function pgsql_db_like($field, $value, $type = 'both')
{
    switch ($type) {
        case 'left':
            return sprintf('"%s" LIKE CONCAT(\'%%\', :%s)', $field, $field);
        case 'right':
            return sprintf('"%s" LIKE CONCAT(:%s, \'%%\')', $field, $field);
        case 'both':
        default:
            return sprintf('"%s" LIKE CONCAT(\'%%\', :%s, \'%%\')', $field, $field);
    }
}

/**
 * PostgreSQL-specific LIMIT clause generation
 */
function pgsql_db_limit($limit, $offset = 0)
{
    if ($offset > 0) {
        return sprintf('LIMIT %d OFFSET %d', $limit, $offset);
    }
    return sprintf('LIMIT %d', $limit);
}