<?php

/**
 * This file is part of the PHPLucidFrame library.
 * MySQL schema driver implementation
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

namespace LucidFrame\Core\db;

class SchemaMySQL implements SchemaInterface
{
    public function getDefaultOptions()
    {
        return array(
            'timestamps'    => true,
            'constraints'   => true,
            'charset'       => 'utf8mb4',
            'collate'       => 'utf8mb4_general_ci',
            'engine'        => 'InnoDB',
        );
    }

    public function quoteIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function getSchemaQualifiedTableName($tableName, $options = array())
    {
        return $this->quoteIdentifier($tableName);
    }

    public function getVendorFieldType(&$definition, $dataTypes)
    {
        if (!isset($dataTypes[$definition['type']])) {
            return null;
        }

        $type = $dataTypes[$definition['type']];

        if (in_array($definition['type'], array('text', 'blob', 'array', 'json'))) {
            if ($definition['type'] === 'json') {
                return $type;
            }

            if (isset($definition['length']) && in_array($definition['length'], array('tiny', 'medium', 'long'))) {
                return strtoupper($definition['length']) . $type;
            }

            return $definition['type'] == 'blob' ? $dataTypes['blob'] : $dataTypes['text'];
        }

        if ($definition['type'] == 'boolean') {
            $definition['unsigned'] = true;

            if (!isset($definition['default'])) {
                $definition['default'] = false;
            }

            if (!isset($definition['null'])) {
                $definition['null'] = false;
            } elseif ($definition['null'] === true) {
                $definition['default'] = null;
            }
        }

        if (!empty($definition['primary']) || !empty($definition['autoinc'])) {
            $definition['null'] = false;
        }

        return $type;
    }

    public function getFieldLength($definition)
    {
        $type = $definition['type'];

        if ($type === 'string' || $type === 'char') {
            $length = 255;
        } elseif ($type === 'int' || $type === 'integer') {
            $length = 11;
        } elseif ($type === 'boolean') {
            $length = 1;
        } elseif (in_array($type, array('text', 'blob', 'array', 'json'))) {
            $length = 0;
        } elseif ($type === 'decimal' || $type === 'float') {
            $length = isset($definition['length']) ? $definition['length'] : 0;
            if (is_array($length) && count($length) === 2) {
                $length = implode(', ', $length);
            }
        } else {
            $length = 0;
        }

        if (isset($definition['length']) && is_numeric($definition['length'])) {
            $length = $definition['length'];
        }

        return $length;
    }

    public function shouldUseInlineIdentityPrimaryKey($definition)
    {
        return false;
    }

    public function isSerialType($type)
    {
        return false;
    }

    public function buildInlineIdentityPrimaryKeyStatement($field, $type, $definition)
    {
        return '';
    }

    public function appendDriverSpecificFieldStatement($statement, $definition, $collate, $schemaOptions = array())
    {
        if (in_array($definition['type'], array('string', 'char', 'text', 'array', 'json'))) {
            $statement .= ' COLLATE ';
            $statement .= $collate ?: $schemaOptions['collate'];
        }

        if (isset($definition['unsigned'])) {
            $statement .= ' unsigned';
        }

        return $statement;
    }

    public function getDefaultValueStatement($definition)
    {
        return sprintf(" DEFAULT '%s'", $definition['default']);
    }

    public function getAutoIncrementStatement($definition)
    {
        if (!empty($definition['primary']) || !empty($definition['autoinc'])) {
            return ' AUTO_INCREMENT';
        }

        return '';
    }

    public function getDisableForeignKeyChecksStatements()
    {
        return array('SET FOREIGN_KEY_CHECKS=0;');
    }

    public function getEnableForeignKeyChecksStatements()
    {
        return array('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function buildTableIndexDefinitions($table, $fkFields, $options, $quoteIdentifierCallback)
    {
        $tableDefinitions = array();

        if (count($fkFields)) {
            foreach (array_keys($fkFields) as $name) {
                if (isset($fkFields[$name]['unique']) && $fkFields[$name]['unique']) {
                    $indexSql = '  UNIQUE KEY';
                } else {
                    $indexSql = '  KEY';
                }
                $indexSql .= ' ' . call_user_func($quoteIdentifierCallback, "IDX_$name")
                    . ' (' . call_user_func($quoteIdentifierCallback, $name) . ')';
                $tableDefinitions[] = $indexSql;
            }
        }

        if (isset($options['unique']) && is_array($options['unique'])) {
            foreach ($options['unique'] as $keyName => $uniqueFields) {
                $quotedFields = array_map($quoteIdentifierCallback, $uniqueFields);
                $tableDefinitions[] = '  UNIQUE KEY ' . call_user_func($quoteIdentifierCallback, "IDX_$keyName")
                    . ' (' . implode(',', $quotedFields) . ')';
            }
        }

        return $tableDefinitions;
    }

    public function shouldSkipTablePrimaryKey($pkFieldsForTable)
    {
        return false;
    }

    public function getCreateTableOptionsStatement($options, $autoinc)
    {
        $sql = ' ENGINE=' . $options['engine'];
        $sql .= ' DEFAULT CHARSET=' . $options['charset'];
        $sql .= ' COLLATE=' . $options['collate'];

        if ($autoinc) {
            $sql .= ' AUTO_INCREMENT=1';
        }

        return $sql;
    }

    public function getPostCreateTableStatements($table, $fullTableName, $fkFields, $quoteIdentifierCallback)
    {
        return array();
    }

    public function buildAlterColumnStatement($quotedTableName, $quotedOldField, $newFieldStatement)
    {
        return "ALTER TABLE {$quotedTableName} CHANGE COLUMN {$quotedOldField} {$newFieldStatement};";
    }

    public function buildRenameColumnStatement($quotedTableName, $oldField, $newField, $newFieldStatement, $quoteIdentifierCallback)
    {
        $quotedOldField = call_user_func($quoteIdentifierCallback, $oldField);
        return "ALTER TABLE {$quotedTableName} CHANGE COLUMN {$quotedOldField} {$newFieldStatement};";
    }

    public function addColumnPosition($field, $fieldBefore)
    {
        if ($fieldBefore && $field != 'created') {
            return ' AFTER ' . $this->quoteIdentifier($fieldBefore);
        }

        return '';
    }

    public function getDropConstraintStatement($fullTableName, $table, $options, $quoteIdentifierCallback)
    {
        $result = db_query("SHOW CREATE TABLE {$fullTableName}");
        if ($result && $row = db_fetchArray($result)) {
            $fKeys = array();
            if (preg_match_all('/CONSTRAINT\s+`([^`]+)`\s+FOREIGN KEY/i', $row[1], $matches)) {
                foreach ($matches[1] as $constraintName) {
                    $fKeys[] = ' DROP FOREIGN KEY ' . call_user_func($quoteIdentifierCallback, $constraintName);
                }
            }

            if (count($fKeys)) {
                return 'ALTER TABLE ' . $fullTableName . PHP_EOL . implode(',' . PHP_EOL, $fKeys) . ';';
            }
        }

        return null;
    }
}
