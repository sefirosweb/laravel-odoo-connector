<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector;

use Illuminate\Support\ServiceProvider;
use Sefirosweb\LaravelOdooConnector\Commands\TestOdooConnection;
use Illuminate\Support\Facades\DB;
use Sefirosweb\LaravelOdooConnector\Database\OdooConnector;

class LaravelOdooConnectorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestOdooConnection::class,
            ]);
        }

        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'laravel-odoo-connector');

        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-odoo-connector.php'),
        ], 'config');

        DB::extend('odoo', function ($config, $connection) {
            $config['connection_name'] = $connection;
            return (new OdooConnector)->connect($config);
        });
    }
}
