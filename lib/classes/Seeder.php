<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Seeder takes care of the initial seeding of your database with default or sample data.
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

/**
 * Database Seeder
 */
class Seeder
{
    /** @var string The namespace for the database */
    private $dbNamespace = 'default';
    /** @var string Directory path to the files of seeding definition */
    private $path;
    /** @var array Seeding data */
    private $data = array();
    /** @var array Foreign key table data */
    private static $references = array();
    /** @var array Tables */
    private $tables;

    /**
     * Constructor
     * @param string $namespace The database namespace
     */
    public function __construct($namespace = 'default')
    {
        $this->dbNamespace = $namespace;
        $this->path = DB . 'seed' . _DS_;
    }

    /**
     * Setter for $dbNamespace
     * @param string $namespace The database namespace
     */
    public function setDbNamespace($namespace)
    {
        $this->dbNamespace = $namespace;
    }

    /**
     * Getter for $dbNamespace
     * @return string The database namespace
     */
    public function getDbNamespace()
    {
        return $this->dbNamespame;
    }

    /**
     * Set reference key
     * @param string $key The reference key
     * @return string
     */
    public static function setReference($key)
    {
        return __CLASS__ . '::' . $key;
    }

    /**
     * Get reference field value
     * @param string $key The reference key
     * @return mixed The value
     */
    public static function getReference($key)
    {
        return isset(self::$references[$key]) ? self::$references[$key] : null;
    }

    /**
     * Run seeding
     * @param array $entities The array of entity names to be executed only
     * @return boolean TRUE if seeded; otherwise FALSE
     */
    public function run(array $entities = array())
    {
        if ($this->load($entities)) {
            # Purge before insert
            db_disableForeignKeyCheck();

            if (count($entities)) {
                $this->tables = array_filter($entities, function($table) {
                    return in_array($table, $this->tables);
                });
            }

            foreach ($this->tables as $table) {
                db_truncate($table);
            }

            $tableDone = '';

            # Arrange data to insert
            foreach ($this->data as $reference => $record) {
                if (!isset($record['__TABLE__'])) {
                    continue;
                }

                $table = $record['__TABLE__'];
                unset($record['__TABLE__']);

                if ($table != $tableDone) {
                    if ($tableDone) {
                        _writeln('%s is seeded.', $tableDone);
                    }
                    $tableDone = $table;
                }

                $slug = null;
                $data = array();
                foreach ($record as $field => $value) {
                    if ($field == 'slug') {
                        $slug = $value;
                        unset($record['slug']);
                    }

                    # Get foreign key field reference
                    if (is_string($value) && strpos($value, __CLASS__ . '::') === 0) {
                        $refKeyName = explode('::', $value);
                        $refKey = end($refKeyName);
                        $data[$field] = self::getReference($refKey);
                    } else {
                        $data[$field] = $value;
                    }
                }

                # Make slug field at the start
                if ($slug) {
                    $data = array('slug' => $slug) + $data;
                }

                if ($insertId = db_insert($table, $data)) {
                    self::$references[$reference] = $insertId;
                }
            }

            if ($tableDone) {
                _writeln('%s is seeded.', $tableDone);
            }

            db_enableForeignKeyCheck();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Load seeding data
     * @param array $entities The array of entity names to be executed only
     * @return boolean TRUE if it is load; FALSE if nothing loaded
     */
    private function load(array $entities = array())
    {
        $_DB = _app('db');

        $entities = array_map(function($entity) {
            $entity .= '.php';
            return $entity;
        }, $entities);

        $dir = $this->path . $this->dbNamespace;
        if (is_dir($dir) && is_object($_DB)) {
            $seeding = array();
            $files = scandir($dir);
            foreach ($files as $fileName) {
                if (count($entities) && !in_array($fileName, $entities)) {
                    continue;
                }

                $dir = rtrim(rtrim($dir, '/'), '\\');
                $file = $dir . _DS_ . $fileName;

                if ($fileName === '.' || $fileName === '..' || $fileName === '.gitkeep' || !is_file($file)) {
                    continue;
                }

                $table = substr($fileName, 0, -4);
                if (file_exists($file) && $_DB->schemaManager->hasTable($table)) {
                    $data = include($file);
                    $order = $data['order'];
                    unset($data['order']);

                    # prepend table name in data array
                    array_walk($data, function (&$value, $key, $table) {
                        $value = array('__TABLE__' => $table) + $value;
                    }, $table);

                    $seeding[$order] = $data;
                    $this->tables[] = $table;
                }
            }

            ksort($seeding);

            foreach ($seeding as $data) {
                $this->data += $data;
            }
        }

        return count($this->data) ? true : false;
    }
}
