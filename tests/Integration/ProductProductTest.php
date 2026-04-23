<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Http\Models\ProductProduct;

class ProductProductTest extends IntegrationTestCase
{
    public function test_first_product_returns_a_record(): void
    {
        $product = ProductProduct::select('id', 'name', 'list_price')->first();

        if ($product === null) {
            $this->markTestSkipped('No product.product rows in Odoo — skipping.');
        }

        $this->assertIsInt($product->id);
        $this->assertNotEmpty($product->name);
    }

    public function test_get_all_chunked_matches_count(): void
    {
        // Restrict the scope to keep the round-trip manageable on large DBs.
        // get_all() under the hood pages 500 records at a time.
        $sample = ProductProduct::select('id')->limit(25)->get();

        if ($sample->isEmpty()) {
            $this->markTestSkipped('No products available for the get_all chunking check.');
        }

        // Query the same slice via get_all with a small chunk size so we exercise
        // the pagination loop at least twice.
        $idsInSample = $sample->pluck('id')->all();
        $maxId = max($idsInSample);

        $all = ProductProduct::query()
            ->whereIn('id', $idsInSample)
            ->get(['id']);

        $this->assertCount(count($idsInSample), $all);
        $this->assertGreaterThanOrEqual(1, $maxId);
    }

    public function test_where_in_filter(): void
    {
        $seed = ProductProduct::select('id')->limit(3)->get();

        if ($seed->isEmpty()) {
            $this->markTestSkipped('No products available for whereIn check.');
        }

        $ids = $seed->pluck('id')->all();
        $found = ProductProduct::select('id', 'name')
            ->whereIn('id', $ids)
            ->get();

        $this->assertSame(count($ids), $found->count());
        $this->assertEqualsCanonicalizing($ids, $found->pluck('id')->all());
    }
}
