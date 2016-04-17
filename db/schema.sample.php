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
        'charset'       => 'utf8',
        'collate'       => 'utf8_unicode_ci',
        'engine'        => 'InnoDB',
    ),
    // array keys are table names without prefix
    'category' => array(
        'slug'           => array('type' => 'string', 'length' => 255, 'null' => false, 'unique' => true),
        'catName'        => array('type' => 'string', 'length' => 200, 'null' => false),
        'catName_en'     => array('type' => 'string', 'length' => 200, 'null' => true),
        'catName_my'     => array('type' => 'string', 'length' => 200, 'null' => true),
        'options' => array(
            'pk'         => array('catId'),     // type: integer, autoinc: true, null: false, unsigned: true
            'timestamps' => true,               // created, updated, deleted; override to _options.timestamps
            'charset'    => 'utf8',             // override to _options.charset
            'collate'    => 'utf8_unicode_ci',  // override to _options.collate
            'engine'     => 'InnoDB',           // override to _options.engine
        ),
        '1:m' => array(
            // one-to-many relation between `category` and `post`
            // there must also be 'm:1' definition at the side of `post`
            'post' => array(
                'name'      => 'catId', // FK field name in the other table (optional; defaults to "table_name + _id")
                //'unique'  => false,   // Unique index for FK; defaults to false
                //'default' => null,    // default value for FK; defaults to null
                'cascade'   => true,    // true for ON DELETE CASCADE; false for ON DELETE RESTRICT (defaults to false)
            ),
        ),
    ),
    'post' => array(
        'slug'           => array('type' => 'string', 'length' => 255, 'null' => false, 'unique' => true),
        'postTitle'      => array('type' => 'string', 'null' => false),
        'postTitle_en'   => array('type' => 'string', 'null' => true),
        'postTitle_my'   => array('type' => 'string', 'null' => true),
        'postBody'       => array('type' => 'text', 'null' => false),
        'postBody_en'    => array('type' => 'text', 'null' => true),
        'postBody_my'    => array('type' => 'text', 'null' => true),
        'options' => array(
            'pk' => array('postId'), // if this is not provided, default field name to `id`
        ),
        '1:m' => array(
            // one-to-many relation between `post` and `post_image`
            // there must also be 'm:1' definition at the side of `post_image`
            'post_image' => array(
                'name'      => 'postId',
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
                'name'      => 'postId',
                'cascade'   => true,
            ),
        ),
    ),
    'post_image' => array(
        'pimgFileName' => array('type' => 'string', 'null' => false),
        'options' => array(
            'pk' => array('pimgId'),
        ),
        // reversed 1:m relation between `post` and `post_image`
        'm:1' => array('post'),
    ),
    'tag' => array(
        'tagName'       => array('type' => 'string', 'length' => 50, 'null' => false),
        'tagName_en'    => array('type' => 'string', 'length' => 50),
        'tagName_my'    => array('type' => 'string', 'length' => 50),
        'options' => array(
            'pk' => array('tagId'),
        ),
        'm:m' => array(
            // many-to-many relation between `post` and `tag`
            // there must also be 'm:m' definition at the side of `post`
            'post' => array(
                'name'      => 'tagId',
                'cascade'   => true,
            ),
        ),
    ),
    'user' => array(
        'slug'      => array('type' => 'string', 'length' => 100),
        'fullName'  => array('type' => 'string', 'length' => 50),
        'username'  => array('type' => 'string', 'length' => 20),
        'password'  => array('type' => 'string', 'length' => 50),
        'email'     => array('type' => 'string', 'length' => 100),
        'role'      => array('type' => 'string', 'length' => 10),
        'isMaster'  => array('type' => 'boolean', 'default' => false),
        'options'   => array(
            'pk' => array('uid'),
        ),
        '1:m' => array(
            // one-to-many relation between `user` and `post`
            // there must also be 'm:1' definition at the side of `post`
            'post' => array(
                'name'      => 'uid',
                'cascade'   => false,
            ),
        ),
    ),
    'social_profile' => array(
        'facebookUrl'  => array('type' => 'string', 'length' => 100, 'null' => true),
        'twitterUrl'   => array('type' => 'string', 'length' => 100, 'null' => true),
        'gplusUrl'     => array('type' => 'string', 'length' => 100, 'null' => true),
        'linkedinUrl'  => array('type' => 'string', 'length' => 100, 'null' => true),
        '1:1' => array(
            // one-to-one relation between `social_profile` and `user`
            // no need to define 1:1 at the side of `user`
            'user' => array(
                'name'      => 'uid',
                'cascade'   => true,
            ),
        ),
    ),
    'document' => array(
        'docFileName' => array('type' => 'string', 'null' => false),
        'options' => array(
            'pk' => array('docId'),
        ),
    ),
    'lc_sessions' => array(
        'sid'       => array('type' => 'string', 'length' => 64, 'null' => false),
        'host'      => array('type' => 'string', 'length' => 128, 'null' => false),
        'timestamp' => array('type' => 'integer', 'unsigned' => true, 'null' => false),
        'session'   => array('type' => 'blob', 'length' => 'long', 'null' => false),
        'useragent' => array('type' => 'string', 'null' => false),
        'options'   => array(
            'pk'         => array('sid'),
            'timestamps' => false,
        ),
    ),
);
