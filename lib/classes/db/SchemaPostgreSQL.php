<?php

/**
 * This file is part of the PHPLucidFrame library.
 * PostgreSQL schema driver implementation
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

class SchemaPostgreSQL implements SchemaInterface
{
    public function getDefaultOptions()
    {
        return array(
            'timestamps'    => true,
            'constraints'   => true,
            'schema'        => 'public',
        );
    }

    public function quoteIdentifier($identifier)
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function getSchemaQualifiedTableName($tableName, $options = array())
    {
        $schema = $options['schema'] ?? 'public';
        return $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier($tableName);
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
                return $dataTypes['text'];
            }

            return $definition['type'] == 'blob' ? $dataTypes['blob'] : $dataTypes['text'];
        }

        if ($definition['type'] == 'boolean') {
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
        } elseif ($type === 'decimal') {
            $length = $definition['length'] ?? 0;
            if (is_array($length) && count($length) === 2) {
                $length = implode(', ', $length);
            }
        } else {
            $length = 0;
        }

        if (isset($definition['length']) && is_numeric($definition['length'])
            && in_array($type, array('string', 'char', 'decimal'))
        ) {
            $length = $definition['length'];
        }

        return $length;
    }

    public function shouldUseInlineIdentityPK($definition)
    {
        if (empty($definition['type'])) {
            return false;
        }

        if (empty($definition['primary']) && empty($definition['autoinc'])) {
            return false;
        }

        if ($this->isExplicitSerialDefinition($definition)) {
            return false;
        }

        return in_array(strtolower($definition['type']), array('int', 'integer', 'bigint', 'smallint'));
    }

    public function isSerialType($type)
    {
        return in_array(strtolower($type), array('serial', 'bigserial', 'smallserial'));
    }

    public function buildInlineIdentityPKStatement($field, $type, $definition)
    {
        $generator = $definition['generator'] ?? 'ALWAYS';
        $generator = $generator === 'default' ? 'BY DEFAULT' : $generator;

        return $field . ' ' . $type . ' GENERATED ' . $generator . ' AS IDENTITY PRIMARY KEY NOT NULL';
    }

    public function appendDriverSpecificFieldStatement($statement, $definition, $collate, $schemaOptions = array())
    {
        return $statement;
    }

    public function getDefaultValueStatement($definition)
    {
        if ($definition['type'] === 'boolean') {
            $defaultValue = $definition['default'] ? 'TRUE' : 'FALSE';
            return " DEFAULT {$defaultValue}";
        }

        return sprintf(" DEFAULT '%s'", $definition['default']);
    }

    public function getAutoIncStatement($definition)
    {
        if (!empty($definition['primary']) || !empty($definition['autoinc'])) {
            return ' PRIMARY KEY';
        }

        return '';
    }

    public function getDisableFKCheckStatements()
    {
        return array();
    }

    public function getEnableFKCheckStatements()
    {
        return array();
    }

    public function buildTableIndexDefinitions($table, $fkFields, $options, $quoteIdentifierCallback)
    {
        $tableDefinitions = array();

        if (count($fkFields)) {
            foreach (array_keys($fkFields) as $name) {
                if (isset($fkFields[$name]['unique']) && $fkFields[$name]['unique']) {
                    $tableDefinitions[] = '  CONSTRAINT '
                        . call_user_func($quoteIdentifierCallback, "UQ_{$table}_{$name}")
                        . ' UNIQUE (' . call_user_func($quoteIdentifierCallback, $name) . ')';
                }
            }
        }

        if (isset($options['unique']) && is_array($options['unique'])) {
            foreach ($options['unique'] as $keyName => $uniqueFields) {
                $quotedFields = array_map($quoteIdentifierCallback, $uniqueFields);
                $tableDefinitions[] = '  CONSTRAINT '
                    . call_user_func($quoteIdentifierCallback, "UQ_{$table}_{$keyName}")
                    . ' UNIQUE (' . implode(',', $quotedFields) . ')';
            }
        }

        return $tableDefinitions;
    }

    public function shouldSkipTablePrimaryKey($pkFieldsForTable)
    {
        if (!is_array($pkFieldsForTable) || count($pkFieldsForTable) !== 1) {
            return false;
        }

        $singlePkField = current($pkFieldsForTable);
        return $this->shouldUseInlineIdentityPK($singlePkField);
    }

    public function getCreateTableOptionsStatement($options, $autoinc)
    {
        return '';
    }

    public function getPostCreateTableStatements($table, $fullTableName, $fkFields, $quoteIdentifierCallback)
    {
        $statements = array();

        if (!count($fkFields)) {
            return $statements;
        }

        foreach (array_keys($fkFields) as $name) {
            if (!isset($fkFields[$name]['unique']) || !$fkFields[$name]['unique']) {
                $indexName = call_user_func($quoteIdentifierCallback, "IDX_{$table}_{$name}");
                $fieldName = call_user_func($quoteIdentifierCallback, $name);
                $statements[] = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$fullTableName} ({$fieldName});";
            }
        }

        return $statements;
    }

    public function buildAlterColumnStatement($quotedTableName, $quotedOldField, $newFieldStatement)
    {
        $definition = trim($newFieldStatement);

        // `$newFieldStatement` is generated as:
        //   "<field>" <type/identity> [NOT NULL|DEFAULT NULL] [DEFAULT ...] [PRIMARY KEY]
        // PostgreSQL ALTER COLUMN TYPE accepts only the type expression, so we split
        // the generated definition into discrete ALTER actions.
        if (preg_match('/^\s*("[^"]+"|\S+)\s+(.+)$/', $definition, $matches)) {
            $definition = trim($matches[2]);
        }

        $actions = array();

        $hasPrimaryKey = preg_match('/\bPRIMARY\s+KEY\b/i', $definition) === 1;
        $definition = trim(preg_replace('/\bPRIMARY\s+KEY\b/i', '', $definition));

        $generator = null;
        if (preg_match('/\bGENERATED\s+(ALWAYS|BY\s+DEFAULT)\s+AS\s+IDENTITY\b/i', $definition, $match)) {
            $generator = strtoupper(trim($match[1]));
            $definition = trim(preg_replace('/\bGENERATED\s+(ALWAYS|BY\s+DEFAULT)\s+AS\s+IDENTITY\b/i', '', $definition));
        }

        $defaultValue = null;
        $dropDefault = false;
        if (preg_match('/\bDEFAULT\s+NULL\b/i', $definition)) {
            $dropDefault = true;
            $definition = trim(preg_replace('/\bDEFAULT\s+NULL\b/i', '', $definition));
        } elseif (preg_match('/\bDEFAULT\b\s+(.+)$/i', $definition, $match)) {
            $defaultValue = trim($match[1]);
            $definition = trim(preg_replace('/\bDEFAULT\b\s+(.+)$/i', '', $definition));
        }

        $setNotNull = null;
        if (preg_match('/\bNOT\s+NULL\b/i', $definition)) {
            $setNotNull = true;
            $definition = trim(preg_replace('/\bNOT\s+NULL\b/i', '', $definition));
        } elseif (preg_match('/\bNULL\b/i', $definition)) {
            $setNotNull = false;
            $definition = trim(preg_replace('/\bNULL\b/i', '', $definition));
        }

        $typeExpression = trim($definition);
        $serialMap = array(
            'serial' => 'INTEGER',
            'bigserial' => 'BIGINT',
            'smallserial' => 'SMALLINT',
        );
        $serialType = strtolower($typeExpression);
        if (isset($serialMap[$serialType])) {
            $typeExpression = $serialMap[$serialType];
            if ($generator === null) {
                $generator = 'BY DEFAULT';
            }
        }

        if ($typeExpression !== '') {
            // PostgreSQL does not always perform implicit casts during type changes.
            // Explicit USING keeps migrations deterministic across existing data.
            $actions[] = "ALTER COLUMN {$quotedOldField} TYPE {$typeExpression} USING CAST({$quotedOldField} AS {$typeExpression})";
        }

        if ($generator !== null) {
            $actions[] = "ALTER COLUMN {$quotedOldField} ADD GENERATED {$generator} AS IDENTITY";
        }

        if ($dropDefault) {
            $actions[] = "ALTER COLUMN {$quotedOldField} DROP DEFAULT";
        } elseif ($defaultValue !== null) {
            $actions[] = "ALTER COLUMN {$quotedOldField} SET DEFAULT {$defaultValue}";
        }

        if ($setNotNull === true) {
            $actions[] = "ALTER COLUMN {$quotedOldField} SET NOT NULL";
        } elseif ($setNotNull === false) {
            $actions[] = "ALTER COLUMN {$quotedOldField} DROP NOT NULL";
        }

        // Keep migration non-destructive: avoid re-adding PK constraints here.
        // PK constraints are handled at table level and may already exist.
        if ($hasPrimaryKey && empty($actions)) {
            return '';
        }

        if (empty($actions)) {
            return '';
        }

        return "ALTER TABLE {$quotedTableName} " . implode(', ', $actions) . ';';
    }

    public function buildRenameColumnStatement($quotedTableName, $oldField, $newField, $newFieldStatement, $quoteIdentifierCallback)
    {
        $quotedOldField = call_user_func($quoteIdentifierCallback, $oldField);
        $quotedNewField = call_user_func($quoteIdentifierCallback, $newField);
        return "ALTER TABLE {$quotedTableName} RENAME COLUMN {$quotedOldField} TO {$quotedNewField};";
    }

    public function addColumnPosition($field, $fieldBefore)
    {
        return '';
    }

    public function getDropConstraintStatement($fullTableName, $table, $options, $quoteIdentifierCallback)
    {
        $schemaName = isset($options['schema']) ? $options['schema'] : 'public';
        $tableName = db_table($table);

        $result = db_query("\n            SELECT constraint_name\n            FROM information_schema.table_constraints\n            WHERE table_schema = '{$schemaName}'\n            AND table_name = '{$tableName}'\n            AND constraint_type = 'FOREIGN KEY'\n        ");

        $fKeys = array();
        if ($result) {
            while ($row = db_fetchAssoc($result)) {
                $constraintName = call_user_func($quoteIdentifierCallback, $row['constraint_name']);
                $fKeys[] = " DROP CONSTRAINT {$constraintName}";
            }
        }

        if (count($fKeys)) {
            return 'ALTER TABLE ' . $fullTableName . PHP_EOL . implode(',' . PHP_EOL, $fKeys) . ';';
        }

        return null;
    }

    private function isExplicitSerialDefinition($definition)
    {
        if (empty($definition['type'])) {
            return false;
        }

        return in_array(strtolower($definition['type']), array('serial', 'bigserial', 'smallserial'));
    }
}
