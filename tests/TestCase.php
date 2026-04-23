<?php

namespace Sefirosweb\LaravelOdooConnector\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sefirosweb\LaravelOdooConnector\LaravelOdooConnectorServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelOdooConnectorServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
