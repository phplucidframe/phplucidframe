<?php

use LucidFrame\Core\Seeder;

return array(
    'order' => 5,
    'post-to-tag-1' => array(
        'postId'    => Seeder::setReference('post-1'),
        'tagId'     => Seeder::setReference('tag-1'),
    ),
    'post-to-tag-2' => array(
        'postId'    => Seeder::setReference('post-1'),
        'tagId'     => Seeder::setReference('tag-2'),
    ),
);
