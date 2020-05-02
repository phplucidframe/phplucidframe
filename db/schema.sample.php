<?php
/**
 * This file is the current state of the database.
 *
 * This schema.sample.php definition is the authoritative source for your database schema.
 * If you need to create the application database, you should be using `php lucidframe schema:load`
 *
 * It's strongly recommended that you check this file into your version control system.
 */

return array(
    '_options' => array(
        // defaults for all tables; this can be overidden by each table
        'timestamps'    => true, // all tables will have 3 datetime fields - `created`, `updated`, `deleted`
        'constraints'   => true, // all FK constraints to all tables
        'engine'        => 'InnoDB',
        'charset'       => 'utf8',
        'collate'       => _p('db.default.collation'),
    ),
    'lc_sessions' => array(
        'sid'       => array('type' => 'string', 'length' => 64, 'null' => false),
        'host'      => array('type' => 'string', 'length' => 128, 'null' => false),
        'timestamp' => array('type' => 'integer', 'unsigned' => true, 'null' => false),
        'session'   => array('type' => 'blob', 'length' => 'long', 'null' => false),
        'useragent' => array('type' => 'string', 'null' => false),
        'options'   => array(
            'pk' => array('sid'), // type: integer, autoinc: true, null: false, unsigned: true; if this is not provided, default field name to `id`
            'timestamps' => false, // created, updated, deleted; override to _options.timestamps
            'charset' => 'utf8', // override to _options.charset
            'collate' => 'utf8_unicode_ci', // override to _options.collate
            'engine' => 'InnoDB', // override to _options.engine
        ),
    ),
    // array keys are table names without prefix
    'category' => array(
        'slug'        => array('type' => 'string', 'length' => 255, 'null' => false, 'unique' => true),
        'name'        => array('type' => 'string', 'length' => 200, 'null' => false),
        'name_en'     => array('type' => 'string', 'length' => 200, 'null' => true),
        'name_my'     => array('type' => 'string', 'length' => 200, 'null' => true),
        '1:m' => array(
            // one-to-many relation between `category` and `post`
            // there must also be 'm:1' definition at the side of `post`
            'post' => array(
                'name'      => 'cat_id', // FK field name in the other table (optional; defaults to "table_name + _id")
                //'unique'  => false,   // Unique index for FK; defaults to false
                //'default' => null,    // default value for FK; defaults to null
                'cascade'   => true,    // true for ON DELETE CASCADE; false for ON DELETE RESTRICT (defaults to false)
            ),
        ),
    ),
    'post' => array(
        'slug'      => array('type' => 'string', 'length' => 255, 'null' => false, 'unique' => true),
        'title'     => array('type' => 'string', 'null' => false),
        'title_en'  => array('type' => 'string', 'null' => true),
        'title_my'  => array('type' => 'string', 'null' => true),
        'body'      => array('type' => 'text', 'null' => false),
        'body_en'   => array('type' => 'text', 'null' => true),
        'body_my'   => array('type' => 'text', 'null' => true),
        '1:m' => array(
            // one-to-many relation between `post` and `post_image`
            // there must also be 'm:1' definition at the side of `post_image`
            'post_image' => array(
                'name'      => 'post_id',
                'cascade'   => true,
            ),
        ),
        'm:1' => array(
            'category', // reversed 1:m relation between `category` and `post`
            'user',     // reversed 1:m relation between `user` and `post`
        ),
        'm:m' => array(
            // many-to-many relation between `post` and `tag`
            // there must also be 'm:m' definition at the side of `tag`
            'tag' => array(
                'name'      => 'post_id',
                'cascade'   => true,
            ),
        ),
    ),
    'post_image' => array(
        'file_name' => array('type' => 'string', 'null' => false),
        // reversed 1:m relation between `post` and `post_image`
        'm:1' => array('post'),
    ),
    'tag' => array(
        'slug'      => array('type' => 'string', 'length' => 50),
        'name'      => array('type' => 'string', 'length' => 50, 'null' => false),
        'name_en'   => array('type' => 'string', 'length' => 50),
        'name_my'   => array('type' => 'string', 'length' => 50),
        'm:m' => array(
            // many-to-many relation between `post` and `tag`
            // there must also be 'm:m' definition at the side of `post`
            'post' => array(
                'name' => 'tag_id',
                'cascade' => true,
            ),
        ),
    ),
    'user' => array(
        'slug'      => array('type' => 'string', 'length' => 100),
        'full_name' => array('type' => 'string', 'length' => 50),
        'username'  => array('type' => 'string', 'length' => 20),
        'password'  => array('type' => 'string', 'length' => 125),
        'email'     => array('type' => 'string', 'length' => 100),
        'role'      => array('type' => 'string', 'length' => 10),
        'is_master' => array('type' => 'boolean', 'default' => false),
        'phone'     => array('type' => 'array', 'null' => true), // example for array type; see /db/seed/sample/user.php
        'social'    => array('type' => 'json', 'null' => true), // example for json type; see /db/seed/sample/user.php
        //'credit'    => array('type' => 'decimal', 'length' => array(5, 1), 'default' => 0),
        //'balance'   => array('type' => 'float', 'length' => array(10, 2), 'null' => true),
        '1:m' => array(
            // one-to-many relation between `user` and `post`
            // there must also be 'm:1' definition at the side of `post`
            'post' => array(
                'name'      => 'user_id',
                'cascade'   => false,
            ),
        ),
    ),
    'social_profile' => array(
        'facebook'  => array('type' => 'string', 'length' => 100, 'null' => true),
        'twitter'   => array('type' => 'string', 'length' => 100, 'null' => true),
        'instagram' => array('type' => 'string', 'length' => 100, 'null' => true),
        'linkedin'  => array('type' => 'string', 'length' => 100, 'null' => true),
        '1:1' => array(
            // one-to-one relation between `social_profile` and `user`
            // no need to define 1:1 at the side of `user`
            'user' => array(
                'name' => 'user_id',
                'cascade' => true,
            ),
        ),
    ),
    'document' => array(
        'file_name' => array('type' => 'string', 'null' => false),
    ),
);
