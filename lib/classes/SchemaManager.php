<?php
/**
 * This file is part of the PHPLucidFrame library.
 * SchemaManager manages your database schema.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

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
    private static $relationships = array('1:m', 'm:1', 'm:m', '1:1');
    /** @var string The namespace for the database */
    private $dbNamespace = 'default';
    /** @var array The array of generated SQL statements */
    private $sqlStatements;

    /**
     * Constructor
     * @param array $schema The array of schema definition
     */
    public function __construct($schema = array())
    {
        $this->defaultOptions = array(
            'timestamps'    => true,
            'constraints'   => true,
            'charset'       => 'utf8',
            'collate'       => 'utf8_general_ci',
            'engine'        => 'InnoDB',
        );

        $this->setSchema($schema);
    }

    /**
     * Setter for the property `schema`
     * @param  array $schema The array of schema definition
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
     * @param  string $driver Database driver
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
     * Get default field type for primary key
     * @return array Array of field type options
     */
    private function getPKDefaultType()
    {
        return array('type' => 'integer', 'autoinc' => true, 'null' => false, 'unsigned' => true);
    }

    /**
     * Get relationship options with defaults
     * @param  array  $relation The relationship options
     * @param  string $fkTable The FK table
     * @return array  The relationship options with defaults
     */
    private function getRelationOptions($relation, $fkTable = '')
    {
        if (!isset($relation['name'])) {
            $relation['name'] = $fkTable.'_id';
        }

        return $relation + array(
            'unique'  => false,
            'default' => null,
            'cascade' => false
        );
    }

    /**
     * Get field statement for CREATE TABLE
     * @param  string   $field        The field name
     * @param  array    $definition   SchemaManager field definition
     * @param  string   $collate      The collation for the field; if it is null, db collation is used
     * @return string   The field statement
     */
    public function getFieldStatement($field, $definition, $collate = null)
    {
        $type = $this->getFieldType($definition);
        if ($type === null) {
            return '';
        }

        $statement = "`{$field}` {$type}";

        $length = $this->getFieldLength($definition);
        if ($length) {
            $statement .= "($length)";
        }

        if (in_array($definition['type'], array('string', 'text', 'array', 'json'))) {
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
            $statement .= sprintf(" DEFAULT '%d'", (int) $definition['default']);
        }

        if (isset($definition['autoinc']) && $definition['autoinc']) {
            # AUTO_INCREMENT
            $statement .= ' AUTO_INCREMENT';
        }

        return $statement;
    }

    /**
     * Get field type
     * @param  array  &$definition SchemaManager field definition
     * @return string The underlying db field type
     */
    public function getFieldType(&$definition)
    {
        if (!isset(self::$dataTypes[$this->driver][$definition['type']])) {
            # if no data type is defined
            return null;
        }

        $type = self::$dataTypes[$this->driver][$definition['type']];

        if (in_array($definition['type'], array('text', 'blob', 'array', 'json'))) {
            if (isset($definition['length']) && in_array($definition['length'], array('tiny', 'medium', 'long'))) {
                return strtoupper($definition['length']).$type;
            } else {
                return $definition['type'] == 'blob' ? self::$dataTypes[$this->driver]['blob'] : self::$dataTypes[$this->driver]['text'];
            }
        }

        if ($definition['type'] == 'boolean') {
            # if type is boolean, force unsigned, not null and default 0
            $definition['unsigned'] = true;
            $definition['null']     = false;
            if (!isset($definition['default'])) {
                $definition['default'] = false;
            }
        }

        return $type;
    }

    /**
     * Get field length
     * @param  array    &$definition SchemaManager field definition
     * @return integer  The field length
     */
    public function getFieldLength(&$definition)
    {
        $type = $definition['type'];

        if ($type == 'string') {
            $length = 255;
        } elseif ($type == 'int' || $type == 'integer') {
            $length = 11;
        } elseif ($type === 'boolean') {
            $length = 1;
        } elseif (in_array($type, array('text', 'blob', 'array', 'json'))) {
            $length = 0;
        } elseif ($type == 'decimal' && $type == 'float') {
            $length = isset($definition['length']) ? $definition['length'] : 0;
            $length = is_array($length) ? "$length[0], $length[1]" : '0, 0';
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
     * @param  string $table    The table where the FK field will be added
     * @param  string $fkTable  The reference table name
     * @param  array  $relation The relationship definition
     * @return array Foreign key schema definition
     */
    protected function getFKField($table, $fkTable, $relation)
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
     * @param  string $table    The table where the FK field will be added
     * @param  string $fkTable  The reference table name
     * @param  array  $relation The relationship definition
     * @return array|null Foreign key constraint definition
     */
    protected function getFKConstraint($table, $fkTable, $relation)
    {
        if ($this->schema['_options']['constraints']) {
            $field = $relation['name'];
            return array(
                'name'              => $table.'_FK_'.$field,
                'fields'            => $field,
                'reference_table'   => $fkTable,
                'reference_fields'  => $field,
                'on_delete'         => $relation['cascade'] ? 'CASCADE' : 'RESTRICT',
                'on_update'         => 'NO ACTION'
            );
        } else {
            return null;
        }
    }

    /**
     * Process schema
     * @return boolean TRUE for success; FALSE for failure
     */
    private function load()
    {
        $schema = $this->schema;
        if (isset($schema['_options'])) {
            $options = $schema['_options'] + $this->defaultOptions;
        } else {
            $options = $this->defaultOptions;
        }
        unset($schema['_options']);

        if (count($schema) == 0) {
            return false;
        }

        # Populate primary key fields
        $pkFields = array();
        foreach ($schema as $table => $def) {
            $fullTableName = db_prefix().$table;

            if (isset($def['options'])) {
                $def['options'] += $options;
            } else {
                $def['options'] = $options;
            }
            $schema[$table] = $def;

            # PK Field(s)
            $pkFields[$table] = array();
            if (isset($def['options']['pk'])) {
                foreach ($def['options']['pk'] as $pk) {
                    if (isset($def[$pk])) {
                        // user-defined PK field type
                        $pkFields[$table][$pk] = $def[$pk];
                    } else {
                        // default PK field type
                        $pkFields[$table][$pk] = $this->getPKDefaultType();
                    }
                }
            } else {
                $pkFields[$table]['id'] = $this->getPKDefaultType();
            }
        }

        $this->schema['_options']['pk'] = $pkFields;

        # Add ManyToMany tables to the schema
        $constraints = array();
        $manyToMany = array_filter($schema, function($def) {
            return isset($def['m:m']) ? true : false;
        });

        foreach ($manyToMany as $table => $def) {
            foreach ($def['m:m'] as $fkTable => $fk) {
                if (isset($schema[$table.'_to_'.$fkTable]) || isset($schema[$fkTable.'_to_'.$table])) {
                    # if the joint table has already been defined
                    continue;
                }

                if (isset($schema[$fkTable]['m:m'][$table])) {
                    # table1_to_table2
                    $jointTable = $table.'_to_'.$fkTable;
                    $schema[$jointTable]['options'] = array(
                        'pk' => array(),
                        'timestamps' => false, # no need timestamp fields for many-to-many table
                        'm:m' => true
                    ) + $this->defaultOptions;

                    # table1.field
                    $relation = $this->getRelationOptions($fk, $table);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($fkTable, $table, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($jointTable, $table, $relation);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }

                    # table2.field
                    $relation = $this->getRelationOptions($schema[$fkTable]['m:m'][$table], $fkTable);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($table, $fkTable, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($jointTable, $fkTable, $relation);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }
                }
            }
        }

        $this->schema['_options']['pk'] = $pkFields;

        $sql = array();
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';
        # loop the tables
        foreach ($schema as $table => $def) {
            $fullTableName = db_prefix().$table;

            # Populate foreign key fields
            $fkFields = array();
            # OneToMany
            if (isset($def['m:1']) && is_array($def['m:1'])) {
                foreach ($def['m:1'] as $fkTable) {
                    if (isset($schema[$fkTable]['1:m'][$table])) {
                        $relation = $this->getRelationOptions($schema[$fkTable]['1:m'][$table], $fkTable);
                        $field = $relation['name'];
                        # Get FK field definition
                        $fkFields[$field] = $this->getFKField($table, $fkTable, $relation);
                        # Get FK constraints
                        $constraint = $this->getFKConstraint($table, $fkTable, $relation);
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
                    $fkFields[$field] = $this->getFKField($table, $fkTable, $relation);
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($table, $fkTable, $relation);
                    if ($constraint) {
                        $constraints[$table][$field] = $constraint;
                    }
                }
            }

            $def = array_merge($pkFields[$table], $fkFields, $def);
            $schema[$table] = $def;

            # ManyToMany table FK indexes
            if (isset($def['options']['m:m'])) {
                foreach ($schema[$jointTable] as $field => $rule) {
                    if ($field == 'options') {
                        continue;
                    }
                    $fkFields[$field] = $rule;
                }
            }

            # Timestamp fields
            if ($def['options']['timestamps']) {
                $def['created'] = array('type' => 'datetime', 'null' => true);
                $def['updated'] = array('type' => 'datetime', 'null' => true);
                $def['deleted'] = array('type' => 'datetime', 'null' => true);
            }

            # CREATE TABLE Statement
            $sql[] = '--';
            $sql[] = '-- Table structure for table `'.$fullTableName.'`';
            $sql[] = '--';

            $sql[] = "DROP TABLE IF EXISTS `{$fullTableName}`;";
            $createTableSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` (\n";
            # loop the fields
            $autoinc = false;
            foreach ($def as $name => $rule) {
                # Skip for relationship and option definitions
                if (in_array($name, self::$relationships) || $name == 'options') {
                    continue;
                }

                $collate = isset($def['options']['collate']) ? $def['options']['collate'] : null;
                $createTableSql .= '  '.$this->getFieldStatement($name, $rule, $collate).",\n";

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
                        $createTableSql .= '  UNIQUE KEY';
                    } else {
                        $createTableSql .= '  KEY';
                    }
                    $createTableSql .= " `IDX_$name` (`$name`),\n";
                }
            }

            # Primay key indexes
            if (count($pkFields)) {
                $createTableSql .= '  PRIMARY KEY (`'.implode('`,`', array_keys($pkFields[$table])).'`)'."\n";
            }

            $createTableSql .= ')';
            $createTableSql .= ' ENGINE='.$options['engine'];
            $createTableSql .= ' DEFAULT CHARSET='.$options['charset'];
            $createTableSql .= ' COLLATE='.$options['collate'];
            if ($autoinc) {
                $createTableSql .= ' AUTO_INCREMENT=1';
            }
            $createTableSql .= ";\n";
            $sql[] = $createTableSql;
        }

        # FK constraints
        if ($options['constraints']) {
            foreach ($constraints as $table => $constraint) {
                $fullTableName = db_prefix().$table;
                $sql[] = '--';
                $sql[] = '-- Constraints for table `'.$fullTableName.'`';
                $sql[] = '--';

                $constraintSql = "ALTER TABLE `{$fullTableName}`\n";
                $statement = array();
                foreach ($constraint as $field => $rule) {
                    $statement[] = "  ADD CONSTRAINT `{$rule['name']}` FOREIGN KEY (`{$rule['fields']}`)"
                        . " REFERENCES `{$rule['reference_table']}` (`{$rule['reference_fields']}`)"
                        . " ON DELETE {$rule['on_delete']}"
                        . " ON UPDATE {$rule['on_update']}";
                }
                $constraintSql .= implode(",\n", $statement) . ";\n";
                $sql[] = $constraintSql;
            }
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $this->sqlStatements = $sql;

        $schema['_options'] = $this->schema['_options'];
        $this->schema = $schema;

        return true;
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
     * Export the built schema definition into a file
     * @param  string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function build($dbNamespace = null)
    {
        if (!$this->isLoaded()) {
            $this->load();
        }

        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $builtSchema = str_replace('  ', '    ', var_export($this->schema, true));
        $builtSchema = preg_replace('/\s+\\n/', "\n", $builtSchema);
        $builtSchema = preg_replace('/=>\\n/', "=>", $builtSchema);
        $builtSchema = preg_replace('/=>\s+/', "=> ", $builtSchema);

        $content = "<?php\n\n";
        $content .= "return ";
        $content .= $builtSchema;
        $content .= ";\n";
        return file_put_contents(DB.'build'._DS_.'schema.'.$dbNamespace.'.inc', $content);
    }

    /**
     * Import schema to the database
     * @param  string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function import($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $this->build($dbNamespace);

        if (!count($this->sqlStatements)) {
            return false;
        }

        if ($this->dbNamespace !== $dbNamespace) {
            db_switch($dbNamespace);
        }

        foreach ($this->sqlStatements as $sql) {
            db_query($sql);
        }

        if ($this->dbNamespace !== $dbNamespace) {
            // back to default db
            db_switch($this->dbNamespace);
        }

        $this->build($dbNamespace);

        return true;
    }

    /**
     * Export sql dump file
     * @param  string $dbNamespace The namespace for the database
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

        $dump = implode("\n", $this->sqlStatements);

        return file_put_contents(DB.'generated'._DS_.'schema.'.$dbNamespace.'.sql', $dump) ? true : false;
    }
}
