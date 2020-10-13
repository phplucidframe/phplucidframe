<?php
/**
 * This file is the current state of the database.
 *
 * This schema.php or schema.default.php definition is the authoritative source for your database schema.
 * If you need to create the application database, you should be using `php lucidframe schema:load`
 *
 * It's strongly recommended that you check this file into your version control system.
 */

return array(
    '_options' => array(
        // defaults for all tables; this can be overridden by each table
        'timestamps'    => true, // all tables will have 3 datetime fields - `created`, `updated`, `deleted`
        'constraints'   => true, // all FK constraints to all tables
        'engine'        => _p('db.default.engine'),
        'charset'       => _p('db.default.charset'),
        'collate'       => _p('db.default.collation'),
    ),
    /*
    ...
    Check schema.sample.php for array format hereafter
    ...
    */
);
