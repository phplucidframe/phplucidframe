<?php

use LucidFrame\Core\Seeder;

return array(
    'order' => 5,
    'post-to-tag-1' => array(
        'post_id'   => Seeder::getReference('post-1'),
        'tag_id'    => Seeder::getReference('tag-1'),
    ),
    'post-to-tag-2' => array(
        'post_id'   => Seeder::getReference('post-1'),
        'tag_id'    => Seeder::getReference('tag-2'),
    ),
);
