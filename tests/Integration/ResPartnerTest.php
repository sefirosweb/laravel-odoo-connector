<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Tests\Integration;

use Sefirosweb\LaravelOdooConnector\Http\Models\ResPartner;

/**
 * Read-only tests against the `res.partner` model. These assume the Odoo
 * database has at least one partner record — which is guaranteed on any
 * freshly installed Odoo (the company partner record).
 *
 * Note on the assertions: Odoo JSON-RPC returns empty char/text fields as
 * `false` (not `null` or `""`). We account for that rather than pretending
 * it's a bug.
 */
class ResPartnerTest extends IntegrationTestCase
{
    public function test_first_partner_has_expected_columns(): void
    {
        $partner = ResPartner::select('id', 'name', 'email')->first();

        $this->assertNotNull($partner, 'Expected at least one res.partner record');
        $this->assertIsInt($partner->id);

        // Odoo JSON-RPC returns empty char/text fields as `false` rather than
        // `null` or `""`. Both string-with-content and `false` are "valid"
        // driver responses; we only fail if we got something else.
        $this->assertTrue(
            is_string($partner->name) || $partner->name === false,
            'Expected name to be a string or Odoo-false; got ' . var_export($partner->name, true),
        );
    }

    public function test_driver_can_decode_partners_with_real_names(): void
    {
        // Across a sample of partners at least one should have a populated name.
        // This guards against the driver mangling all responses (regression).
        $sample = ResPartner::select('id', 'name')->limit(20)->get();

        if ($sample->isEmpty()) {
            $this->markTestSkipped('No partners available in sample.');
        }

        $named = $sample->filter(fn ($p) => is_string($p->name) && $p->name !== '');

        $this->assertGreaterThan(
            0,
            $named->count(),
            'Expected at least one partner in the first 20 rows to have a non-empty string name.',
        );
    }

    public function test_where_filter_returns_matching_partner_by_id(): void
    {
        $first = ResPartner::select('id', 'name')->first();
        $this->assertNotNull($first);

        $match = ResPartner::select('id', 'name')
            ->where('id', $first->id)
            ->first();

        $this->assertNotNull($match);
        $this->assertSame($first->id, $match->id);
        $this->assertSame($first->name, $match->name);
    }

    public function test_find_returns_same_record_as_where(): void
    {
        $first = ResPartner::select('id')->first();
        $this->assertNotNull($first);

        $found = ResPartner::find($first->id);

        $this->assertNotNull($found);
        $this->assertSame($first->id, $found->id);
    }

    public function test_find_returns_null_for_nonexistent_id(): void
    {
        // Use a value that fits in a 32-bit int but is far above any realistic
        // row id. PHP_INT_MAX would overflow Odoo/Postgres int4 and produce an
        // out-of-range error, which is a different path than "record not found".
        $this->assertNull(ResPartner::find(999_999_999));
    }

    public function test_limit_caps_result_set(): void
    {
        $partners = ResPartner::select('id', 'name')->limit(3)->get();

        $this->assertLessThanOrEqual(3, $partners->count());
        $this->assertGreaterThan(0, $partners->count());
    }
}
