<?php

namespace Sefirosweb\LaravelOdooConnector\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Sefirosweb\LaravelOdooConnector\Tests\TestCase;

class ServiceProviderBootTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertIsArray(config('laravel-odoo-connector'));
    }

    public function test_odoo_driver_is_extended_on_db(): void
    {
        // The SP registers a DB::extend('odoo', …) callback. It is resolved lazily
        // when a connection with driver=odoo is requested. Configuring a fake
        // odoo connection and then just asking the connection factory to
        // recognise the driver proves the extend callback was registered.
        config()->set('database.connections.odoo_fake', [
            'driver' => 'odoo',
            'host' => 'http://localhost:8069',
            'database' => 'test',
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $connection = DB::connection('odoo_fake');

        $this->assertInstanceOf(\Sefirosweb\LaravelOdooConnector\Database\OdooConnection::class, $connection);
    }
}
