# Changelog

All notable changes to `sefirosweb/laravel-odoo-connector` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [12.0.1] - 2026-04-23

### Added
- **New exception class `Sefirosweb\LaravelOdooConnector\Exceptions\OdooUnsupportedOperationException`** raised by `OdooGrammar` when the caller tries to compile an operation Odoo's JSON-RPC domain filter language cannot express. Specifically `whereHas()` / `has()` / `whereDoesntHave()` now throw a clear, actionable message pointing at the `whereIn()` workaround instead of the generic `"Unsupported where type: Exists"`.
- **Integration test suite** (`tests/Integration/`) exercising the driver against a real Odoo instance over JSON-RPC. Covers authentication, `ResPartner`, `ProductProduct`, `SaleOrder` + `HasMany` / `BelongsTo` relations, `PurchaseOrder`, and the actionable error paths. All Integration tests auto-skip when the `ODOO_*` environment variables are not set, so the default suite stays runnable offline.
- `phpunit.xml` now ships two test suites: `Feature` (offline) and `Integration` (requires Odoo).
- `README` documents known limitations of the JSON-RPC driver (no `whereHas`, many2one tuples, empty-as-false) and the run instructions for both suites.

### Changed
- **`OdooUserLogin::login()` fails loudly on bad credentials**: Odoo's `common/login` returns `{"result": false}` for an invalid username / password / database — not an error payload. The driver previously silently set `uid = false`, producing confusing downstream failures. It now throws an `Exception` with the attempted host, database and user.
- **`OdooModel::getConnection()` now honours `$this->getConnectionName()`** instead of hardcoding `'odoo'`. `Model::on('other_odoo')` and subclasses that only override `protected $connection = 'other_odoo'` now route queries to the named connection as documented. Subclasses previously had to override both `$connection` and `getConnection()` for multi-connection setups to work.
- **Typo fix**: renamed the internal property `OdooJsonRpc::$conections` → `$connections` and the internal config key `$config['conection']` → `$config['connection_name']`. These are internal plumbing and are not expected to be referenced by consumer code.
- Cosmetic: `"Cant connect to to odoo server!"` error message → `"Cannot reach Odoo server at <url>"`.

## [12.0.0] - 2026-04-23

### Added
- Initial Laravel 12 release. Requires PHP `^8.2` and `laravel/framework ^12.0`.
- Orchestra Testbench baseline suite (service provider boot, `DB::extend('odoo', …)` registration).

### Removed
- Support for Laravel `< 12` on this branch. Older majors live on the `9.x` branch with their legacy tag lineage.
- The prior `tests/` harness (`RandomTests.php`, `ModelRelationsTest.php` etc.) was dropped in favour of the Testbench layout; it relied on the host Laravel app's `bootstrap/app.php` and did not run in isolation.
