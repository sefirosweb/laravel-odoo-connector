<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Tests\TestCase as BaseTestCase;

/**
 * Base class for tests that hit a real Odoo server via JSON-RPC.
 *
 * Required environment variables:
 *   - ODOO_HOST     (e.g. http://host.docker.internal:8069)
 *   - ODOO_DB
 *   - ODOO_USERNAME
 *   - ODOO_PASSWORD
 *
 * When any of them is missing, every test in this hierarchy is skipped so
 * the base suite still passes on contributors who have no Odoo access.
 */
abstract class IntegrationTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        foreach (['ODOO_HOST', 'ODOO_DB', 'ODOO_USERNAME', 'ODOO_PASSWORD'] as $var) {
            if (self::resolveEnv($var) === null) {
                $this->markTestSkipped("{$var} is not set — skipping Odoo integration tests.");
            }
        }

        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.odoo', [
            'driver'   => 'odoo',
            'host'     => self::resolveEnv('ODOO_HOST'),
            'database' => self::resolveEnv('ODOO_DB'),
            'username' => self::resolveEnv('ODOO_USERNAME'),
            'password' => self::resolveEnv('ODOO_PASSWORD'),
            'defaultOptions' => [
                'timeout' => (int) (self::resolveEnv('ODOO_TIMEOUT') ?? 20),
                'context' => [
                    'lang' => self::resolveEnv('ODOO_LANG') ?? 'en_US',
                ],
            ],
        ]);
    }

    private static function resolveEnv(string $name): ?string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return null;
        }
        return $value;
    }
}
