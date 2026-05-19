<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hard safety wall: refuse to run tests against any non-disposable database.
     *
     * Tests use RefreshDatabase / migrate:fresh, which will wipe whatever DB they
     * point at. The local dev DB ("nds" on 127.0.0.1) was wiped once already.
     * This guard makes that mistake impossible by aborting the test run before
     * any migration touches the connection.
     *
     * Allowed connections:
     *   - sqlite with :memory: database (the phpunit.xml default)
     *   - any pgsql/mariadb DB whose name starts with "norman_test" or "ci_"
     *     (use these prefixes for CI service containers and local test DBs)
     */
    protected function setUp(): void
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        $this->guardAgainstRealDatabase();

        parent::setUp();
    }

    private function guardAgainstRealDatabase(): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? '');
        $host = (string) ($config['host'] ?? '');

        if ($connection === 'sqlite' && $database === ':memory:') {
            return;
        }

        if (preg_match('/^(norman_test|ci_)/i', $database) === 1) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Test suite refused to run: database "%s" on host "%s" via connection "%s" '
            .'is not a recognised disposable test database. Tests will run migrate:fresh '
            .'and would destroy this data. Allowed: sqlite :memory:, or a database whose '
            .'name starts with "norman_test" or "ci_". Override phpunit.xml only after '
            .'creating an isolated test database.',
            $database,
            $host,
            $connection,
        ));
    }
}
