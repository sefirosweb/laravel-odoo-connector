<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Http\Models\PurchaseOrder;
use Sefirosweb\LaravelOdooConnector\Http\Models\PurchaseOrderLine;

/**
 * A second "standard" Odoo model to demonstrate the connector works across
 * arbitrary models (not just sale.order). If both sale and purchase flows
 * resolve through the same OdooEloquentBuilder + OdooProcessor, the driver
 * is generic.
 */
class PurchaseOrderTest extends IntegrationTestCase
{
    public function test_first_purchase_order_loads(): void
    {
        $order = PurchaseOrder::select('id', 'name', 'partner_id')->first();

        if ($order === null) {
            $this->markTestSkipped('No purchase.order rows in Odoo — skipping.');
        }

        $this->assertIsInt($order->id);
        $this->assertTrue(
            is_string($order->name) && $order->name !== '',
            'Expected a non-empty order name; got ' . var_export($order->name, true),
        );
    }

    public function test_has_many_lines_relation_returns_a_collection(): void
    {
        // Seed from the child side to avoid whereHas / EXISTS.
        $line = PurchaseOrderLine::select('id', 'order_id')->first();

        if ($line === null) {
            $this->markTestSkipped('No purchase.order.line rows — skipping.');
        }

        $orderId = self::unwrapMany2oneId($line->order_id);
        $order = PurchaseOrder::select('id')->find($orderId);

        $this->assertNotNull($order, 'Seeded line points at an order that should exist');

        $lines = $order->purchase_order_lines;

        $this->assertGreaterThan(0, $lines->count());
        $this->assertContainsOnlyInstancesOf(PurchaseOrderLine::class, $lines);

        foreach ($lines as $purchaseLine) {
            $this->assertSame($order->id, self::unwrapMany2oneId($purchaseLine->order_id));
        }
    }

    private static function unwrapMany2oneId(mixed $value): int
    {
        if (is_array($value) && array_key_exists(0, $value)) {
            return (int) $value[0];
        }
        return (int) $value;
    }
}
