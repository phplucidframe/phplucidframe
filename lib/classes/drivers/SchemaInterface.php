<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Schema driver interface for SchemaManager
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

namespace LucidFrame\Core\drivers;

interface SchemaInterface
{
    public function getDefaultOptions();

    public function quoteIdentifier($identifier);

    public function getSchemaQualifiedTableName($tableName, $options = array());

    public function getVendorFieldType(&$definition, $dataTypes);

    public function getFieldLength($definition);

    public function shouldUseInlineIdentityPrimaryKey($definition);

    public function isSerialType($type);

    public function buildInlineIdentityPrimaryKeyStatement($field, $type, $definition);

    public function appendDriverSpecificFieldStatement($statement, $definition, $collate, $schemaOptions = array());

    public function getDefaultValueStatement($definition);

    public function getAutoIncrementStatement($definition);

    public function getDisableForeignKeyChecksStatements();

    public function getEnableForeignKeyChecksStatements();

    public function buildTableIndexDefinitions($table, $fkFields, $options, $quoteIdentifierCallback);

    public function shouldSkipTablePrimaryKey($pkFieldsForTable);

    public function getCreateTableOptionsStatement($options, $autoinc);

    public function getPostCreateTableStatements($table, $fullTableName, $fkFields, $quoteIdentifierCallback);

    public function buildAlterColumnStatement($quotedTableName, $quotedOldField, $newFieldStatement);

    public function buildRenameColumnStatement($quotedTableName, $oldField, $newField, $newFieldStatement, $quoteIdentifierCallback);

    public function getDropConstraintStatement($fullTableName, $table, $options, $quoteIdentifierCallback);
}
