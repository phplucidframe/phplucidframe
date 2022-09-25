<?php

use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for QueryBuilder.php
 */
class QueryBuilderTestCase extends LucidFrameTestCase
{
    public function testQueryBuilderSELECT()
    {
        $qb = new QueryBuilder('post', 'p');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p`');

        $qb = new QueryBuilder();
        $qb->from('post', 'p');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p`');

        $qb = db_select('post');
        $this->assertEqual($qb->getSQL(), 'SELECT `post`.* FROM `post` `post`');

        $qb = db_select('post', 'p');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p`');

        $qb = db_select('post', 'p')
            ->fields('p', array('id', 'title'))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`id`, `p`.`title` FROM `post` `p`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p', array('id', 'title'))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`id`, `p`.`title` FROM `post` `p`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p', array('id', 'title'))
            ->fields('u')
            ->join('user', 'u', 'p.user_id = u.id')
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`id`, `p`.`title`, `u`.* FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->where()
            ->condition('p.id', 1);
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`id` = :p_id');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`id` = 1');

        $qb = db_select('post', 'p')
            ->where()
            ->condition('p.created >', '2015-11-08');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`created` > :p_created');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`created` > 2015-11-08');

        $qb = db_select('post', 'p')
            ->fields('p', array('id', 'title'))
            ->fields('u', array('full_name', 'username'))
            ->join('user', 'u', 'p.user_id = u.id')
            ->leftJoin('category', 'c', 'p.cat_id = c.id')
            ->where()
            ->condition('cat_id', 1)
            ->condition('user_id', 1)
            ->orderBy('p.created', 'desc')
            ->orderBy('c.id');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`id`, `p`.`title`, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            LEFT JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE `cat_id` = :cat_id
            AND `user_id` = :user_id
            ORDER BY `p`.`created` DESC, `c`.`id` ASC
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.`id`, `p`.`title`, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            LEFT JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE `cat_id` = 1
            AND `user_id` = 1
            ORDER BY `p`.`created` DESC, `c`.`id` ASC
        '));

        $qb = db_select('post', 'p')
            ->orWhere()
            ->condition('cat_id', 1)
            ->condition('cat_id', 2);
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `cat_id` = :cat_id OR `cat_id` = :cat_id0');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `cat_id` = 1 OR `cat_id` = 2');

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('full_name', 'username'))
            ->join('user', 'u', 'p.user_id = u.id')
            ->leftJoin('category', 'c', 'p.cat_id = c.id')
            ->orWhere(array(
                'title like' => 'A project',
                '$and' => array(
                    'id' => array(1, 2, 3),
                    'user_id' => 1
                )
            ))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            LEFT JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE ( `title` LIKE CONCAT("%", :title, "%")
            OR (`id` IN (:id0, :id1, :id2) AND `user_id` = :user_id) )
            ORDER BY `p`.`created` DESC
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            LEFT JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE ( `title` LIKE CONCAT("%", A project, "%")
            OR (`id` IN (1, 2, 3) AND `user_id` = 1) )
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('full_name', 'username'))
            ->join('user', 'u', 'p.user_id = u.id')
            ->join('category', 'c', 'p.cat_id = c.id')
            ->where(array(
                'title like' => 'A project',
                '$or' => array(
                    'id' => array(1, 2, 3),
                    'user_id' => 1
                )
            ))
            ->orderBy('p.created', 'desc')
            ->limit(0, 20);
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            INNER JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE ( `title` LIKE CONCAT("%", :title, "%")
            AND (`id` IN (:id0, :id1, :id2) OR `user_id` = :user_id) )
            ORDER BY `p`.`created` DESC
            LIMIT 0, 20
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            INNER JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE ( `title` LIKE CONCAT("%", A project, "%")
            AND (`id` IN (1, 2, 3) OR `user_id` = 1) )
            ORDER BY `p`.`created` DESC
            LIMIT 0, 20
        '));

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('full_name', 'username'))
            ->join('user', 'u', 'p.user_id = u.id')
            ->join('category', 'c', 'p.cat_id = c.id')
            ->orWhere(array(
                'title nlike' => 'A project',
                '$and' => array(
                    'id' => array(1, 2, 3),
                    'id <=' => 10,
                    '$or' => array(
                        'created >' => '2014-12-31',
                        'deleted' => null
                    )
                )
            ))
            ->orderBy('p.created', 'desc')
            ->limit(5);
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            INNER JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE (
                `title` NOT LIKE CONCAT("%", :title, "%")
                OR (`id` IN (:id0, :id1, :id2)
                    AND `id` <= :id3
                    AND (`created` > :created OR `deleted` IS NULL))
            )
            ORDER BY `p`.`created` DESC
            LIMIT 5
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`full_name`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`user_id` = `u`.`id`
            INNER JOIN `category` `c` ON `p`.`cat_id` = `c`.`id`
            WHERE (
                `title` NOT LIKE CONCAT("%", A project, "%")
                OR (`id` IN (1, 2, 3)
                    AND `id` <= 10
                    AND (`created` > 2014-12-31 OR `deleted` IS NULL))
            )
            ORDER BY `p`.`created` DESC
            LIMIT 5
        '));

        $qb = db_select('post', 'p')
            ->groupBy('p.cat_id');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`cat_id`');

        $qb = db_select('post', 'p')
            ->groupBy('p.cat_id')
            ->having(array('p.cat_id >' => 10));
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`cat_id` HAVING `p`.`cat_id` > :p_cat_id');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`cat_id` HAVING `p`.`cat_id` > 10');

        $qb = db_select('post', 'p')
            ->groupBy('p.cat_id')
            ->having(array(
                'p.cat_id >' => 10
            ));
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`cat_id` HAVING `p`.`cat_id` > :p_cat_id');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`cat_id` HAVING `p`.`cat_id` > 10');
    }

    public function testQueryBuilderCOUNT()
    {
        $qb = db_count('post');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(*) count FROM `post` `post`');

        $qb = db_count('post', 'id');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`id`) count FROM `post` `post`');

        $qb = db_count('post', 'id');
        $qb->count('user_id');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`id`) count, COUNT(`user_id`) FROM `post` `post`');

        $qb = db_count('post', 'id')
            ->where()
            ->condition('cat_id', 1);
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`id`) count FROM `post` `post` WHERE `cat_id` = :cat_id');
        $this->assertEqual($qb->getReadySQL(), 'SELECT COUNT(`id`) count FROM `post` `post` WHERE `cat_id` = 1');
    }

    public function testQueryBuilderAggregates()
    {
        $qb = db_max('post', 'postViews');
        $this->assertEqual($qb->getSQL(), 'SELECT MAX(`postViews`) max FROM `post` `post`');

        $qb = db_max('post', 'postViews', 'maxPostViews');
        $this->assertEqual($qb->getSQL(), 'SELECT MAX(`postViews`) maxPostViews FROM `post` `post`');

        $qb = db_min('post', 'postViews');
        $this->assertEqual($qb->getSQL(), 'SELECT MIN(`postViews`) min FROM `post` `post`');

        $qb = db_sum('post', 'postViews');
        $this->assertEqual($qb->getSQL(), 'SELECT SUM(`postViews`) sum FROM `post` `post`');

        $qb = db_avg('post', 'postViews');
        $this->assertEqual($qb->getSQL(), 'SELECT AVG(`postViews`) avg FROM `post` `post`');

        $qb = db_max('post', 'postViews');
        $qb->min('postViews', 'min');
        $this->assertEqual($qb->getSQL(), 'SELECT MAX(`postViews`) max, MIN(`postViews`) min FROM `post` `post`');

        $qb = db_select('post', 'p')
            ->max('postViews', 'max')
            ->min('postViews', 'min');
        $this->assertEqual($qb->getSQL(), 'SELECT MAX(`postViews`) max, MIN(`postViews`) min FROM `post` `p`');
    }

    public function testQueryBuilderFunctions()
    {
        $qb = db_select('post', 'p')
            ->fields('p', array(array('COUNT(*)', 'count')));
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(*) count FROM `post` `p`');

        $qb = db_select('post', 'p')
            ->field('COUNT(*)', 'count');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(*) count FROM `post` `p`');

        $qb = db_select('post', 'p')
            ->field('COUNT(*)', 'count')
            ->field('NOW()', 'now');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(*) count, NOW() now FROM `post` `p`');

        $qb = db_select('user', 'u')
            ->field('CONTACT(u.full_name, "-", u.username)', 'name');
        $this->assertEqual($qb->getSQL(), 'SELECT CONTACT(u.full_name, "-", u.username) name FROM `user` `u`');
    }

    public function testQueryBuilderResult()
    {
        $result = db_select('user', 'u')
            ->where()
            ->condition('LOWER(username)', 'admin')
            ->getSingleResult();
        $this->assertTrue(is_object($result));
        $this->assertEqual($result->username, 'admin');

        $userId = 1;
        $qb = db_count('user')
            ->where()
            ->condition('LOWER(username)', 'admin');
        if ($userId) {
            $qb->condition('id !=', $userId);
        }
        $count = $qb->fetch();
        $this->assertEqual($count, 0);

        db_insert('category', array('name' => 'PHP'));
        db_insert('category', array('name' => 'MySQL'));
        db_insert('category', array('name' => 'Framework'));

        $result = db_select('category')->getResult();
        $this->assertTrue(is_array($result));
        $this->assertEqual(count($result), 3);

        $result = db_select('category')->getSingleResult();
        $this->assertTrue(is_object($result));
        $this->assertEqual($result->name, 'PHP');

        $result = db_select('category')
            ->field('slug')
            ->orderBy('id')
            ->fetch();
        $this->assertEqual($result, 'php');

        $result = db_count('category')->fetch();
        $this->assertEqual($result, 3);

        $result = db_select('category', 'c')
            ->field('COUNT(*)', 'count')
            ->fetch();
        $this->assertEqual($result, 3);

        $result = db_select('category', 'c')
            ->fields('c', array('slug', 'name'))
            ->orderBy('name')
            ->getSingleResult();
        $this->assertTrue(is_object($result));
        $this->assertEqual($result->slug, 'framework');
        $this->assertEqual($result->name, 'Framework');
    }

    function testQueryBuilderExists()
    {
        foreach (range(1, 3) as $i) {
            db_insert('tag', array(
                'name' => 'tag' . $i,
            ));
        }

        $postId = db_insert('post', array(
            'title'     => 'Hello World',
            'body'      => 'Hello World body',
            'user_id'   => 1,
        ));

        db_insert('post_to_tag', array(
            'post_id' => $postId,
            'tag_id' => 1,
        ));

        $postId = db_insert('post', array(
            'title'     => 'Another Hello World',
            'body'      => 'Another Hello World body',
            'user_id'   => 1,
        ));

        db_insert('post_to_tag', array(
            'post_id' => $postId,
            'tag_id' => 1,
        ));

        db_insert('post_to_tag', array(
            'post_id' => $postId,
            'tag_id' => 3,
        ));

        // EXISTS test cases
        $tests = array(
            /* tag_id => result count */
            1 => 2,
            2 => 0,
            3 => 1,
        );

        foreach ($tests as $tagId => $count) {
            $subquery = db_select('post_to_tag', 'pt')
                ->where()
                ->condition('post_id', db_raw('p.id'))
                ->condition('tag_id', $tagId)
                ->getReadySQL();
            $this->assertEqual($subquery, 'SELECT `pt`.* FROM `post_to_tag` `pt` WHERE `post_id` = `p`.`id` AND `tag_id` = ' . $tagId);

            $qb = db_select('post', 'p')
                ->where()
                ->condition('deleted', null)
                ->exists($subquery);

            $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `deleted` IS NULL AND EXISTS (SELECT `pt`.* FROM `post_to_tag` `pt` WHERE `post_id` = `p`.`id` AND `tag_id` = ' . $tagId . ')');

            $result = $qb->getResult();
            $this->assertEqual(count($result), $count);
        }

        // NOT EXISTS test cases
        $tests = array(
            /* tag_id => result count */
            1 => 0,
            2 => 2,
            3 => 1,
        );

        foreach ($tests as $tagId => $count) {
            $subquery = db_select('post_to_tag', 'pt')
                ->where()
                ->condition('post_id', db_raw('p.id'))
                ->condition('tag_id', $tagId)
                ->getReadySQL();
            $this->assertEqual($subquery, 'SELECT `pt`.* FROM `post_to_tag` `pt` WHERE `post_id` = `p`.`id` AND `tag_id` = ' . $tagId);

            $qb = db_select('post', 'p')
                ->where()
                ->condition('deleted', null)
                ->notExists($subquery);

            $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `deleted` IS NULL AND NOT EXISTS (SELECT `pt`.* FROM `post_to_tag` `pt` WHERE `post_id` = `p`.`id` AND `tag_id` = ' . $tagId . ')');

            $result = $qb->getResult();
            $this->assertEqual(count($result), $count);
        }
    }
}
