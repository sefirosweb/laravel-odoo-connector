<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Http\Models\ResPartner;
use Sefirosweb\LaravelOdooConnector\Http\Models\SaleOrder;
use Sefirosweb\LaravelOdooConnector\Http\Models\SaleOrderLine;

/**
 * Exercises the `HasMany` and `BelongsTo` relation builders shipped by the
 * package (`Database\Relelations\HasMany.php`, `BelongsTo.php`) — these are
 * the Odoo-specific replacements for Eloquent's default ones and are what
 * the whole connector ORM layer hinges on.
 *
 * Important Odoo quirks exposed here:
 *  - many2one fields are returned as tuples `[id, display_name]`, never as
 *    plain ids. The helper `unwrapMany2oneId()` below compensates.
 *  - `whereHas` compiles to SQL `EXISTS` which OdooGrammar rejects, so we
 *    discover a parent record by seeding from the child side.
 */
class SaleOrderTest extends IntegrationTestCase
{
    /** @return array{0: SaleOrder, 1: SaleOrderLine} */
    private function seedOrderAndLine(): array
    {
        // Pick a sale.order.line first, then hop up to its sale.order. This
        // sidesteps whereHas / EXISTS which the driver cannot compile.
        $line = SaleOrderLine::select('id', 'order_id', 'product_id')
            ->orderBy('id')
            ->first();

        if ($line === null) {
            $this->markTestSkipped('No sale.order.line rows in Odoo — skipping.');
        }

        $orderId = self::unwrapMany2oneId($line->order_id);
        $order = SaleOrder::select('id', 'name', 'partner_id')->find($orderId);

        if ($order === null) {
            $this->markTestSkipped('Seeded sale.order.line points at missing order — skipping.');
        }

        return [$order, $line];
    }

    public function test_sale_order_has_many_order_lines(): void
    {
        [$order, ] = $this->seedOrderAndLine();

        $lines = $order->sale_order_lines;

        $this->assertGreaterThan(0, $lines->count());
        $this->assertContainsOnlyInstancesOf(SaleOrderLine::class, $lines);

        foreach ($lines as $line) {
            $this->assertSame($order->id, self::unwrapMany2oneId($line->order_id));
        }
    }

    public function test_eager_loading_with_populates_relation(): void
    {
        [$order, ] = $this->seedOrderAndLine();

        $reloaded = SaleOrder::select('id', 'name')
            ->with('sale_order_lines:id,order_id,product_id,name')
            ->find($order->id);

        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->relationLoaded('sale_order_lines'));
        $this->assertGreaterThan(0, $reloaded->sale_order_lines->count());
    }

    public function test_belongs_to_partner_resolves_when_partner_exists(): void
    {
        [$order, ] = $this->seedOrderAndLine();

        if ($order->partner_id === null || $order->partner_id === false) {
            $this->markTestSkipped('Seeded order has no partner — skipping BelongsTo check.');
        }

        $partner = $order->partner;

        $this->assertInstanceOf(ResPartner::class, $partner);
        $this->assertSame(self::unwrapMany2oneId($order->partner_id), $partner->id);
    }

    /**
     * Odoo JSON-RPC returns many2one fields as `[id, display_name]` tuples
     * rather than plain integer foreign keys. This helper normalises both
     * shapes to a plain int so assertions stay readable.
     */
    private static function unwrapMany2oneId(mixed $value): int
    {
        if (is_array($value) && array_key_exists(0, $value)) {
            return (int) $value[0];
        }
        return (int) $value;
    }
}
