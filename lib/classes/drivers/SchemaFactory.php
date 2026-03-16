<?php

/**
 * This file is part of the PHPLucidFrame library.
 * Schema driver factory for SchemaManager
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

class SchemaFactory
{
    /**
     * Create schema driver by name.
     *
     * @param string $driver
     * @return SchemaInterface
     */
    public static function create($driver)
    {
        $driver = strtolower((string)$driver);

        switch ($driver) {
            case 'pgsql':
                return new SchemaPostgreSQL();
            case 'mysql':
            default:
                return new SchemaMySQL();
        }
    }
}
