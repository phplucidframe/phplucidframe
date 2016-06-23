<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for db_helper.mysqli.php
 */
class DBHelperTestCase extends LucidFrameTestCase
{
    public function setUp()
    {
        parent::setUp();
        db_prq(true);
        _cfg('useDBAutoFields', false);
    }

    /**
     * Test cases
     */
    public function testCondition()
    {
        // 0.
        $cond = db_and('');
        $this->assertEqual($cond, '');

        // 1.
        $cond = db_and(array(
            'id' => 1,
            'title' => 'a project'
        ));
        $this->assertEqual($cond, '`id` = 1 AND `title` = "a project"');

        // 2.
        $cond = db_or(array(
            'id' => 1,
            'title' => 'a project'
        ));
        $this->assertEqual($cond, '`id` = 1 OR `title` = "a project"');

        // 3.
        $cond = db_and(array(
            'id' => array(1, 2, 3),
            'id >' => 10
        ));
        $this->assertEqual($cond, '`id` IN (1, 2, 3) AND `id` > 10');

        // 4.
        $cond = db_or(array(
            'id' => array(1, 2, 3),
            'id >' => 10
        ));
        $this->assertEqual($cond, '`id` IN (1, 2, 3) OR `id` > 10');

        // 5.
        $cond = db_or(array(
            'id' => array(1, 2, 3),
            'title' => array("project one", "project two")
        ));
        $this->assertEqual($cond, '`id` IN (1, 2, 3) OR `title` IN ("project one", "project two")');

        // 6.
        $cond = db_and(array(
            'id' => 1,
            'deleted' => null
        ));
        $this->assertEqual($cond, '`id` = 1 AND `deleted` IS NULL');

        // 7.
        $cond = db_and(array(
            'id' => 1,
            'deleted !=' => null
        ));
        $this->assertEqual($cond, '`id` = 1 AND `deleted` IS NOT NULL');

        // 8.
        $cond = db_or(array(
            'id' => 1,
            'deleted' => null
        ));
        $this->assertEqual($cond, '`id` = 1 OR `deleted` IS NULL');

        // 9. AND (OR)
        $cond = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            db_or(array(
                'id' => array(1, 2, 3),
                'id >=' => 10
            ))
        ));
        $this->assertEqual($cond, self::oneline('
            `title` = "a project" AND `type` = "software" AND ( `id` IN (1, 2, 3) OR `id` >= 10 )
        '));

        // 10. OR (AND)
        $cond = db_or(array(
            'title' => 'a project',
            'type' => 'software',
            db_and(array(
                'id' => array(1, 2, 3),
                'id >=' => 10
            ))
        ));
        $this->assertEqual($cond, self::oneline('
            `title` = "a project" OR `type` = "software" OR ( `id` IN (1, 2, 3) AND `id` >= 10 )
        '));

        // 11. AND (OR (AND))
        $cond = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            db_or(array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
                db_and(array(
                    'created >' => '2014-12-31',
                    'deleted' => null
                ))
            ))
        ));
        $this->assertEqual($cond, self::oneline('
            `title` = "a project" AND `type` = "software"
            AND ( `id` IN (1, 2, 3) OR `id` >= 10
            OR ( `created` > "2014-12-31" AND `deleted` IS NULL ) )
        '));

        // 12. OR (AND (OR))
        $cond = db_or(array(
            'title' => 'a project',
            'type' => 'software',
            db_and(array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
                db_or(array(
                    'created >' => '2014-12-31',
                    'deleted' => null
                ))
            ))
        ));
        $this->assertEqual($cond, self::oneline('
            `title` = "a project" OR `type` = "software"
            OR ( `id` IN (1, 2, 3) AND `id` >= 10
            AND ( `created` > "2014-12-31" OR `deleted` IS NULL ) )
        '));

        // 13. AND (OR) (AND)
        $cond = db_and(array(
            'title' => 'a project',
            'type' => 'software',
            db_or(array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
            )),
            db_and(array(
                'created >' => '2014-12-31',
                'deleted' => null
            ))
        ));
        $this->assertEqual($cond, self::oneline('
            `title` = "a project" AND `type` = "software"
            AND ( `id` IN (1, 2, 3) OR `id` >= 10 )
            AND ( `created` > "2014-12-31" AND `deleted` IS NULL )
        '));

        // 14. (AND) (OR) (AND)
        $cond = db_and(array(
            db_and(array(
                'title' => 'a project',
                'type' => 'software'
            )),
            db_or(array(
                'id' => array(1, 2, 3),
                'id >=' => 10,
            )),
            db_and(array(
                'created >' => '2014-12-31',
                'deleted' => null
            ))
        ));
        $this->assertEqual($cond, self::oneline('( `title` = "a project" AND `type` = "software" )
            AND ( `id` IN (1, 2, 3) OR `id` >= 10 )
            AND ( `created` > "2014-12-31" AND `deleted` IS NULL )'));

        $this->bootEnd = microtime(true);

        // 15.
        $cond = db_or(array(
            'id !=' => array(1, 2, 3),
            'id >' => 10
        ));
        $this->assertEqual($cond, '`id` NOT IN (1, 2, 3) OR `id` > 10');

        // 16.
        $cond = db_or(array(
            'id between' => array(1, 50),
            'id >' => 100
        ));
        $this->assertEqual($cond, '( `id` BETWEEN 1 AND 50 ) OR `id` > 100');

        // 17.
        $cond = db_or(array(
            'id nbetween' => array(1, 50),
            'id >' => 100
        ));
        $this->assertEqual($cond, '( `id` NOT BETWEEN 1 AND 50 ) OR `id` > 100');

        // 18.
        $cond = db_or(array(
            'id between' => 10, // force to equal condition
            'id >' => 100
        ));
        $this->assertEqual($cond, '`id` = 10 OR `id` > 100');

        // 19.
        $cond = db_or(
            array('id' => 10),
            array('id' => 100)
        );
        $this->assertEqual($cond, '`id` = 10 OR `id` = 100');

        // 20.
        $cond = db_and(array(
            'title like' => 'a project'
        ));
        $this->assertEqual($cond, '`title` LIKE "%a project%"');

        // 21.
        $cond = db_and(array(
            'title like%%' => 'a project'
        ));
        $this->assertEqual($cond, '`title` LIKE "%a project%"');

        // 22.
        $cond = db_and(array(
            'title like%~' => 'a project'
        ));
        $this->assertEqual($cond, '`title` LIKE "%a project"');

        // 23.
        $cond = db_and(array(
            'title nlike~%' => 'a project'
        ));
        $this->assertEqual($cond, '`title` NOT LIKE "a project%"');

        // 24.
        $cond = db_and(array(
            'title nlike' => 'a project'
        ));
        $this->assertEqual($cond, '`title` NOT LIKE "%a project%"');

        // 25.
        $cond = db_and(array(
            'title nlike%%' => 'a project'
        ));
        $this->assertEqual($cond, '`title` NOT LIKE "%a project%"');

        // 26.
        $cond = db_and(array(
            'title nlike%~' => 'a project'
        ));
        $this->assertEqual($cond, '`title` NOT LIKE "%a project"');

        // 27.
        $cond = db_and(array(
            'title nlike~%' => 'a project'
        ));
        $this->assertEqual($cond, '`title` NOT LIKE "a project%"');
    }

    public function testUpdateQuery()
    {
        # Using the first field as condition
        $sql = db_update('post', array(
            'postId' => 1,
            'postTitle' => 'Hello World Updated!'
        ));
        $this->assertEqual(self::oneline($sql), self::oneline('UPDATE `post`
            SET `postTitle` = "Hello World Updated!"
            WHERE `postId` = 1'));

        # Using simple array condition
        $sql = db_update(
            'post',
            array('postTitle' => 'Hello World Updated!'),
            array('postId' => 1)
        );
        $this->assertEqual(self::oneline($sql), self::oneline('
            UPDATE `post`
            SET `postTitle` = "Hello World Updated!"
            WHERE `postId` = 1
        '));

        # Using array AND condition
        $sql = db_update(
            'post',
            array('postTitle' => 'Hello World Updated!'),
            array('postId' => 1, 'uid' => 1)
        );
        $this->assertEqual(self::oneline($sql), self::oneline('
            UPDATE `post`
            SET `postTitle` = "Hello World Updated!"
            WHERE `postId` = 1 AND `uid` = 1
        '));

        # Using string AND condition
        $sql = db_update(
            'post',
            array('postTitle' => 'Hello World Updated!'),
            db_and(array('postId' => 1, 'uid' => 1))
        );
        $this->assertEqual(self::oneline($sql), self::oneline('
            UPDATE `post`
            SET `postTitle` = "Hello World Updated!"
            WHERE `postId` = 1 AND `uid` = 1
        '));

        # Using string OR condition
        $sql = db_update(
            'post',
            array('postTitle' => 'Hello World Updated!'),
            db_or(array('postId' => 1, 'uid' => 1))
        );
        $this->assertEqual(self::oneline($sql), self::oneline('
            UPDATE `post`
            SET `postTitle` = "Hello World Updated!"
            WHERE `postId` = 1 OR `uid` = 1
        '));
    }

    public function testDeleteQuery()
    {
        $sql = db_delete('post', array(
            'postId' => 1
        ));
        $this->assertEqual(self::oneline($sql), 'DELETE FROM `post` WHERE `postId` = 1 LIMIT 1');

        $sql = db_delete_multi('post', array(
            'postId between' => array(1, 10)
        ));
        $this->assertEqual(self::oneline($sql), 'DELETE FROM `post` WHERE ( `postId` BETWEEN 1 AND 10 )');

        $sql = db_delete_multi('post', array(
            'uid' => 1,
            'postId' => array(9, 10)
        ));
        $this->assertEqual(self::oneline($sql), 'DELETE FROM `post` WHERE `uid` = 1 AND `postId` IN (9, 10)');

        $sql = db_delete_multi('post', db_or(
            array('postId' => 1),
            array('postId' => array(9, 10))
        ));
        $this->assertEqual(self::oneline($sql), 'DELETE FROM `post` WHERE `postId` = 1 OR `postId` IN (9, 10)');
    }

    public function testUpdateQueryWithAutoFields()
    {
        db_prq(false);
        _cfg('useDBAutoFields', true);

        db_insert('post', array(
            'postTitle' => 'Welcome to LucidFrame Blog',
            'postId'    => 1,
            'uid'       => 1
        ));

        ### if no slug and condition is given
        db_update('post', array(
            'postId' => 1,
            'postTitle' => 'LucidFrame Blog'
        ));

        $sql = 'SELECT slug, postTitle FROM '.db_prefix().'post WHERE postId = 1';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'lucidframe-blog');
        $this->assertEqual($post->postTitle, 'LucidFrame Blog');

        ### if no slug flag given and condition at 2nd place
        db_update('post', array(
            'postTitle' => 'Welcome to LucidFrame Blog'
        ), array(
            'postId' => 1
        ));

        $sql = 'SELECT slug, postTitle FROM '.db_prefix().'post WHERE postId = 1';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog');

        ### if slug flag is false
        db_update(
            'post',
            array(
                'postTitle' => 'Welcome to LucidFrame Blog Updated'
            ),
            $useSlug = false,
            array(
                'postId' => 1
            )
        );

        $sql = 'SELECT slug, postTitle FROM '.db_prefix().'post WHERE postId = 1';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog Updated');

        ### if slug flag is true
        db_update(
            'post',
            array(
                'postTitle' => 'Welcome to LucidFrame Blog'
            ),
            $useSlug = true,
            array(
                'postId' => 1
            )
        );

        $sql = 'SELECT slug, postTitle FROM '.db_prefix().'post WHERE postId = 1';
        $post = db_fetchResult($sql);
        $this->assertEqual($post->slug, 'welcome-to-lucidframe-blog');
        $this->assertEqual($post->postTitle, 'Welcome to LucidFrame Blog');
    }

    public function tearDown()
    {
        db_prq(false);
        _cfg('useDBAutoFields', true);
        parent::tearDown();
    }
}
