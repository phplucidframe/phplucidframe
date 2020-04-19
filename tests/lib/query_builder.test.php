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
            ->fields('p', array('postId', 'postTitle'))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`postId`, `p`.`postTitle` FROM `post` `p`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p', array('postId', array('postTitle', 'title')))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`postId`, `p`.`postTitle` `title` FROM `post` `p`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p', array('postId', 'postTitle'))
            ->fields('u')
            ->join('user', 'u', 'p.uid = u.uid')
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`postId`, `p`.`postTitle`, `u`.* FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->where()
            ->condition('p.postId', 1);
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`postId` = :p.postId');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`postId` = 1');

        $qb = db_select('post', 'p')
            ->where()
            ->condition('p.created >', '2015-11-08');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`created` > :p.created');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `p`.`created` > 2015-11-08');

        $qb = db_select('post', 'p')
            ->fields('p', array('postId', 'postTitle'))
            ->fields('u', array('fullName', 'username'))
            ->join('user', 'u', 'p.uid = u.uid')
            ->leftJoin('category', 'c', 'p.catId = c.catId')
            ->where()
            ->condition('catId', 1)
            ->condition('uid', 1)
            ->orderBy('p.created', 'desc')
            ->orderBy('c.catId');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.`postId`, `p`.`postTitle`, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            LEFT JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE `catId` = :catId
            AND `uid` = :uid
            ORDER BY `p`.`created` DESC, `c`.`catId` ASC
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.`postId`, `p`.`postTitle`, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            LEFT JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE `catId` = 1
            AND `uid` = 1
            ORDER BY `p`.`created` DESC, `c`.`catId` ASC
        '));

        $qb = db_select('post', 'p')
            ->orWhere()
            ->condition('catId', 1)
            ->condition('catId', 2);
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` WHERE `catId` = :catId OR `catId` = :catId0');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` WHERE `catId` = 1 OR `catId` = 2');

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('fullName', 'username'))
            ->join('user', 'u', 'p.uid = u.uid')
            ->leftJoin('category', 'c', 'p.catId = c.catId')
            ->orWhere(array(
                'postTitle like' => 'A project',
                'and' => array(
                    'postId' => array(1, 2, 3),
                    'uid' => 1
                )
            ))
            ->orderBy('p.created', 'desc');
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            LEFT JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE ( `postTitle` LIKE CONCAT("%", :postTitle, "%")
            OR (`postId` IN (:postId0, :postId1, :postId2) AND `uid` = :uid) )
            ORDER BY `p`.`created` DESC
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            LEFT JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE ( `postTitle` LIKE CONCAT("%", A project, "%")
            OR (`postId` IN (1, 2, 3) AND `uid` = 1) )
            ORDER BY `p`.`created` DESC
        '));

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('fullName', 'username'))
            ->join('user', 'u', 'p.uid = u.uid')
            ->join('category', 'c', 'p.catId = c.catId')
            ->where(array(
                'postTitle like' => 'A project',
                'or' => array(
                    'postId' => array(1, 2, 3),
                    'uid' => 1
                )
            ))
            ->orderBy('p.created', 'desc')
            ->limit(0, 20);
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            INNER JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE ( `postTitle` LIKE CONCAT("%", :postTitle, "%")
            AND (`postId` IN (:postId0, :postId1, :postId2) OR `uid` = :uid) )
            ORDER BY `p`.`created` DESC
            LIMIT 0, 20
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            INNER JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE ( `postTitle` LIKE CONCAT("%", A project, "%")
            AND (`postId` IN (1, 2, 3) OR `uid` = 1) )
            ORDER BY `p`.`created` DESC
            LIMIT 0, 20
        '));

        $qb = db_select('post', 'p')
            ->fields('p')
            ->fields('u', array('fullName', 'username'))
            ->join('user', 'u', 'p.uid = u.uid')
            ->join('category', 'c', 'p.catId = c.catId')
            ->orWhere(array(
                'postTitle nlike' => 'A project',
                'and' => array(
                    'postId' => array(1, 2, 3),
                    'postId <=' => 10,
                    'or' => array(
                        'created >' => '2014-12-31',
                        'deleted' => null
                    )
                )
            ))
            ->orderBy('p.created', 'desc')
            ->limit(5);
        $this->assertEqual($qb->getSQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            INNER JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE (
                `postTitle` NOT LIKE CONCAT("%", :postTitle, "%")
                OR (`postId` IN (:postId0, :postId1, :postId2)
                    AND `postId` <= :postId3
                    AND (`created` > :created OR `deleted` IS NULL))
            )
            ORDER BY `p`.`created` DESC
            LIMIT 5
        '));
        $this->assertEqual($qb->getReadySQL(), self::oneline('
            SELECT `p`.*, `u`.`fullName`, `u`.`username` FROM `post` `p`
            INNER JOIN `user` `u` ON `p`.`uid` = `u`.`uid`
            INNER JOIN `category` `c` ON `p`.`catId` = `c`.`catId`
            WHERE (
                `postTitle` NOT LIKE CONCAT("%", A project, "%")
                OR (`postId` IN (1, 2, 3)
                    AND `postId` <= 10
                    AND (`created` > 2014-12-31 OR `deleted` IS NULL))
            )
            ORDER BY `p`.`created` DESC
            LIMIT 5
        '));

        $qb = db_select('post', 'p')
            ->groupBy('p.catId');
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`catId`');

        $qb = db_select('post', 'p')
            ->groupBy('p.catId')
            ->having(array('p.catId >' => 10));
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`catId` HAVING `p`.`catId` > :p.catId');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`catId` HAVING `p`.`catId` > 10');

        $qb = db_select('post', 'p')
            ->groupBy('p.catId')
            ->having(array(
                'p.catId >' => 10
            ));
        $this->assertEqual($qb->getSQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`catId` HAVING `p`.`catId` > :p.catId');
        $this->assertEqual($qb->getReadySQL(), 'SELECT `p`.* FROM `post` `p` GROUP BY `p`.`catId` HAVING `p`.`catId` > 10');
    }

    public function testQueryBuilderCOUNT()
    {
        $qb = db_count('post');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(*) count FROM `post` `post`');

        $qb = db_count('post', 'postId');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`postId`) count FROM `post` `post`');

        $qb = db_count('post', 'postId');
        $qb->count('uid');
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`postId`) count, COUNT(`uid`) FROM `post` `post`');

        $qb = db_count('post', 'postId')
            ->where()
            ->condition('catId', 1);
        $this->assertEqual($qb->getSQL(), 'SELECT COUNT(`postId`) count FROM `post` `post` WHERE `catId` = :catId');
        $this->assertEqual($qb->getReadySQL(), 'SELECT COUNT(`postId`) count FROM `post` `post` WHERE `catId` = 1');
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
            ->field('CONTACT(u.fullName, "-", u.username)', 'name');
        $this->assertEqual($qb->getSQL(), 'SELECT CONTACT(u.fullName, "-", u.username) name FROM `user` `u`');
    }

    public function testQueryBuilderResult()
    {
        db_insert('category', array('catName' => 'PHP'));
        db_insert('category', array('catName' => 'MySQL'));
        db_insert('category', array('catName' => 'Framework'));

        $result = db_select('category')->getResult();
        $this->assertTrue(is_array($result));
        $this->assertEqual(count($result), 3);

        $result = db_select('category')->getSingleResult();
        $this->assertTrue(is_object($result));
        $this->assertEqual($result->catName, 'PHP');

        $result = db_select('category')
            ->field('slug')
            ->orderBy('catId')
            ->fetch();
        $this->assertEqual($result, 'php');

        $result = db_count('category')->fetch();
        $this->assertEqual($result, 3);

        $result = db_select('category', 'c')
            ->field('COUNT(*)', 'count')
            ->fetch();
        $this->assertEqual($result, 3);

        $result = db_select('category', 'c')
            ->fields('c', array('slug', 'catName'))
            ->orderBy('catName')
            ->getSingleResult();
        $this->assertTrue(is_object($result));
        $this->assertEqual($result->slug, 'framework');
        $this->assertEqual($result->catName, 'Framework');
    }
}
