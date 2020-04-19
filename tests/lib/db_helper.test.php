<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for db_helper.mysqli.php
 */
class DBHelperTestCase extends LucidFrameTestCase
{
    public function testCondition()
    {
        db_prq(true);

        // 1. simple AND
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'id' => 1,
            'title' => 'a project'
        ));
        $this->assertEqual($clause, '`id` = :id AND `title` = :title');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 1 AND `title` = a project');

        // 2. simple OR
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id' => 1,
            'title' => 'a project'
        ));
        $this->assertEqual($clause, '`id` = :id OR `title` = :title');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 1 OR `title` = a project');

        // 3. AND with IN, gt
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'id' => array(1, 2, 3),
            'id >' => 10
        ));
        $this->assertEqual($clause, '`id` IN (:id0, :id1, :id2) AND `id` > :id3');
        $this->assertEqual($this->toSql($clause, $values), '`id` IN (1, 2, 3) AND `id` > 10');

        // 4. OR with IN, gt
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id >' => 10,
            'id' => array(1, 2, 3),
        ));
        $this->assertEqual($clause, '`id` > :id OR `id` IN (:id0, :id1, :id2)');
        $this->assertEqual($this->toSql($clause, $values), '`id` > 10 OR `id` IN (1, 2, 3)');

        // 5. OR with IN
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id' => array(1, 2, 3),
            'title' => array("project one", "project two")
        ));
        $this->assertEqual($clause, '`id` IN (:id0, :id1, :id2) OR `title` IN (:title0, :title1)');
        $this->assertEqual($this->toSql($clause, $values), '`id` IN (1, 2, 3) OR `title` IN (project one, project two)');

        // 6. AND ... IS NULL
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'id' => 1,
            'deleted' => null
        ));
        $this->assertEqual($clause, '`id` = :id AND `deleted` IS NULL');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 1 AND `deleted` IS NULL');

        // 7. AND ... IS NOT NULL
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'id' => 1,
            'deleted !=' => null
        ));
        $this->assertEqual($clause, '`id` = :id AND `deleted` IS NOT NULL');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 1 AND `deleted` IS NOT NULL');

        // 8. OR ... IS NULL
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id' => 1,
            'deleted' => null
        ));
        $this->assertEqual($clause, '`id` = :id OR `deleted` IS NULL');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 1 OR `deleted` IS NULL');

        // 9. AND (OR)
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            'or' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10
            )
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title AND `type` = :type AND (`id` IN (:id0, :id1, :id2) OR `id` >= :id3)
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project AND `type` = software AND (`id` IN (1, 2, 3) OR `id` >= 10)
        '));

        // 10. OR (AND)
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'title' => 'a project',
            'type' => 'software',
            'and' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10
            )
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title OR `type` = :type OR (`id` IN (:id0, :id1, :id2) AND `id` >= :id3)
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project OR `type` = software OR (`id` IN (1, 2, 3) AND `id` >= 10)
        '));

        // 11. AND (OR (AND))
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            'or' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
                'and' => array(
                    'created >' => '2014-12-31',
                    'deleted' => null
                ),
            ),
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title AND `type` = :type
            AND (`id` IN (:id0, :id1, :id2) OR `id` >= :id3
            OR (`created` > :created AND `deleted` IS NULL))
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project AND `type` = software
            AND (`id` IN (1, 2, 3) OR `id` >= 10
            OR (`created` > 2014-12-31 AND `deleted` IS NULL))
        '));

        // 12. OR (AND (OR))
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'title' => 'a project',
            'type' => 'software',
            'and' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
                'or' => array(
                    'created >' => '2014-12-31',
                    'deleted' => null
                ),
            ),
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title OR `type` = :type
            OR (`id` IN (:id0, :id1, :id2) AND `id` >= :id3
            AND (`created` > :created OR `deleted` IS NULL))
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project OR `type` = software
            OR (`id` IN (1, 2, 3) AND `id` >= 10
            AND (`created` > 2014-12-31 OR `deleted` IS NULL))
        '));

        // 13. AND (OR) AND
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            'or' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
            ),
            'created >' => '2014-12-31',
            'deleted' => null
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title AND `type` = :type
            AND (`id` IN (:id0, :id1, :id2) OR `id` >= :id3)
            AND `created` > :created AND `deleted` IS NULL
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project AND `type` = software
            AND (`id` IN (1, 2, 3) OR `id` >= 10)
            AND `created` > 2014-12-31 AND `deleted` IS NULL
        '));

        // 14. OR (AND) (OR)
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'title' => 'a project',
            'type' => 'software',
            'and' => array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
            ),
            'or' => array(
                'created >' => '2014-12-31',
                'deleted' => null
            )
        ));

        $this->assertEqual($clause, self::oneline('
            `title` = :title OR `type` = :type
            OR (`id` IN (:id0, :id1, :id2) AND `id` >= :id3)
            OR (`created` > :created OR `deleted` IS NULL)
        '));
        $this->assertEqual($this->toSql($clause, $values), self::oneline('
            `title` = a project OR `type` = software
            OR (`id` IN (1, 2, 3) AND `id` >= 10)
            OR (`created` > 2014-12-31 OR `deleted` IS NULL)
        '));

        $this->bootEnd = microtime(true);

        // 15. OR with NOT IN, gt
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id !=' => array(1, 2, 3),
            'id >' => 10
        ));

        $this->assertEqual($clause, '`id` NOT IN (:id0, :id1, :id2) OR `id` > :id3');
        $this->assertEqual($this->toSql($clause, $values), '`id` NOT IN (1, 2, 3) OR `id` > 10');

        // 16. OR with BETWEEN, gt
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id between' => array(1, 50),
            'id >' => 100
        ));

        $this->assertEqual($clause, '(`id` BETWEEN :id_from AND :id_to) OR `id` > :id0');
        $this->assertEqual($this->toSql($clause, $values), '(`id` BETWEEN 1 AND 50) OR `id` > 100');

        // 17. OR with NOT BETWEEN, gt
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id nbetween' => array(1, 50),
            'id >' => 100
        ));

        $this->assertEqual($clause, '(`id` NOT BETWEEN :id_from AND :id_to) OR `id` > :id0');
        $this->assertEqual($this->toSql($clause, $values), '(`id` NOT BETWEEN 1 AND 50) OR `id` > 100');

        // 18.
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_or(array(
            'id between' => 10,  // force to equal condition
            'id >' => 100
        ));

        $this->assertEqual($clause, '`id` = :id OR `id` > :id0');
        $this->assertEqual($this->toSql($clause, $values), '`id` = 10 OR `id` > 100');

        // 19. LIKE %~%
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title like' => 'a project'
        ));

        $this->assertEqual($clause, '`title` LIKE CONCAT("%", :title, "%")');
        $this->assertEqual($this->toSql($clause, $values), '`title` LIKE CONCAT("%", a project, "%")');

        // 20. LIKE %~
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title like%~' => 'a project'
        ));

        $this->assertEqual($clause, '`title` LIKE CONCAT("%", :title)');
        $this->assertEqual($this->toSql($clause, $values), '`title` LIKE CONCAT("%", a project)');

        // 21. LIKE ~%
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title like~%' => 'a project'
        ));

        $this->assertEqual($clause, '`title` LIKE CONCAT(:title, "%")');
        $this->assertEqual($this->toSql($clause, $values), '`title` LIKE CONCAT(a project, "%")');

        // 22. NOT LIKE %~%
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title nlike' => 'a project'
        ));

        $this->assertEqual($clause, '`title` NOT LIKE CONCAT("%", :title, "%")');
        $this->assertEqual($this->toSql($clause, $values), '`title` NOT LIKE CONCAT("%", a project, "%")');

        // 23. NOT LIKE %~
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title nlike%~' => 'a project'
        ));

        $this->assertEqual($clause, '`title` NOT LIKE CONCAT("%", :title)');
        $this->assertEqual($this->toSql($clause, $values), '`title` NOT LIKE CONCAT("%", a project)');

        // 24. NOT LIKE ~%
        QueryBuilder::clearBindValues();
        list($clause, $values) = db_and(array(
            'title nlike~%' => 'a project'
        ));

        $this->assertEqual($clause, '`title` NOT LIKE CONCAT(:title, "%")');
        $this->assertEqual($this->toSql($clause, $values), '`title` NOT LIKE CONCAT(a project, "%")');

        db_prq(false);
    }

    public function testUpdateQuery()
    {
        db_insert('post', array(
            'postTitle' => 'Hello World',
            'postBody'  => 'Hello World body',
            'postId'    => 1,
            'uid'       => 1
        ));

        # Using the first field as condition
        db_update('post', array(
            'postId' => 1,
            'postTitle' => 'Hello World Updated!'
        ));

        $post = db_select('post')
            ->where(array('postId' => 1))
            ->getSingleResult();

        $this->assertEqual($post->slug, 'hello-world-updated');
        $this->assertEqual($post->postTitle, 'Hello World Updated!');

        # Using simple array condition
        db_update(
            'post',
            array('postTitle' => 'Hello World updated with simple array condition'),
            array('postId' => 1)
        );

        $post = db_select('post')
            ->where(array('postId' => 1))
            ->getSingleResult();

        $this->assertEqual($post->slug, 'hello-world-updated-with-simple-array-condition');
        $this->assertEqual($post->postTitle, 'Hello World updated with simple array condition');

        # Using array AND condition
        db_update(
            'post',
            array('postTitle' => 'Hello World updated with array AND condition!'),
            array('postId' => 1, 'uid' => 1)
        );

        $post = db_select('post')
            ->where(array(
                'postId' => 1,
                'uid' => 1
            ))
            ->getSingleResult();

        $this->assertEqual($post->slug, 'hello-world-updated-with-array-and-condition');
        $this->assertEqual($post->postTitle, 'Hello World updated with array AND condition!');

        # Using string OR condition
        db_update(
            'post',
            array('postTitle' => 'Hello World updated with string OR condition!'),
            array(
                'or' => array(
                    'postId' => 1,
                    'uid' => 1
                )
            )
        );

        $post = db_select('post')
            ->where(array(
                'or' => array(
                    'postId' => 1,
                    'uid' => 1
                )
            ))
            ->getSingleResult();

        $this->assertEqual($post->slug, 'hello-world-updated-with-string-or-condition');
        $this->assertEqual($post->postTitle, 'Hello World updated with string OR condition!');
    }

    public function testDeleteQuery()
    {
        db_delete('post', array(
            'postId' => 1
        ));
        $this->assertEqual(self::oneline(db_queryStr()), 'DELETE FROM `post` WHERE `postId` = 1 LIMIT 1');

        db_delete_multi('post', array(
            'postId between' => array(1, 10)
        ));
        $this->assertEqual(self::oneline(db_queryStr()), 'DELETE FROM `post` WHERE (`postId` BETWEEN 1 AND 10)');

        db_delete_multi('post', array(
            'uid' => 1,
            'postId' => array(9, 10)
        ));
        $this->assertEqual(self::oneline(db_queryStr()), 'DELETE FROM `post` WHERE `uid` = 1 AND `postId` IN (9, 10)');

        db_delete_multi('post', array(
            'postId' => array(1, 9, 10))
        );
        $this->assertEqual(self::oneline(db_queryStr()), 'DELETE FROM `post` WHERE `postId` IN (1, 9, 10)');
    }

    public function testUpdateQueryWithAutoFields()
    {
        db_insert('post', array(
            'postTitle' => 'Welcome to LucidFrame Blog',
            'postBody'  => 'Blog body',
            'postId'    => 2,
            'uid'       => 1
        ));

        ### if no slug and condition is given
        db_update('post', array(
            'postId' => 2,
            'postTitle' => 'LucidFrame Blog'
        ));

        $sql = 'SELECT slug, postTitle FROM ' . db_table('post') . ' WHERE postId = 2';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'lucidframe-blog');
        $this->assertEqual($post->postTitle, 'LucidFrame Blog');

        ### if no slug flag given and condition at 2nd place
        db_update(
            'post',
            array('postTitle' => 'Welcome to LucidFrame Blog'),
            array('postId' => 2)
        );

        $sql = 'SELECT slug, postTitle FROM ' . db_table('post') .' WHERE postId = 2';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog');

        ### if slug flag is false
        db_update(
            'post',
            array('postTitle' => 'Welcome to LucidFrame Blog Updated'),
            false,
            array('postId' => 2)
        );

        $sql = 'SELECT slug, postTitle FROM ' . db_table('post') .' WHERE postId = 2';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog Updated');

        ### if slug flag is true
        db_update(
            'post',
            array('postTitle' => 'Welcome to LucidFrame Blog'),
            true,
            array('postId' => 2)
        );

        $sql = 'SELECT slug, postTitle FROM ' . db_table('post') . ' WHERE postId = 2';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog');
    }
}
