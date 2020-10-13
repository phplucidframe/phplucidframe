<?php
/**
 * This file is part of the PHPLucidFrame library.
 * SchemaManager manages your database schema.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

use LucidFrame\Console\Command;

/**
 * Schema Manager
 */
class SchemaManager
{
    /** @var array The schema definition */
    protected $schema = array();
    /** @var string The database driver; currently it allows "mysql" only */
    private $driver = 'mysql';
    /** @var array The global schema options */
    private $defaultOptions;
    /** @var array The data types for each db driver */
    private static $dataTypes = array(
        'mysql' => array(
            'smallint'  => 'SMALLINT',
            'int'       => 'INT',
            'integer'   => 'INT',
            'bigint'    => 'BIGINT',
            'decimal'   => 'NUMERIC',
            'float'     => 'DOUBLE',
            # For decimal and float
            # length => array(p, s) where p is the precision and s is the scale
            # The precision represents the number of significant digits that are stored for values, and
            # the scale represents the number of digits that can be stored following the decimal point.
            'string'    => 'VARCHAR',
            'char'      => 'CHAR',
            'binary'    => 'VARBINARY',
            'text'      => 'TEXT',
            'blob'      => 'BLOB',
            'array'     => 'TEXT',
            'json'      => 'TEXT',
            # For text, blob, array and json
            # length => tiny, medium or long
            # tiny for TINYTEXT, medium for MEDIUMTEXT, long for LONGTEXT
            # if no length is specified, default to TEXT
            'boolean'   => 'TINYINT', # TINYINT(1)
            'date'      => 'DATE',
            'datetime'  => 'DATETIME',
            'time'      => 'TIME',
        ),
    );
    /** @var array The relational database relationships */
    public static $relationships = array('1:m', 'm:1', 'm:m', '1:1');
    /** @var string The namespace for the database */
    private $dbNamespace = 'default';
    /** @var array The array of generated SQL statements */
    private $sqlStatements = array();
    /** @var string Version file name extension */
    private $sqlExtension = '.sqlc';
    /** @var array Dropped table names */
    private $droppedTables = array();
    /** @var array Added table names */
    private $addedTables = array();
    /** @var array Dropped field names */
    private $droppedColumns = array();
    /** @var array Added column names */
    private $addedColumns = array();
    /** @var array Renamed table names */
    private $tablesRenamed = array();
    /** @var array Renamed field names */
    private $columnsRenamed = array();

    /**
     * Constructor
     * @param array $schema The array of schema definition
     * @param string $dbNamespace The namespace for the database schema
     */
    public function __construct($schema = array(), $dbNamespace = null)
    {
        $this->defaultOptions = array(
            'timestamps'    => true,
            'constraints'   => true,
            'charset'       => 'utf8mb4',
            'collate'       => 'utf8mb4_general_ci',
            'engine'        => 'InnoDB',
        );

        $this->setSchema($schema);

        if ($dbNamespace) {
            $this->dbNamespace = $dbNamespace;
        }
    }

    /**
     * Setter for the property `schema`
     * @param array $schema The array of schema definition
     * @return object SchemaManager
     */
    public function setSchema($schema)
    {
        if (!is_array($schema)) {
            $schema = array(
                '_options' => $this->defaultOptions
            );
        }

        $this->schema = $schema;

        return $this;
    }

    /**
     * Getter for the property `schema`
     * @return array The array of schema definition
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Setter for the property `driver`
     * Currently driver allows mysql only, that's why this method is private
     * @param string $driver Database driver
     * @return object SchemaManager
     */
    private function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Getter for the property `driver`
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Setter for the property `dbNamespace`
     * @param string $namespace The namespace
     * @return object SchemaManager
     */
    public function setDbNamespace($namespace)
    {
        $this->dbNamespace = $namespace;

        return $this;
    }

    /**
     * Getter for the property `dbNamespace`
     * @return string
     */
    public function getDbNamespace()
    {
        return $this->dbNamespace;
    }

    /**
     * Get default field type for primary key
     * @return array Array of field type options
     */
    private function getPKDefaultType()
    {
        return array(
            'type'      => 'int',
            'autoinc'   => true,
            'null'      => false,
            'unsigned'  => true
        );
    }

    /**
     * Get relationship options with defaults
     * @param array $relation The relationship options
     * @param string $fkTable The FK table
     * @return array  The relationship options with defaults
     */
    private function getRelationOptions($relation, $fkTable = '')
    {
        if (!isset($relation['name'])) {
            $relation['name'] = $fkTable . '_id';
        }

        return $relation + array(
            'unique'  => false,
            'default' => null,
            'cascade' => false
        );
    }

    /**
     * Get field statement for CREATE TABLE
     * @param string $field The field name
     * @param array $definition SchemaManager field definition
     * @param string $collate The collation for the field; if it is null, db collation is used
     * @return string   The field statement
     */
    public function getFieldStatement($field, $definition, $collate = null)
    {
        $type = $this->getVendorFieldType($definition);
        if ($type === null) {
            return '';
        }

        $statement = "`{$field}` {$type}";

        $length = $this->getFieldLength($definition);
        if ($length) {
            $statement .= "($length)";
        }

        if (in_array($definition['type'], array('string', 'char', 'text', 'array', 'json'))) {
            # COLLATE for text fields
            $statement .= ' COLLATE ';
            $statement .= $collate ? $collate : $this->schema['_options']['collate'];
        }

        if (isset($definition['unsigned'])) {
            # unsigned
            $statement .= ' unsigned';
        }

        if (isset($definition['null'])) {
            # true: DEFAULT NULL
            # false: NOT NULL
            $statement .= $definition['null'] ? ' DEFAULT NULL' : ' NOT NULL';
        }

        if (isset($definition['default'])) {
            $statement .= sprintf(" DEFAULT '%d'", (int)$definition['default']);
        }

        if (isset($definition['autoinc']) && $definition['autoinc']) {
            # AUTO_INCREMENT
            $statement .= ' AUTO_INCREMENT';
        }

        return $statement;
    }

    /**
     * Get field type
     * @param array $definition SchemaManager field definition
     * @return string The underlying db field type
     */
    public function getVendorFieldType(&$definition)
    {
        if (!isset(self::$dataTypes[$this->driver][$definition['type']])) {
            # if no data type is defined
            return null;
        }

        $type = self::$dataTypes[$this->driver][$definition['type']];

        if (in_array($definition['type'], array('text', 'blob', 'array', 'json'))) {
            if (isset($definition['length']) && in_array($definition['length'], array('tiny', 'medium', 'long'))) {
                return strtoupper($definition['length']) . $type;
            } else {
                return $definition['type'] == 'blob' ? self::$dataTypes[$this->driver]['blob'] : self::$dataTypes[$this->driver]['text'];
            }
        }

        if ($definition['type'] == 'boolean') {
            # if type is boolean, force unsigned, not null and default 0
            $definition['unsigned'] = true;
            $definition['null'] = false;
            if (!isset($definition['default'])) {
                $definition['default'] = false;
            }
        }

        return $type;
    }

    /**
     * Get field length
     * @param array $definition SchemaManager field definition
     * @return integer  The field length
     */
    public function getFieldLength(&$definition)
    {
        $type = $definition['type'];

        if ($type == 'string' || $type == 'char') {
            $length = 255;
        } elseif ($type == 'int' || $type == 'integer') {
            $length = 11;
        } elseif ($type === 'boolean') {
            $length = 1;
        } elseif (in_array($type, array('text', 'blob', 'array', 'json'))) {
            $length = 0;
        } elseif ($type == 'decimal' || $type == 'float') {
            $length = isset($definition['length']) ? $definition['length'] : 0;
            if (is_array($length) && count($length) == 2) {
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

    /**
     * Get foreign key schema definition
     * @param string $fkTable The reference table name
     * @param array $relation The relationship definition
     * @return array Foreign key schema definition
     */
    protected function getFKField($fkTable, $relation)
    {
        $field = $relation['name'];
        $pkFields = $this->schema['_options']['pk'];

        if (isset($pkFields[$fkTable][$field])) {
            $fkField = $pkFields[$fkTable][$field];
        } else {
            $keys = array_keys($pkFields[$fkTable]);
            $firstPKField = array_shift($keys);
            $fkField = $pkFields[$fkTable][$firstPKField];
        }

        if (isset($fkField['autoinc'])) {
            unset($fkField['autoinc']);
        }

        if ($relation['unique']) {
            $fkField['unique'] = true;
        }

        if ($relation['default'] === null) {
            $fkField['null'] = true;
        } else {
            $fkField['default'] = $relation['default'];
            $fkField['null'] = false;
        }

        return $fkField;
    }

    /**
     * Get foreign key constraint definition
     * @param string $fkTable The reference table name
     * @param array $relation The relationship definition
     * @param array $schema The whole schema definition
     * @return array|null Foreign key constraint definition
     */
    protected function getFKConstraint($fkTable, $relation, $schema = array())
    {
        if ($this->schema['_options']['constraints']) {
            $pkFields = $this->schema['_options']['pk'];
            $field = $relation['name'];
            $refField = $field;

            if (!isset($pkFields[$fkTable][$refField])) {
                $refField = 'id';
            }

            if ($relation['cascade'] === true) {
                $cascade = 'CASCADE';
            } elseif ($relation['cascade'] === null) {
                $cascade = 'SET NULL';
            } else {
                $cascade = 'RESTRICT';
            }

            return array(
                'name'              => 'FK_' . strtoupper(_randomCode(15)),
                'fields'            => $field,
                'reference_table'   => $fkTable,
                'reference_fields'  => $refField,
                'on_delete'         => $cascade,
                'on_update'         => 'NO ACTION'
            );
        }

        return null;
    }

    /**
     * Process schema
     * @return boolean TRUE for success; FALSE for failure
     */
    private function load()
    {
        $schema = $this->schema;
        unset($schema['_options']);

        if (count($schema) == 0) {
            return false;
        }

        # Populate primary key fields
        $this->populatePrimaryKeys($schema);
        # Add ManyToMany tables to the schema
        $constraints = $this->populatePivots($schema);

        $pkFields = $this->getPrimaryKeys();

        $sql = array();
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';

        # Create each table
        foreach ($schema as $table => $def) {
            $fullTableName = db_table($table); # The full table name with prefix
            $createSql = $this->createTableStatement($table, $schema, $pkFields, $constraints);
            if ($createSql) {
                $sql[] = '--';
                $sql[] = '-- Table structure for table `' . $fullTableName . '`';
                $sql[] = '--';
                $sql[] = "DROP TABLE IF EXISTS `{$fullTableName}`;";
                $sql[] = $createSql;
            }
        }

        # Generate FK constraints
        $constraintSql = $this->createConstraintStatements($constraints);
        if ($constraintSql) {
            $sql = array_merge($sql, $constraintSql);
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';

        $this->sqlStatements = $sql;

        # Get the current version
        $versions = $this->checkVersions($schema);
        if (is_array($versions) && count($versions)) {
            $currentVersion = str_replace($this->sqlExtension, '', array_pop($versions));
        } else {
            $currentVersion = 0;
        }

        $this->schema['_options']['version'] = $currentVersion;
        $schema['_options'] = $this->schema['_options'];
        $this->schema = $schema;

        return true;
    }

    /**
     * Export the built schema definition into a file
     * @param string $dbNamespace The namespace for the database
     * @param boolean $backup Create a backup file or not
     * @return boolean TRUE for success; FALSE for failure
     */
    public function build($dbNamespace = null, $backup = false)
    {
        if (!$this->isLoaded()) {
            $this->load();
        }

        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $fileName = self::getSchemaLockFileName($dbNamespace);
        $result = file_put_contents($fileName, serialize($this->schema));
        if ($result) {
            if ($backup) {
                copy($fileName, self::getSchemaLockFileName($dbNamespace, true));
            }

            # Delete the deprecated built files with extension .inc
            $deprecatedFileName = self::getSchemaLockFileName($dbNamespace, false, 'inc');
            if (is_file($deprecatedFileName) && file_exists($deprecatedFileName)) {
                unlink($deprecatedFileName);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Import schema to the database
     * @param string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function import($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        if (!$this->isLoaded()) {
            $this->load();
        }

        if ($this->executeQueries($dbNamespace, $this->sqlStatements)) {
            $this->build($dbNamespace);
            return true;
        }

        return false;
    }

    /**
     * Export sql dump file
     * @param string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function export($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $this->build($dbNamespace);

        if (!count($this->sqlStatements)) {
            return false;
        }

        $dump = '--' . PHP_EOL
            . '-- Generated by PHPLucidFrame ' . _version() . PHP_EOL
            . '-- ' . date('r') . PHP_EOL
            . '--' . PHP_EOL . PHP_EOL
            . implode(PHP_EOL, $this->sqlStatements);

        return file_put_contents(DB . 'generated' . _DS_ . 'schema.' . $dbNamespace . '.sql', $dump) ? true : false;
    }

    /**
     * Update schema to the latest version
     * @param Command $cmd LucidFrame\Console\Command
     * @param string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function update(Command $cmd, $dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $schemaFrom = self::getSchemaLockDefinition($dbNamespace);

        if (!$this->isLoaded()) {
            $this->load();
        }

        $schemaTo = $this->schema;
        $isSchemaChanged = $this->isSchemaChanged($schemaFrom, $schemaTo);
        $versions = $this->checkVersions($schemaFrom);

        if (is_array($versions) && count($versions)) {
            # Migrate to the latest version
            $version = $this->migrate($versions, $schemaFrom, $schemaTo);

            if ($version) {
                # Update build version
                $this->schema['_options']['version'] = $version;
                $this->build($dbNamespace);

                _writeln();
                _writeln('Your schema has been updated.');
            }

            return true;
        }

        if ($versions === 0 || $versions === 1) {
            # if there is no version file or if the schema is up-to-date;
            if ($isSchemaChanged) {
                # but if the schema is changed, get the difference
                $sql = $this->generateSqlFromDiff($schemaFrom, $schemaTo, $cmd);
            } else {
                _writeln();
                _writeln('Your schema is up-to-date.');

                return true;
            }
        }

        if (!empty($sql['up'])) {
            _writeln();
            _writeln('##########');
            foreach ($sql['up'] as $query) {
                _writeln($query);
            }
            _writeln('##########');
            _writeln();
        }

        $dropConstraintSql = $this->dropConstraintStatements($this->getConstraints($schemaFrom));
        $createConstraintSql = $this->createConstraintStatements();

        if (empty($sql['up']) && count($dropConstraintSql) == 0 && count($createConstraintSql) == 0) {
            return false;
        }

        # Confirm before executing the queries
        $statements = array();
        if ($cmd->confirm('Type "y" to execute or type "n" to abort:')) {
            $statements[] = 'SET FOREIGN_KEY_CHECKS = 0;';
            $statements = array_merge($statements, $dropConstraintSql, $sql['up'], $createConstraintSql);
            $statements[] = 'SET FOREIGN_KEY_CHECKS = 1;';

            $noOfQueries = $this->executeQueries($dbNamespace, $statements);
            if (!$noOfQueries) {
                return false;
            }
        } else {
            _writeln('Aborted.');
            return false;
        }

        # Export version sql file
        if ($dbVersion = $this->exportVersionFile($sql['up'], $dbNamespace)) {
            # Build schema
            $this->schema['_options']['version'] = $dbVersion;
            $this->build($dbNamespace);
        } else {
            return false;
        }

        _writeln('--------------------');
        //_writeln('%d queries executed.', $noOfQueries);

        return true;
    }

    /**
     * Find the schema difference and generate SQL file
     * @param Command $cmd LucidFrame\Console\Command
     * @param string $dbNamespace The namespace for the database
     * @return boolean TRUE for SQL file exported; FALSE for no updates
     */
    public function diff(Command $cmd, $dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $schemaFrom = self::getSchemaLockDefinition($dbNamespace);

        if (!$this->isLoaded()) {
            $this->load();
        }

        $schemaTo = $this->schema;
        $isSchemaChanged = $this->isSchemaChanged($schemaFrom, $schemaTo);
        $versions = $this->checkVersions($schemaFrom);

        if (is_array($versions) && count($versions)) {
            return false;
        }

        if ($versions === 0 || $versions === 1) {
            # if there is no version file or if the schema is up-to-date;
            if ($isSchemaChanged) {
                # but if the schema is changed, get the difference
                $sql = $this->generateSqlFromDiff($schemaFrom, $schemaTo, $cmd);
                if ($dbVersion = $this->exportVersionFile($sql['up'], $dbNamespace)) {
                    $versionDir = $this->getVersionDir($dbNamespace);

                    _writeln();
                    _writeln($versionDir . _DS_ . $dbVersion . $this->sqlExtension . ' is exported.');
                    _writeln('Check the file and run `php lucidframe schema:update ' . $dbNamespace . '`');

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Export the SQL file with .sqlc extension in the directory /db/version/{namespace}/
     * @param array $sql Array of SQL statements
     * @param string $dbNamespace The namespace for the database
     * @return mixed The version number on success or FALSE on failure
     */
    private function exportVersionFile(array $sql, $dbNamespace = null)
    {
        if (!count($sql)) {
            return false;
        }

        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        # Export version sql file
        $dbVersion = date('YmdHis');

        $dump = '--' . PHP_EOL
            . '-- Version ' . $dbVersion . PHP_EOL
            . '-- Generated by PHPLucidFrame ' . _version() . PHP_EOL
            . '-- ' . date('r') . PHP_EOL
            . '--' . PHP_EOL . PHP_EOL
            . implode(PHP_EOL . PHP_EOL, $sql);

        $versionDir = $this->getVersionDir($dbNamespace);
        if (file_put_contents($versionDir . _DS_ . $dbVersion . $this->sqlExtension, $dump)) {
            return $dbVersion;
        }

        return false;
    }

    /**
     * Get schema difference and generate SQL statements
     * @param array $schemaFrom Array of the current schema data
     * @param array $schemaTo Array of the updated schema data
     * @param Command $cmd LucidFrame\Console\Command
     * @return array
     */
    public function generateSqlFromDiff($schemaFrom, $schemaTo, Command $cmd)
    {
        $fieldNamesChanged = array();
        $this->columnsRenamed = array();

        $sql = array(
            'up'    => array(),
            'down'  => array(),
        );

        # Detect table renaming
        $this->detectTableRenamings($schemaFrom, $schemaTo);
        if (count($this->tablesRenamed)) {
            _writeln();
            _writeln('Type "y" to rename or type "n" to drop/create for the following tables:');
            _writeln();
        }

        # Get user confirmation for table renaming
        foreach ($this->tablesRenamed as $from => $to) {
            if (!$cmd->confirm('Table renaming from `' . $from . '` to `' . $to . '`:')) {
                unset($this->tablesRenamed[$from]);
            }
        }

        # Detect field renaming
        $this->detectColumnRenamings($schemaFrom, $schemaTo);
        if (count($this->columnsRenamed)) {
            _writeln();
            _writeln('Type "y" to rename or type "n" to drop/create for the following fields:');
            _writeln();
        }

        # Get user confirmation for column renaming
        foreach ($this->columnsRenamed as $from => $to) {
            $fieldFrom = explode('.', $from);
            if (!$cmd->confirm('Field renaming from `' . $fieldFrom[1] . '` to `' . $fieldFrom[0] . '.' . $to . '`:')) {
                unset($this->columnsRenamed[$from]);
            }
        }

        # Detect schema differences and generate SQL statements
        foreach ($schemaFrom as $table => $tableDef) {
            if ($table == '_options') {
                $dbOptions = $table;
                continue;
            }

            $fullTableName = db_table($table);
            $renamedTable = $this->isRenamed($table, $this->tablesRenamed);

            if (isset($schemaTo[$table]) || ($renamedTable && isset($schemaTo[$renamedTable]))) {
                # Existing table
                if ($renamedTable) {
                    # if the table is renamed
                    $table = $renamedTable;
                }

                foreach ($tableDef as $field => $fieldDef) {
                    $collate        = $this->getTableCollation($table, $schemaTo);
                    $oldField       = $field;
                    $renamedField   = $this->isRenamed($table . '.' . $field, $this->columnsRenamed);

                    if (isset($schemaTo[$table][$field]) || ($renamedField && isset($schemaTo[$table][$renamedField]))) {
                        # Existing field
                        if ($renamedField) {
                            $field = $renamedField;
                        }

                        $diff = $fieldDef !== $schemaTo[$table][$field];
                        if ($diff) {
                            # Change field
                            if (in_array($field, self::$relationships)) {
                                continue;
                            }

                            if ($field == 'options') {
                                if (!empty($fieldDef['m:m'])) {
                                    # if it is many-to-many table, skip
                                    continue;
                                }

                                $fromFieldOptions = $fieldDef;
                                $toFieldOptions = $schemaTo[$table][$field];
                                $diffOptions = $this->diffColumns($fromFieldOptions, $toFieldOptions);

                                foreach ($diffOptions['diff'] as $optName => $optValue) {
                                    switch ($optName) {
                                        case 'unique':
                                            // Drop old composite unique indices
                                            if (isset($fromFieldOptions['unique'])) {
                                                foreach ($fromFieldOptions['unique'] as $keyName => $uniqueFields) {
                                                    $sql['up'][] = "ALTER TABLE `{$fullTableName}` DROP INDEX `IDX_$keyName`;";
                                                }
                                            }

                                            if (isset($toFieldOptions['unique'])) {
                                                // Add new composite unique indices
                                                foreach ($toFieldOptions['unique'] as $keyName => $uniqueFields) {
                                                    $sql['up'][] = "ALTER TABLE `{$fullTableName}` ADD UNIQUE `IDX_$keyName` (`" . implode('`,`', $uniqueFields) . "`);";
                                                }
                                            }
                                            break;

                                        case 'engine':
                                            $sql['up'][] = "ALTER TABLE `{$fullTableName}` ENGINE={$toFieldOptions['engine']};";
                                            break;

                                        case 'charset':
                                        case 'collate':
                                            $sql['up'][] = "ALTER TABLE `{$fullTableName}` CONVERT TO CHARACTER SET {$toFieldOptions['charset']} COLLATE {$toFieldOptions['collate']};";
                                            break;
                                    }
                                }

                                continue;
                            }

                            $newField = $field;

                            $sql['up'][] = "ALTER TABLE `{$fullTableName}` CHANGE COLUMN `{$oldField}` " .
                                $this->getFieldStatement($newField, $schemaTo[$table][$newField], $collate) . ';';

                            if (isset($schemaFrom[$table][$oldField]['unique']) && !isset($schemaTo[$table][$newField]['unique'])) {
                                $sql['up'][] = "ALTER TABLE `{$fullTableName}` DROP INDEX `IDX_$oldField`;";
                            } elseif (!isset($schemaFrom[$table][$oldField]['unique']) && isset($schemaTo[$table][$newField]['unique'])) {
                                $sql['up'][] = "ALTER TABLE `{$fullTableName}` ADD UNIQUE `IDX_$newField` (`$newField`);";
                            }

                            $fieldNamesChanged[] = $table . '.' . $oldField;
                            $fieldNamesChanged = array_unique($fieldNamesChanged);
                        } else {
                            if ($renamedField) {
                                $fieldNamesChanged[] = $table . '.' . $renamedField;
                                $sql['up'][] = "ALTER TABLE `{$fullTableName}` CHANGE COLUMN `{$oldField}` " .
                                    $this->getFieldStatement($renamedField, $schemaTo[$table][$renamedField], $collate) . ';';
                            }
                        }
                    } else {
                        # Drop or change field
                        if (in_array($field, array('m:m', '1:m', 'm:1'))) {
                            continue;
                        }

                        if (in_array($table . '.' . $field, $fieldNamesChanged)) {
                            # The field name is already changed, no need to drop it
                            continue;
                        }

                        if ($field == '1:1') {
                            foreach ($fieldDef as $tableOne => $fkFieldInTable) {
                                $sql['up'][] = "ALTER TABLE `{$fullTableName}` DROP COLUMN `{$fkFieldInTable['name']}`;";
                            }

                            continue;
                        }

                        $sql['up'][] = "ALTER TABLE `{$fullTableName}` DROP COLUMN `{$field}`;";
                    }
                }

                # Rename table
                if ($renamedTable) {
                    $sql['up'][] = 'RENAME TABLE `' . $fullTableName . '` TO `' . db_table($renamedTable) . '`;';
                }
            } else {
                # Drop table
                $sql['up'][] = "DROP TABLE IF EXISTS `{$fullTableName}`;";
            }
        }

        $pkFields = $this->getPrimaryKeys();
        $constraints = $this->getConstraints();
        foreach ($schemaTo as $table => $tableDef) {
            if ($table == '_options') {
                $dbOptions = $table;
                continue;
            }

            $collate = $this->getTableCollation($table, $schemaTo);
            $fullTableName = db_table($table);
            $tableFrom = $table;
            $fieldBefore = '';

            if (!isset($schemaFrom[$table])) {
                $oldTable = array_search($table, $this->tablesRenamed);
                if ($oldTable === false) {
                    # Create a new table
                    $createSql = trim($this->createTableStatement($table, $schemaTo, $pkFields, $constraints));
                    if ($createSql) {
                        $sql['up'][] = $createSql;
                    }
                    # if new table, no need to lookup field changes and then continue the next table
                    continue;
                } else {
                    $tableFrom = $oldTable;
                }
            }

            # Add new fields for existing table
            foreach ($tableDef as $field => $fieldDef) {
                if (in_array($field, array_merge(SchemaManager::$relationships, array('options')))) {
                    continue;
                }

                if (!isset($schemaFrom[$tableFrom][$field]) && array_search($table . '.' . $field, $fieldNamesChanged) === false) {
                    # Add a new field
                    $alterSql = "ALTER TABLE `{$fullTableName}` ADD COLUMN ";
                    $alterSql .= $this->getFieldStatement($field, $fieldDef, $collate);
                    if ($fieldBefore && $field != 'created') {
                        $alterSql .= " AFTER `{$fieldBefore}`";
                    }
                    $alterSql .= ';';
                    $sql['up'][] = $alterSql;
                }

                $fieldBefore = $field;
            }
        }

        return $sql;
    }

    /**
     * Migrate db to the latest version
     * @param array $versions Array of versions (older to newer)
     * @param array $schemaFrom Array of the current schema data
     * @param array $schemaTo Array of the updated schema data
     * @param bool $verbose Output in console or not
     * @return string|bool
     */
    public function migrate(array $versions, array $schemaFrom, array $schemaTo, $verbose = true)
    {
        # Drop all foreign key constraints from the old schema
        if ($dropConstraintSql = $this->dropConstraintStatements($this->getConstraints($schemaFrom))) {
            $this->executeQueries($this->dbNamespace, $dropConstraintSql);
        }

        if ($verbose) {
            _writeln();
        }

        $version = false;
        $noOfQueries = 0;
        foreach ($versions as $verFile) {
            $version = str_replace($this->sqlExtension, '', $verFile);

            if ($verbose) {
                _writeln('Executing ' . $version);
            }

            $sql = file_get_contents(DB . 'version' . _DS_ . $this->dbNamespace . _DS_ . $verFile);
            if (empty($sql)) {
                if ($verbose) {
                    _writeln('No sql statements executed.');
                }

                return false;
            }

            $sqls = explode(PHP_EOL, $sql);
            $sql = array_filter($sqls, function($line) {
                $line = trim($line);
                return !empty($line) && strpos($line, '--') === false;
            });

            if (empty($sql)) {
                if ($verbose) {
                    _writeln('No sql statements executed.');
                }

                return false;
            }

            $executed = $this->executeQueries($this->dbNamespace, $sql);
            if (!$executed) {
                return false;
            }

            $noOfQueries += $executed;
            if ($verbose) {
                _writeln();
            }
        }

        # Re-create all foreign key constraints from the new schema
        if ($createConstraintSql = $this->createConstraintStatements($this->getConstraints($schemaTo))) {
            $this->executeQueries($this->dbNamespace, $createConstraintSql);
        }

        return $version;
    }

    /**
     * Execute batch queries
     *
     * @param string $dbNamespace The namespace for the database
     * @param array $queries Array of SQL statements
     * @return boolean  TRUE for success; FALSE for failure
     */
    private function executeQueries($dbNamespace, $queries)
    {
        if (!count($queries)) {
            return false;
        }

        if ($this->dbNamespace !== $dbNamespace) {
            db_switch($dbNamespace);
        }

        db_transaction();

        $count = 0;
        $error = false;
        foreach ($queries as $sql) {
            $sql = trim($sql);

            if (empty($sql)) {
                continue;
            }

            if (substr($sql, 0, 2) == '--') {
                continue;
            }

            if (!db_query($sql)) {
                $error = true;
                break;
            }

            $count++;
        }

        if ($error) {
            db_rollback();
        } else {
            db_commit();
        }

        if ($this->dbNamespace !== $dbNamespace) {
            # back to default db
            db_switch($this->dbNamespace);
        }

        if ($error == true) {
            return false;
        } else {
            return $count;
        }
    }

    /**
     * Check if schema changed
     * @param array $from The last schema
     * @param array $to The changed schema
     * @return bool TRUE if the schema is changed, otherwise FALSE
     */
    public function isSchemaChanged(array $from, array $to)
    {
        if (isset($from['_options']['version'])) {
            unset($from['_options']['version']);
        }

        if (isset($from['_options']['fkConstraints'])) {
            unset($from['_options']['fkConstraints']);
        }

        if (isset($to['_options']['version'])) {
            unset($to['_options']['version']);
        }

        if (isset($to['_options']['fkConstraints'])) {
            unset($to['_options']['fkConstraints']);
        }

        return $from != $to;
    }

    /**
     * Get the current db version
     * @return integer The version number
     */
    public function getCurrentVersion()
    {
        $version = 0;
        if ($schema = self::getSchemaLockDefinition($this->dbNamespace)) {
            $version = isset($schema['_options']['version']) ? $schema['_options']['version'] : 0;
        }

        return $version;
    }

    /**
     * Check db version files in the version directory against the current version in $schema[_options][version]
     * @param array $schema The schema to check in
     * @return mixed
     *  0 if there is no version file;
     *  1 if the schema is up-to-date;
     *  ARRAY if there is version file to migrate
     */
    public function checkVersions(array $schema)
    {
        # Check if there is version files in the version directory
        $versionDir = DB . 'version' . _DS_ . $this->dbNamespace;
        if (!is_dir($versionDir)) {
            return 0;
        }

        $files = scandir($versionDir);
        rsort($files); # sort file name by descending

        # Check if the current schema version is up-to-date
        $lastVersion = 0;
        if (isset($schema['_options']['version'])) {
            $lastVersion = $schema['_options']['version'];
            if ($lastVersion . $this->sqlExtension == $files[0]) {
                return 1;
            }
        }

        # Filter all version greater than the last version
        $manager = $this;
        $files = array_filter($files, function ($fileName) use ($lastVersion, $manager) {
            if (preg_match('/\d{14}\\' . $manager->sqlExtension . '/', $fileName)) {
                if ($lastVersion == 0) {
                    return true;
                }

                $version = str_replace($manager->sqlExtension, '', $fileName);
                if ($version > $lastVersion) {
                    return true;
                }
            }

            return false;
        });

        if (count($files)) {
            sort($files);
            return $files;
        }

        return 0;
    }

    /**
     * Check if the schema is parsed and fully loaded
     * @return boolean TRUE/FALSE
     */
    public function isLoaded()
    {
        return isset($this->schema['_options']['pk']);
    }

    /**
     * Check if a table or field is renamed
     *
     * @param string $needle The table or field name
     * @param array $haystack Array of renamed fields or tables
     * @return mixed The renamed table name or field name or false
     */
    protected function isRenamed($needle, $haystack)
    {
        if (isset($haystack[$needle])) {
            return $haystack[$needle];
        } else {
            return false;
        }
    }

    /**
     * Check if the table exists
     * @param string $table The table name
     * @return boolean TRUE if the table exists, otherwise FALSE
     */
    public function hasTable($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table]);
    }

    /**
     * Check if a field exists
     * @param string $table The table name
     * @param string $field The field name
     * @return boolean TRUE if the table exists, otherwise FALSE
     */
    public function hasField($table, $field)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table][$field]);
    }

    /**
     * Check if the table has the timestamp fields or not
     * @param string $table The table name without prefix
     * @return boolean TRUE if the table has the timestamp fields, otherwise FALSE
     */
    public function hasTimestamps($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return (isset($this->schema[$table]['options']['timestamps']) && $this->schema[$table]['options']['timestamps']) ? true : false;
    }

    /**
     * Check if the table has the slug field or not
     * @param string $table The table name without prefix
     * @return boolean TRUE if the table has the slug field, otherwise FALSE
     */
    public function hasSlug($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table]['slug']) ? true : false;
    }

    /**
     * Get data type of the field
     * @param string $table The table name
     * @param string $field The field name in the table
     * @return string The data type or null if there is no field
     */
    public function getFieldType($table, $field)
    {
        $table = db_table($table);

        if ($this->hasField($table, $field)) {
            return $this->schema[$table][$field]['type'];
        }

        return null;
    }

    /**
     * Get schema options if it is defined
     * otherwise return the default options
     *
     * @return array
     */
    protected function getOptions()
    {
        if (isset($this->schema['_options'])) {
            $options = $this->schema['_options'] + $this->defaultOptions;
        } else {
            $options = $this->defaultOptions;
        }

        return $options;
    }

    /**
     * Get table options if it is defined
     * otherwise return the default options
     *
     * @param array $tableDef The table definition
     * @return array
     */
    protected function getTableOptions($tableDef)
    {
        $options = $this->getOptions();

        if (isset($options['pk'])) {
            unset($options['pk']);
        }

        if (isset($options['fkConstraints'])) {
            unset($options['fkConstraints']);
        }

        if (isset($tableDef['options'])) {
            $tableDef['options'] += $options;
        } else {
            $tableDef['options'] = $options;
        }

        return $tableDef['options'];
    }

    /**
     * Populate primary keys acccording to the schema defined
     * @param array $schema The database schema
     * @return array
     */
    public function populatePrimaryKeys(&$schema)
    {
        # Populate primary key fields
        $pkFields = array();
        foreach ($schema as $table => $def) {
            $def['options'] = $this->getTableOptions($def);

            if ($def['options']['timestamps']) {
                $def['created'] = array('type' => 'datetime', 'null' => true);
                $def['updated'] = array('type' => 'datetime', 'null' => true);
                $def['deleted'] = array('type' => 'datetime', 'null' => true);
            }

            $schema[$table] = $def;

            # PK Field(s)
            $pkFields[$table] = array();
            if (isset($def['options']['pk'])) {
                foreach ($def['options']['pk'] as $pk) {
                    if (isset($def[$pk])) {
                        # user-defined PK field type
                        $pkFields[$table][$pk] = $def[$pk];
                    } else {
                        # default PK field type
                        $pkFields[$table][$pk] = $this->getPKDefaultType();
                    }
                }
            } else {
                $pkFields[$table]['id'] = $this->getPKDefaultType();
            }
        }

        $this->setPrimaryKeys($pkFields);

        return $pkFields;
    }

    /**
     * Populate pivot tables (joint tables fo many-to-many relationship) into the schema
     * @param array $schema The database schema
     * @return array Array of constraints
     */
    public function populatePivots(&$schema)
    {
        $constraints = array();
        $pkFields = $this->getPrimaryKeys();

        $manyToMany = array_filter($schema, function ($def) {
            return isset($def['m:m']);
        });

        foreach ($manyToMany as $table => $def) {
            foreach ($def['m:m'] as $fkTable => $joint) {
                if (!empty($joint['table']) && isset($schema[$joint['table']])) {
                    # if the joint table has already been defined
                    continue;
                }

                if (isset($schema[$table . '_to_' . $fkTable]) || isset($schema[$fkTable . '_to_' . $table])) {
                    # if the joint table has already been defined
                    continue;
                }

                if (isset($schema[$fkTable]['m:m'][$table])) {
                    if (empty($joint['table']) && !empty($schema[$fkTable]['m:m'][$table]['table'])) {
                        $joint['table'] = $schema[$fkTable]['m:m'][$table]['table'];
                    }

                    # table1_to_table2
                    $jointTable = !empty($joint['table']) ? $joint['table'] : $table . '_to_' . $fkTable;
                    $schema[$jointTable]['options'] = array(
                            'pk' => array(),
                            'timestamps' => false, # no need timestamp fields for many-to-many table
                            'm:m' => true
                        ) + $this->defaultOptions;

                    # table1.field
                    $relation = $this->getRelationOptions($joint, $table);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($table, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($table, $relation, $schema);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }

                    # table2.field
                    $relation = $this->getRelationOptions($schema[$fkTable]['m:m'][$table], $fkTable);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($fkTable, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($fkTable, $relation, $schema);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }
                }
            }
        }

        $this->setPrimaryKeys($pkFields);
        $this->setConstraints($constraints);

        return $constraints;
    }

    /**
     * Generate CREATE TABLE SQL
     * @param string $table The new table name
     * @param array $schema The database schema
     * @param array $pkFields Array of PK fields
     * @param array $constraints Array of FK constraints
     * @return string
     */
    public function createTableStatement($table, &$schema, &$pkFields, &$constraints)
    {
        if (!isset($schema[$table])) {
            return null;
        }

        $def            = $schema[$table]; # The table definition
        $fullTableName  = db_table($table); # The full table name with prefix
        $fkFields       = array(); # Populate foreign key fields

        # OneToMany
        if (isset($def['m:1']) && is_array($def['m:1'])) {
            foreach ($def['m:1'] as $fkTable) {
                if (isset($schema[$fkTable]['1:m'][$table]) || array_search($table, $schema[$fkTable]['1:m']) !== false) {
                    $relationOptions = array();
                    if (isset($schema[$fkTable]['1:m'][$table])) {
                        $relationOptions = $schema[$fkTable]['1:m'][$table];
                    }

                    $relation = $this->getRelationOptions($relationOptions, $fkTable);
                    $field = $relation['name'];
                    # Get FK field definition
                    $fkFields[$field] = $this->getFKField($fkTable, $relation);
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($fkTable, $relation, $schema);
                    if ($constraint) {
                        $constraints[$table][$field] = $constraint;
                    }
                }
            }
        }

        # OneToOne
        if (isset($def['1:1']) && is_array($def['1:1'])) {
            foreach ($def['1:1'] as $fkTable => $fk) {
                $relation = $this->getRelationOptions($fk, $fkTable);
                $field = $relation['name'];
                # Get FK field definition
                $fkFields[$field] = $this->getFKField($fkTable, $relation);
                # Get FK constraints
                $constraint = $this->getFKConstraint($fkTable, $relation, $schema);
                if ($constraint) {
                    $constraints[$table][$field] = $constraint;
                }
            }
        }

        $this->setConstraints($constraints);

        $def = array_merge($pkFields[$table], $fkFields, $def);
        $schema[$table] = $def;

        # ManyToMany table FK indexes
        if (isset($def['options']['m:m']) && $def['options']['m:m']) {
            $jointTable = $table;
            foreach ($schema[$jointTable] as $field => $rule) {
                if ($field == 'options') {
                    continue;
                }
                $fkFields[$field] = $rule;
            }
        }

        $options = $this->getTableOptions($def);
        $def['options'] = $options;

        # CREATE TABLE Statement
        $sql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` (" . PHP_EOL;

        # loop the fields
        $autoinc = false;
        foreach ($def as $name => $rule) {
            # Skip for relationship and option definitions
            if (in_array($name, self::$relationships) || $name == 'options') {
                continue;
            }

            $sql .= '  ' . $this->getFieldStatement($name, $rule, $this->getTableCollation($name, $schema)) . ',' . PHP_EOL;

            # if there is any unique index
            if (isset($rule['unique']) && $rule['unique']) {
                $fkFields[$name] = $rule;
            }

            if (isset($rule['autoinc']) && $rule['autoinc']) {
                $autoinc = true;
            }
        }

        # Indexes
        if (count($fkFields)) {
            foreach (array_keys($fkFields) as $name) {
                if (isset($fkFields[$name]['unique']) && $fkFields[$name]['unique']) {
                    $sql .= '  UNIQUE KEY';
                } else {
                    $sql .= '  KEY';
                }
                $sql .= " `IDX_$name` (`$name`)," . PHP_EOL;
            }
        }

        // Unique indexes for composite unique fields
        if (isset($options['unique']) && is_array($options['unique'])) {
            foreach ($options['unique'] as $keyName => $uniqueFields) {
                $sql .= '  UNIQUE KEY';
                $sql .= " `IDX_$keyName` (`" . implode('`,`', $uniqueFields) . "`)," . PHP_EOL;
            }
        }

        # Primary key indexes
        if (isset($pkFields[$table])) {
            $sql .= '  PRIMARY KEY (`' . implode('`,`', array_keys($pkFields[$table])) . '`)' . PHP_EOL;
        }

        $sql .= ')';
        $sql .= ' ENGINE=' . $options['engine'];
        $sql .= ' DEFAULT CHARSET=' . $options['charset'];
        $sql .= ' COLLATE=' . $options['collate'];

        if ($autoinc) {
            $sql .= ' AUTO_INCREMENT=1';
        }

        $sql .= ';' . PHP_EOL;

        return $sql;
    }

    /**
     * Generate foreign key constraints SQL statements
     * @param array $constraints Array of populated constraints
     * @return array Array of SQL statements
     */
    public function createConstraintStatements($constraints = null)
    {
        if ($constraints === null) {
            $constraints = $this->getConstraints();
        }

        $options = $this->getOptions();
        $sql = array();
        # FK constraints
        if ($options['constraints']) {
            foreach ($constraints as $table => $constraint) {
                $fullTableName = db_table($table);
                $constraintSql = "ALTER TABLE `{$fullTableName}`" . PHP_EOL;
                $statement = array();
                foreach ($constraint as $field => $rule) {
                    $statement[] = "  ADD CONSTRAINT `{$rule['name']}` FOREIGN KEY (`{$rule['fields']}`)"
                        . " REFERENCES `{$rule['reference_table']}` (`{$rule['reference_fields']}`)"
                        . " ON DELETE {$rule['on_delete']}"
                        . " ON UPDATE {$rule['on_update']}";
                }
                $constraintSql .= implode(',' . PHP_EOL, $statement) . ';' . PHP_EOL;
                $sql[] = $constraintSql;
            }
        }

        return count($sql) ? $sql : null;
    }

    /**
     * Generate DROP foreign key constraints SQL statements
     * @param array $constraints Array of populated constraints
     * @return array Array of SQL statements
     */
    public function dropConstraintStatements($constraints = null)
    {
        if ($constraints === null) {
            $constraints = $this->getConstraints();
        }

        $options = $this->getOptions();
        $sql = array();
        # FK constraints
        if ($options['constraints']) {
            $tables = array_keys($constraints);
            foreach ($tables as $table) {
                $fullTableName = db_table($table);
                $result = db_query("SHOW CREATE TABLE `{$fullTableName}`");
                if ($result && $row = db_fetchArray($result)) {
                    $fKeys = array();
                    if (preg_match_all('/CONSTRAINT `(FK_[A-Z0-9]+)` FOREIGN KEY/', $row[1], $matches)) {
                        foreach ($matches[1] as $constraintName) {
                            $fKeys[] = " DROP FOREIGN KEY `{$constraintName}`";
                        }
                    }

                    if (count($fKeys)) {
                        $sql[] = "ALTER TABLE `{$fullTableName}`" . PHP_EOL . implode(',' . PHP_EOL, $fKeys) . ';';
                    }
                }
            }
        }

        return count($sql) ? $sql : null;
    }

    /**
     * Set the populated primary keys into the schema database options
     * @param array $pkFields Array of primary keys
     * @return void
     */
    public function setPrimaryKeys($pkFields)
    {
        $this->schema['_options']['pk'] = $pkFields;
    }

    /**
     * Get the populated primary keys from the schema database options
     * @param array $schema The schema definition
     * @return array Array of primary keys
     */
    public function getPrimaryKeys($schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return !empty($schema['_options']['pk']) ? $schema['_options']['pk'] : array();
    }

    /**
     * Set the populated foreign key constraints into the schema database options
     * @param array $constraints Array of FK constraints
     * @return void
     */
    public function setConstraints($constraints)
    {
        $this->schema['_options']['fkConstraints'] = $constraints;
    }

    /**
     * Get the populated foreign key constraints from the schema database options
     * @param array $schema The schema definition
     * @return array Array of FK constraints
     */
    public function getConstraints($schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return !empty($schema['_options']['fkConstraints']) ? $schema['_options']['fkConstraints'] : array();
    }

    /**
     * Return table collation from the schema definition
     * @param string $table The table name
     * @param array $schema The schema definition (optional)
     * @return string
     */
    public function getTableCollation($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return isset($schema[$table]['options']['collate']) ? $schema[$table]['options']['collate'] : null;
    }

    /**
     * Try to find columns that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @param string $needle The table or field name
     * @param array $from The table or field definition to check difference against $to
     * @param array $to The table or field definition to check difference against $from
     * @param string $table The table name or null
     *
     * @return mixed The similar name or false
     */
    private function getSimilarity($needle, array $from, array $to, $table = null)
    {
        if (in_array($needle, array_merge(SchemaManager::$relationships, array('options')))) {
            return false;
        }

        if ($table) {
            $compared = 'field';
            if (isset($this->droppedColumns[$table]) && !in_array($needle, $this->droppedColumns[$table])) {
                return false;
            }

            $haystack = &$this->addedColumns[$table];
        } else {
            $compared = 'table';
            if (!in_array($needle, $this->droppedTables)) {
                return false;
            }

            $haystack = &$this->addedTables;
        }

        if (!is_array($haystack) || in_array($needle, $haystack)) {
            return false;
        }

        $similarity = array();
        $matchingText = array();
        $matchingMetaphone = array();

        foreach ($haystack as $i => $name) {
            if ($needle === $name) {
                return false;
            }

            $scores = array();
            $matching[$name] = array();

            $changes = 100;
            if ($compared == 'table') {
                # Table definition comparison
                $diff = $this->diffTables($from[$needle], $to[$name]);
                if ($diff['changes'] == 0) {
                    unset($haystack[$i]);
                    return $name;
                }
                $changes = $diff['changes'];
            } else {
                # Field definition comparison
                $diff = $this->diffColumns($from[$needle], $to[$name]);
                $changes = $diff['changes'];
            }
            $percentChanges = 100 - $changes;

            # Check similar chars
            similar_text(strtolower($needle), strtolower($name), $percent1);
            $matchingText[$name] = (int)round($percent1);

            # Check sound
            $metaphone1 = metaphone(strtolower($needle));
            $metaphone2 = metaphone(strtolower($name));
            similar_text($metaphone1, $metaphone2, $percent2);
            $matchingMetaphone[$name] = (int)round($percent2);

            $percentByTwo = round(($percent1 + $percent2) / 2);
            $percent1 = round($percent1);

            if ($percent1 < 100 && $percent2 == 100) {
                # not similar_text, but same sound
                $scores[] = $percent1 + $percentChanges;
            }

            if ($percentByTwo >= 95 && $percentByTwo <= 100) {
                # similar_text + metaphone
                $scores[] = $percentByTwo + $percentChanges;
            }

            if ($percent1 > 50 && $percent1 < 100) {
                # similar_text only
                $scores[] = $percent1 + $percentChanges;
            }

            if ($compared == 'field' && strpos(strtolower($needle), 'id') !== false && strpos(strtolower($name), 'id') !== false) {
                # id field
                $scores[] = 75 + $percentChanges;
            }

            if (count($scores)) {
                arsort($scores);
                $similarity[$name] = (int)round(array_shift($scores));
            }
        }

        if (count($similarity) == 0) {
            return false;
        }

        arsort($similarity);
        arsort($matchingText);
        arsort($matchingMetaphone);

        foreach (array($similarity, $matchingText, $matchingMetaphone) as $i => $matchings) {
            $dups = array_count_values($matchings);
            if (array_pop($dups) == 1 || $i == 2) {
                $candidate = array_keys($matchings);
                $topSimilarity = array_shift($candidate);
                break;
            }
        }

        unset($haystack[array_search($topSimilarity, $haystack)]);

        return $topSimilarity;
    }

    /**
     * Try to find out dropped tables
     * @param array $schemaFrom The schema definion from
     * @param array $schemaTo The schema definion to
     * @return void
     */
    private function detectDroppedTables(array $schemaFrom, array $schemaTo)
    {
        # Find out dropped tables and columns
        foreach ($schemaFrom as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            if (!isset($schemaTo[$table])) {
                $this->droppedTables[] = $table;
                continue;
            }
        }
    }

    /**
     * Try to find out possible new tables
     * @param array $schemaFrom The schema definion from
     * @param array $schemaTo The schema definion to
     * @return void
     */
    private function detectAddedTables(array $schemaFrom, array $schemaTo)
    {
        # Find out possible new tables and columns
        foreach ($schemaTo as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            if (!isset($schemaFrom[$table])) {
                $this->addedTables[] = $table;
                continue;
            }
        }
    }

    /**
     * Try to find out dropped tables
     * @param array $schemaFrom The schema definion from
     * @param array $schemaTo The schema definion to
     * @return void
     */
    private function detectDroppedColumns(array $schemaFrom, array $schemaTo)
    {
        # Find out dropped tables and columns
        foreach ($schemaFrom as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            # Add new fields for existing table
            foreach ($tableDef as $field => $fieldDef) {
                if (in_array($field, array_merge(SchemaManager::$relationships, array('options')))) {
                    continue;
                }

                if (!isset($schemaTo[$table][$field])) {
                    # Add a new field
                    $this->droppedColumns[$table][] = $field;
                }
            }
        }
    }

    /**
     * Try to find out possible new columns
     * @param array $schemaFrom The schema definition from
     * @param array $schemaTo The schema definition to
     * @return void
     */
    private function detectAddedColumns(array $schemaFrom, array $schemaTo)
    {
        # Find out possible new tables and columns
        foreach ($schemaTo as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            # Add new fields for existing table
            foreach ($tableDef as $field => $fieldDef) {
                if (in_array($field, array_merge(SchemaManager::$relationships, array('options')))) {
                    continue;
                }

                if (!isset($schemaFrom[$table][$field])) {
                    # Add a new field
                    $this->addedColumns[$table][] = $field;
                }
            }
        }
    }


    /**
     * Try to find tables and columns that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @param array $schemaFrom The schema definition from
     * @param array $schemaTo The schema definition to
     * @return void
     */
    private function detectTableRenamings(array $schemaFrom, array $schemaTo)
    {
        $this->detectDroppedTables($schemaFrom, $schemaTo);
        $this->detectAddedTables($schemaFrom, $schemaTo);

        # Detect table and column renaming
        foreach ($schemaFrom as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            $renamedTable = $this->getSimilarity($table, $schemaFrom, $schemaTo);
            if ($renamedTable) {
                $this->tablesRenamed[$table] = $renamedTable;
            }
        }
    }

    /**
     * Try to find tables and columns that only changed their name, rename operations maybe cheaper than add/drop
     * however ambiguities between different possibilities should not lead to renaming at all.
     *
     * @param array $schemaFrom The schema definion from
     * @param array $schemaTo The schema definion to
     * @return void
     */
    private function detectColumnRenamings(array $schemaFrom, array $schemaTo)
    {
        $this->detectDroppedColumns($schemaFrom, $schemaTo);
        $this->detectAddedColumns($schemaFrom, $schemaTo);

        # Detect table and column renaming
        foreach ($schemaFrom as $table => $tableDef) {
            if ($table == '_options') {
                continue;
            }

            $originalTable = $table;
            $renamedTable = null;
            if (isset($this->tablesRenamed[$table])) {
                $renamedTable = $this->tablesRenamed[$table];
            }

            if (isset($schemaTo[$table]) || ($renamedTable && isset($schemaTo[$renamedTable]))) {
                if ($renamedTable) {
                    $table = $renamedTable;
                }

                foreach ($tableDef as $field => $fieldDef) {
                    if (in_array($field, array_merge(SchemaManager::$relationships, array('options')))) {
                        continue;
                    }

                    if (!isset($schemaTo[$table][$field])) {
                        # Check if there is similar field name
                        $renamedCol = $this->getSimilarity($field, $tableDef, $schemaTo[$table], $table);
                        if ($renamedCol) {
                            $this->columnsRenamed[$table . '.' . $field] = $renamedCol;
                        }
                    }
                }
            }
        }
    }

    /**
     * Computes the difference of two arrays similar to the native function `array_diff`
     * which can't be used for multi-dimensional arrays
     *
     * @param array $from The array to compare from
     * @param array $to An array to compare against
     *
     * @return array The array with two keys:
     *  `diff` - an array containing all the entries from $from that are not present in the other array $to.
     *  `changes` - number of changes; the more differences, the higher numbers; 0 means the two arrays are identical
     */
    private function diffColumns(array $from, array $to)
    {
        $changes = 0;
        $diff = array();
        foreach ($from as $key => $value) {
            if (!isset($to[$key])) {
                $diff[$key] = $value;
                $changes++;
                continue;
            }

            if (isset($to[$key]) && $from[$key] != $to[$key]) {
                $diff[$key] = $to[$key];
                $changes++;
                continue;
            }
        }

        $fromKeys = array_keys($from);
        $toKeys = array_keys($to);
        $diffKeys = array_diff($toKeys, $fromKeys);
        foreach ($diffKeys as $key) {
            $diff[$key] = $to[$key];
            $changes++;
        }

        return array(
            'diff' => $diff,
            'changes' => $changes,
        );
    }

    /**
     * Computes the difference of two arrays similar to the native function `array_diff`
     * which can't be used for multi-dimensional arrays
     *
     * @param array $from The array to compare from
     * @param array $to An array to compare against
     *
     * @return array The array with two keys:
     *  `diff` - an array containing all the entries from $from that are not present in the other array $to.
     *  `changes` - number of changes; the more differences, the higher numbers; 0 means the two arrays are identical
     */
    private function diffTables(array $from, array $to)
    {
        $changes = 0;
        $diff = array();
        foreach ($from as $key => $value) {
            if (!isset($to[$key]) || (isset($to[$key]) && $from[$key] != $to[$key])) {
                $diff[$key] = $value;
                $changes++;
            }
        }

        return array(
            'diff' => $diff,
            'changes' => $changes,
        );
    }

    /**
     * Get the version directory path
     * @param string $dbNamespace The namespace for the database
     * @return string The full directory path
     */
    private function getVersionDir($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $versionDir = DB . 'version' . _DS_ . $dbNamespace;
        if (!is_dir($versionDir)) {
            mkdir($versionDir, 777, true);
        }

        return $versionDir;
    }

    /**
     * Get schema definition from the built schema file
     * @param string $dbNamespace The namespace for the database
     * @return array The schema definition; NULL when there is no file
     */
    public static function getSchemaLockDefinition($dbNamespace = null)
    {
        $extensions = array('lock', 'inc'); # @TODO: Remove inc for backward compatibility support

        foreach ($extensions as $ext) {
            $file = DB . _DS_ . 'build' . _DS_ . 'schema';
            if ($dbNamespace) {
                $file .= '.' . $dbNamespace;
            }
            $file .= '.' . $ext;

            if (!(is_file($file) && file_exists($file))) {
                continue;
            }

            if ($ext === 'lock') {
                return unserialize(file_get_contents($file));
            } else {
                return include $file;
            }
        }

        return null;
    }

    /**
     * Get schema lock file name
     * @param string $dbNamespace The namespace for the database
     * @param boolean $backupFileName If true, ~ will be prefixed in the file name
     * @param string $ext The file extension, default to .lock
     * @return string The file name with full path
     */
    public static function getSchemaLockFileName($dbNamespace = null, $backupFileName = false, $ext = 'lock')
    {
        if (!in_array($ext, array('lock', 'inc'))) {
            # @TODO: Remove inc for backward compatibility support
            $ext = 'lock';
        }

        $file = DB . _DS_ . 'build' . _DS_;

        if ($backupFileName) {
            $file .= '~';
        }

        $file .= 'schema';

        if ($dbNamespace) {
            $file .= '.' . $dbNamespace;
        }

        $file .= '.' . $ext;

        return $file;
    }
}
