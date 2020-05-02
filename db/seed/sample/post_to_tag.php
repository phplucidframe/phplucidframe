<?php

use LucidFrame\Core\Seeder;

return array(
    'order' => 5,
    'post-to-tag-1' => array(
        'post_id'   => Seeder::setReference('post-1'),
        'tag_id'    => Seeder::setReference('tag-1'),
    ),
    'post-to-tag-2' => array(
        'post_id'   => Seeder::setReference('post-1'),
        'tag_id'    => Seeder::setReference('tag-2'),
    ),
);
