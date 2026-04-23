<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Illuminate\Support\Facades\DB;
use Sefirosweb\LaravelOdooConnector\Database\OdooConnection;
use Sefirosweb\LaravelOdooConnector\Http\Models\ResUser;

class ConnectionTest extends IntegrationTestCase
{
    public function test_driver_resolves_to_odoo_connection(): void
    {
        $connection = DB::connection('odoo');

        $this->assertInstanceOf(OdooConnection::class, $connection);
    }

    public function test_authentication_succeeds_and_returns_a_user(): void
    {
        // End-to-end sanity check: the configured ODOO_USERNAME must resolve
        // to a real res.users row. If JSON-RPC auth fails we'd get an exception
        // before reaching the assertion.
        $user = ResUser::select('id', 'login')
            ->where('login', getenv('ODOO_USERNAME'))
            ->first();

        $this->assertNotNull($user, 'Authenticated user should be resolvable via res.users');
        $this->assertSame(getenv('ODOO_USERNAME'), $user->login);
    }

    public function test_invalid_model_raises_an_exception(): void
    {
        // Odoo's error surface is inconsistent for bad credentials / bad
        // databases (some setups return `result: false` instead of an error
        // payload), so we don't test those. Calling an unknown model on a
        // valid connection DOES consistently return an error payload, which
        // the driver re-throws as an Exception.
        $this->expectException(\Throwable::class);

        \Sefirosweb\LaravelOdooConnector\Rpc\OdooJsonRpc::execute_kw(
            'definitely.no.such.model.' . uniqid(),
            'search_read',
            [[]],
            ['limit' => 1],
        );
    }
}
