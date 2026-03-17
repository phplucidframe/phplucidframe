<?php

use LucidFrame\Core\db\Database;
use LucidFrame\Core\db\drivers\DriverFactory;
use LucidFrame\Core\QueryBuilder;
use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for QueryBuilder.php with PostgreSQL driver
 */
class QueryBuilderPostgreSQLTestCase extends LucidFrameTestCase
{
    private $originalDb;
    private $postgresDb;

    public function setUp()
    {
        parent::setUp();

        // Store original database instance
        $this->originalDb = _app('db');

        // Create PostgreSQL configuration for testing
        $config = array(
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
            'charset' => 'utf8',
            'schema' => 'public'
        );

        try {
            // Create PostgreSQL driver instance for testing
            $driver = DriverFactory::create($config);
            $this->postgresDb = new Database();

            // Use reflection to set the driver instance for testing
            $reflection = new ReflectionClass($this->postgresDb);
            $driverProperty = $reflection->getProperty('driverInstance');
            $driverProperty->setAccessible(true);
            $driverProperty->setValue($this->postgresDb, $driver);

            // Set the test database as the active database
            _app('db', $this->postgresDb);
        } catch (Exception $e) {
            // Skip tests if PostgreSQL is not available
            $this->skip('PostgreSQL not available for testing: ' . $e->getMessage());
        }
    }

    public function tearDown()
    {
        // Restore original database instance
        if ($this->originalDb) {
            _app('db', $this->originalDb);
        }
        parent::tearDown();
    }

    public function testPostgreSQLIdentifierQuoting()
    {
        $qb = new QueryBuilder('post', 'p');
        $sql = $qb->getSQL();

        // PostgreSQL should use double quotes for identifiers
        $this->assertEqual($sql, 'SELECT "p".* FROM "post" "p"');
    }

    public function testPostgreSQLFieldQuoting()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->fields('p', array('id', 'title', 'content'));
        $sql = $qb->getSQL();

        $expected = 'SELECT "p"."id", "p"."title", "p"."content" FROM "post" "p"';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLJoinQuoting()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->fields('p', array('id', 'title'))
           ->fields('u', array('username'))
           ->join('user', 'u', 'p.user_id = u.id');

        $sql = $qb->getSQL();
        $expected = self::oneline('
            SELECT "p"."id", "p"."title", "u"."username" FROM "post" "p"
            INNER JOIN "user" "u" ON "p"."user_id" = "u"."id"
        ');

        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLLimitOffset()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->limit(10, 20); // offset=10, limit=20
        $sql = $qb->getSQL();

        // PostgreSQL uses LIMIT count OFFSET offset syntax
        $expected = 'SELECT "p".* FROM "post" "p" LIMIT 20 OFFSET 10';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLLimitOnly()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->limit(15);
        $sql = $qb->getSQL();

        $expected = 'SELECT "p".* FROM "post" "p" LIMIT 15';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLLikeExpressions()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('title like' => 'test'));
        $sql = $qb->getSQL();

        // PostgreSQL uses || for concatenation instead of CONCAT()
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "title" LIKE \'%\' || :title || \'%\'';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLLikeVariations()
    {
        // Test like%~ (ends with)
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('title like%~' => 'test'));
        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "title" LIKE \'%\' || :title';
        $this->assertEqual($sql, $expected);

        // Test like~% (starts with)
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('title like~%' => 'test'));
        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "title" LIKE :title || \'%\'';
        $this->assertEqual($sql, $expected);

        // Test nlike (not like)
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('title nlike' => 'test'));
        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "title" NOT LIKE \'%\' || :title || \'%\'';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLComplexQuery()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->fields('p', array('id', 'title'))
           ->fields('u', array('username', 'email'))
           ->fields('c', array('name'))
           ->join('user', 'u', 'p.user_id = u.id')
           ->leftJoin('category', 'c', 'p.category_id = c.id')
           ->where(array(
               'p.status' => 'published',
               'title like' => 'PostgreSQL'
           ))
           ->orderBy('p.created_at', 'DESC')
           ->limit(5, 10);

        $sql = $qb->getSQL();
        $expected = self::oneline('
            SELECT "p"."id", "p"."title", "u"."username", "u"."email", "c"."name" FROM "post" "p"
            INNER JOIN "user" "u" ON "p"."user_id" = "u"."id"
            LEFT JOIN "category" "c" ON "p"."category_id" = "c"."id"
            WHERE 1 = 1 AND "p"."status" = :p_status AND "title" LIKE \'%\' || :title || \'%\'
            ORDER BY "p"."created_at" DESC
            LIMIT 10 OFFSET 5
        ');

        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLAggregates()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->count('id', 'post_count')
           ->max('created_at', 'latest_post')
           ->min('created_at', 'earliest_post');

        $sql = $qb->getSQL();
        $expected = 'SELECT COUNT("id") "post_count", MAX("created_at") "latest_post", MIN("created_at") "earliest_post" FROM "post" "p"';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLGroupByHaving()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->fields('p', array('category_id'))
           ->count('id', 'post_count')
           ->groupBy('p.category_id')
           ->having(array('COUNT(id) >' => 5));

        $sql = $qb->getSQL();
        $expected = 'SELECT "p"."category_id", COUNT("id") "post_count" FROM "post" "p" GROUP BY "p"."category_id" HAVING COUNT(id) > :COUNT_id_';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLSubqueryExists()
    {
        $subquery = 'SELECT 1 FROM "comment" "c" WHERE "c"."post_id" = "p"."id"';

        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('status' => 'published'))
           ->exists($subquery);

        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "status" = :status AND EXISTS (' . $subquery . ')';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLOrConditions()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->orWhere(array(
            'status' => 'published',
            'status' => 'draft'
        ));

        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "status" = :status OR "status" = :status0';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLInConditions()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('category_id' => array(1, 2, 3, 4)));

        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "category_id" IN (:category_id0, :category_id1, :category_id2, :category_id3)';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLBetweenConditions()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->where(array('created_at between' => array('2023-01-01', '2023-12-31')));

        $sql = $qb->getSQL();
        $expected = 'SELECT "p".* FROM "post" "p" WHERE 1 = 1 AND "created_at" BETWEEN :created_at0 AND :created_at1';
        $this->assertEqual($sql, $expected);
    }

    public function testPostgreSQLRawExpressions()
    {
        $qb = new QueryBuilder('post', 'p');
        $qb->field('COUNT(*)', 'total_posts')
           ->field('AVG(view_count)', 'avg_views')
           ->orderBy(QueryBuilder::raw('RANDOM()'));

        $sql = $qb->getSQL();
        $expected = 'SELECT COUNT(*) total_posts, AVG(view_count) avg_views FROM "post" "p" ORDER BY RANDOM()';
        $this->assertEqual($sql, $expected);
    }

    public function testBackwardCompatibilityWithMySQL()
    {
        // Restore MySQL database for this test
        _app('db', $this->originalDb);

        $qb = new QueryBuilder('post', 'p');
        $qb->limit(5, 10); // offset=5, limit=10
        $sql = $qb->getSQL();

        // Should use MySQL syntax when MySQL driver is active
        $expected = 'SELECT `p`.* FROM `post` `p` LIMIT 5, 10';
        $this->assertEqual($sql, $expected);

        // Restore PostgreSQL database
        _app('db', $this->postgresDb);
    }
}
