<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Base class for database-backed unit test cases.
 *
 * @package     PHPLucidFrame\Test
 * @since       PHPLucidFrame v 3.5.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Test;

use LucidFrame\Core\Seeder;
use LucidFrame\Core\db\DatabaseException;
use LucidFrame\Core\db\SchemaManager;

/**
 * Base class for tests that need the application database.
 *
 * The active database namespace is never a literal: it is resolved at runtime
 * from `$lc_defaultDbSource` (inc/config.php), and its connection settings are
 * read from the `db` option of inc/parameter/test.php.
 *
 * The test database is prepared by replaying the exact logic of `schema:load`
 * + `db:seed` (non-interactive) against it:
 *
 *  1. once per namespace per test process, the schema definition of the active
 *     namespace is imported (purge + rebuild) with the schema lock file written
 *     under `db/build/test/`, never `db/build/`;
 *  2. seeding runs from `tests/db/seed/{namespace}/`;
 *  3. before every test case, the schema tables are truncated and re-seeded,
 *     which restores the fully-defined baseline (the light per-test cleanup).
 *
 * This makes the suite work with any database namespace, not just `sample`.
 * Subclasses may still override cleanup() to add case-specific fixtures on
 * top of the seeded data, and may set $refreshPerTest = TRUE to reload the
 * schema before every single test case.
 */
class LucidFrameDatabaseTestCase extends LucidFrameTestCase
{
    /**
     * @var boolean Set TRUE in a subclass to purge + rebuild the schema before
     * EVERY test case (fully precise but slow); by default (FALSE) the schema +
     * seed are loaded once per namespace per test process (a static flag keyed
     * by namespace) and each test case only runs the light per-test cleanup:
     * truncating the schema tables, re-seeding and cleanup().
     */
    protected $refreshPerTest = false;

    /** @var array Namespaces whose schema has been imported in this test process */
    private static $preparedSources = array();

    /** @var \LucidFrame\Core\db\Database|null Snapshot of the shared DB instance, restored after each test */
    protected $lcSavedDb;

    public function setUp()
    {
        parent::setUp();

        // Snapshot the shared DB instance so tests that create their own
        // Database instances (multi-driver tests etc.) cannot poison the
        // global state for subsequent tests
        $this->lcSavedDb = _app('db');

        $this->prepareDatabase();
    }

    public function tearDown()
    {
        // Restore the shared DB instance
        if ($this->lcSavedDb) {
            _app('db', $this->lcSavedDb);
        }
        $this->lcSavedDb = null;

        parent::tearDown();
    }

    /**
     * Clear the recorded error state of the shared connection
     *
     * The driver only records an error when one happens — successful queries
     * never reset it — and mysql_db_delete() uses db_errorNo() == 0 as its
     * success signal; a stale error from an earlier caught exception (e.g. an
     * intentional bad-query test) would otherwise make every later db_delete()
     * return FALSE even when the DELETE succeeded.
     *
     * @return void
     */
    protected function clearDbErrorState()
    {
        $db = _app('db');
        if (!is_object($db)) {
            return;
        }

        foreach (array($db, $db->getDriverInstance()) as $target) {
            if (!is_object($target)) {
                continue;
            }

            foreach (array('errorCode', 'error', 'lastException') as $property) {
                try {
                    $ref = new \ReflectionProperty($target, $property);
                    $ref->setAccessible(true);
                    $ref->setValue($target, null);
                } catch (\ReflectionException $e) {
                    // Property not present on this object; ignore
                }
            }
        }
    }

    /**
     * Prepare the test database for the active namespace
     * @return void
     */
    protected function prepareDatabase()
    {
        $source = _cfg('defaultDbSource');
        if ($source === null || $source === '') {
            throw new \RuntimeException('The test database namespace could not be resolved: $lc_defaultDbSource is empty in inc/config.php.');
        }

        // Session hygiene: a failed DDL import (or a test that opens a
        // transaction) can leave the shared connection inside an open
        // transaction with AUTOCOMMIT disabled; reset it so every test
        // starts from a clean autocommit session
        if (db_driver() !== 'pgsql') {
            db_query('SET AUTOCOMMIT=1');
        }

        // A caught DatabaseException never resets the recorded error state
        // (it is only set on failure), and mysql_db_delete() relies on
        // db_errorNo() == 0 to decide a delete succeeded — a stale error
        // makes every later db_delete() return FALSE even when it worked.
        // Clear the recorded error state before every test case.
        $this->clearDbErrorState();

        if (!isset(self::$preparedSources[$source]) || $this->refreshPerTest) {
            // Import the schema of the active namespace as defined by the
            // `schema:load` logic (non-interactive), then seed.
            //
            // The purge + rebuild DDL can transiently contend for locks (MySQL
            // deadlock 1213), and a DDL storm can crash a constrained MySQL
            // server, so the import runs only ONCE per namespace per test
            // process (a static flag keyed by namespace) unless a subclass
            // opts into $refreshPerTest = TRUE. The import and the seeding are
            // idempotent, so each retry converges.
            $attempts = 3;
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $this->loadSchema($source);
                    $this->seedDatabase($source);
                    self::$preparedSources[$source] = true;
                    $this->cleanup();
                    return;
                } catch (DatabaseException $e) {
                    // A failed DDL import may leave an open transaction on the
                    // shared connection; discard it before the next attempt
                    db_rollback();

                    if ($attempt === $attempts) {
                        throw $e;
                    }

                    usleep(100000); // pause briefly before the next attempt
                }
            }
        } else {
            // Light per-test cleanup (the default): the schema is static
            // between the test cases, so truncating the schema tables and
            // re-seeding restores the fully-defined baseline.
            //
            // A failed DDL import leaves its table dropped (the DROP is
            // committed while the CREATE is rolled back), so if the truncation
            // or the seeding hits a missing table the fully-precise purge +
            // schema + seed path heals the schema
            try {
                $this->truncateSchemaTables($source);
                $this->seedDatabase($source);
            } catch (DatabaseException $e) {
                db_rollback();
                $this->loadSchema($source);
                $this->seedDatabase($source);
            }
        }

        $this->cleanup();
    }

    /**
     * Truncate the tables of the active schema definition
     * @param string $source The database namespace
     * @return void
     */
    protected function truncateSchemaTables($source)
    {
        // The processed schema (the test lock file, written during loadSchema)
        // also contains the auto-generated m:m pivot tables
        $schema = _schema($source, true);
        if (!is_array($schema)) {
            $schema = _schema($source);
            if (!is_array($schema)) {
                return;
            }
        }

        db_disableForeignKeyCheck();
        foreach (array_keys($schema) as $table) {
            if ($table === '_options') {
                continue;
            }

            db_truncate($table);
        }
        db_enableForeignKeyCheck();
    }

    /**
     * Load the schema of the given namespace into the test database
     *
     * This mirrors SchemaLoadCommand::setDefinition() without the confirm() prompt.
     *
     * @param string $source The database namespace
     * @return void
     */
    protected function loadSchema($source)
    {
        $schema = _schema($source);
        if ($schema === null || $schema === false || !is_array($schema)) {
            throw new \RuntimeException(sprintf(
                'The schema definition for "%s" could not be loaded. Expected db%sschema.%s.php (or db%sschema.php).',
                $source,
                _DS_,
                $source,
                _DS_
            ));
        }

        // The test schema lock file must live in db/build/test/, never db/build/
        SchemaManager::setLockDir(DB . _DS_ . 'build' . _DS_ . 'test');

        // Transient MySQL deadlocks (SQLSTATE 40001 / error 1213) can occur while
        // the purge + rebuild DDL contends with metadata locks; the import is
        // idempotent (it purges and rebuilds), so retry a few times
        $attempts = 3;
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $sm = new SchemaManager($schema, $source);
                if ($sm->import($source)) {
                    // Keep the shared connection's schema manager in sync with
                    // the freshly imported schema (it was built from the
                    // application lock file at bootstrap)
                    $db = _app('db');
                    if (is_object($db)) {
                        $db->setSchemaManager(new SchemaManager(_schema($source, true), $source));
                    }

                    return;
                }
            } catch (DatabaseException $e) {
                $retryable = stripos($e->getMessage(), 'deadlock') !== false
                    || strpos($e->getMessage(), '1213') !== false;
                if (!$retryable || $attempt === $attempts) {
                    throw $e;
                }
            }

            usleep(100000); // pause briefly before the next attempt
        }

        throw new \RuntimeException(sprintf('Failed to import the "%s" schema into the test database.', $source));
    }

    /**
     * Seed the test database from tests/db/seed/{namespace}/
     *
     * This mirrors DbSeedCommand::setDefinition() without the confirm() prompt.
     * The seeder purges and re-inserts the tables that have a seed definition.
     *
     * @param string $source The database namespace
     * @return void
     */
    protected function seedDatabase($source)
    {
        $seedPath = TEST_DIR . 'db' . _DS_ . 'seed' . _DS_;
        $seedDir  = $seedPath . $source;

        if (!is_dir($seedDir)) {
            throw new \RuntimeException(sprintf(
                'No test seeders found at "%s". Create the folder with seed definition files for the "%s" namespace.',
                $seedDir,
                $source
            ));
        }

        $seeder = new Seeder($source);
        $seeder->setPath($seedPath);

        // Discard the seeder console output to keep the test reporter clean.
        // DatabaseException is rethrown so prepareDatabase() can retry the whole
        // preparation (a transient deadlock during the seeding purge is recovered
        // by a full purge + schema + seed); any other exception yields FALSE
        ob_start();
        try {
            $seeded = $seeder->run();
        } catch (DatabaseException $e) {
            ob_end_clean();
            throw $e;
        } catch (\Exception $e) {
            $seeded = false;
        }
        ob_end_clean();

        if (!$seeded) {
            throw new \RuntimeException(sprintf(
                'No seed data was loaded from "%s" for the "%s" namespace. Check that the seed tables exist in the schema definition.',
                $seedDir,
                $source
            ));
        }
    }

    /**
     * Hook for subclasses to add case-specific fixtures on top of the seeded data
     *
     * The base implementation adds nothing: purge + schema + seed already yield
     * a clean, fully-defined state.
     *
     * @return void
     */
    protected function cleanup()
    {
    }

    /**
     * Build a DB connection configuration array for the given driver,
     * reading every value from inc/parameter/env.inc via _env().
     *
     * The namespace is never a literal: it is resolved from `$lc_defaultDbSource`,
     * so the env.inc keys are `test.db.{$source}.{$driver}.*`.
     *
     * Supported drivers: "mysql" and "pgsql".
     *
     * @param string $driver "mysql" or "pgsql"
     * @return array
     */
    protected function getDbConfig($driver = 'mysql')
    {
        $driver = ($driver === 'pgsql') ? 'pgsql' : 'mysql';
        $source = _cfg('defaultDbSource');

        $config = array(
            'driver'    => $driver,
            'host'      => _env("test.db.{$source}.{$driver}.host"),
            'port'      => _env("test.db.{$source}.{$driver}.port"),
            'database'  => _env("test.db.{$source}.{$driver}.database"),
            'username'  => _env("test.db.{$source}.{$driver}.username"),
            'password'  => _env("test.db.{$source}.{$driver}.password"),
            'charset'   => _env("test.db.{$source}.{$driver}.charset"),
            'collation' => _env("test.db.{$source}.{$driver}.collation"),
        );

        if ($driver === 'pgsql') {
            $config['schema'] = _env("test.db.{$source}.pgsql.schema");
        }

        return $config;
    }
}
