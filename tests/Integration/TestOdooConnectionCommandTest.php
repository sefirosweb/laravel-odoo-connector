<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Commands\TestOdooConnection;

/**
 * Covers the `test:odoo` artisan command against a real Odoo instance.
 * Runs only when the ODOO_* env vars are set (see IntegrationTestCase).
 */
class TestOdooConnectionCommandTest extends IntegrationTestCase
{
    public function test_command_is_registered_when_running_in_console(): void
    {
        // In CI / queue workers the service provider only registers the
        // command if `runningInConsole()` is true. Testbench runs in console
        // mode by default, so we assert the command is discoverable.
        $commands = \Illuminate\Support\Facades\Artisan::all();

        $this->assertArrayHasKey('test:odoo', $commands);
        $this->assertInstanceOf(TestOdooConnection::class, $commands['test:odoo']);
    }

    public function test_command_succeeds_against_a_reachable_odoo(): void
    {
        $this->artisan('test:odoo')->assertExitCode(0);
    }

    public function test_command_prints_a_readable_banner(): void
    {
        // Regression: the command used to `dd()` its output, killing the
        // process before the return value was asserted. After the v12.0.3
        // fix it must print a banner and return SUCCESS.
        $this->artisan('test:odoo')
            ->expectsOutputToContain('Odoo')
            ->assertExitCode(0);
    }
}
