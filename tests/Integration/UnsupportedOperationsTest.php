<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Exceptions\OdooUnsupportedOperationException;
use Sefirosweb\LaravelOdooConnector\Http\Models\SaleOrder;

/**
 * Regression tests for the actionable error messages the driver raises when
 * the caller asks for something Odoo's domain filter language cannot express.
 */
class UnsupportedOperationsTest extends IntegrationTestCase
{
    public function test_whereHas_throws_actionable_exception(): void
    {
        $this->expectException(OdooUnsupportedOperationException::class);
        $this->expectExceptionMessageMatches('/whereHas\(\).*not supported/i');

        SaleOrder::query()
            ->whereHas('sale_order_lines')
            ->select('id')
            ->first();
    }

    public function test_has_throws_actionable_exception(): void
    {
        $this->expectException(OdooUnsupportedOperationException::class);
        $this->expectExceptionMessageMatches('/whereHas\(\).*not supported/i');

        SaleOrder::query()
            ->has('sale_order_lines')
            ->select('id')
            ->first();
    }

    public function test_bad_credentials_throw_authentication_failed_direct(): void
    {
        // Regression test for OdooUserLogin detecting `result: false` responses
        // (Odoo returns that on bad creds instead of a JSON-RPC error payload).
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Odoo authentication failed/');

        new \Sefirosweb\LaravelOdooConnector\Rpc\OdooUserLogin(
            getenv('ODOO_HOST'),
            getenv('ODOO_DB'),
            'definitely-not-a-user@invalid.local',
            'definitely-wrong',
        );
    }

    public function test_on_routes_query_to_the_named_connection(): void
    {
        // Regression: OdooModel::getConnection() used to hardcode 'odoo'
        // ignoring $this->connection / ::on('foo') overrides. After honoring
        // getConnectionName(), a query fired via ::on('odoo_bad_creds')
        // really hits that connection — and bad creds surface the auth error.
        config()->set('database.connections.odoo_bad_creds', [
            'driver'   => 'odoo',
            'host'     => getenv('ODOO_HOST'),
            'database' => getenv('ODOO_DB'),
            'username' => 'definitely-not-a-user@invalid.local',
            'password' => 'definitely-wrong',
            'defaultOptions' => ['timeout' => 10],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Odoo authentication failed/');

        \Sefirosweb\LaravelOdooConnector\Http\Models\ResUser::on('odoo_bad_creds')
            ->select('id')
            ->limit(1)
            ->get();
    }
}
