# Laravel Odoo Connector

Use Laravel's Eloquent ORM against an [Odoo](https://www.odoo.com/) instance, through Odoo's JSON-RPC API.

Reference: [Odoo Web Services Documentation (JSON-RPC)](https://www.odoo.com/documentation/master/developer/howtos/web_services.html).

## Why JSON-RPC instead of a raw PostgreSQL connection?

Connecting Laravel directly to Odoo's Postgres database is simpler on paper but bypasses Odoo's business logic. Every time Odoo's ORM handles a write it runs a chain of Python-side triggers — invoice generation, stock moves, mail activities, etc. — that a raw SQL `INSERT` will silently skip.

This package goes through Odoo's JSON-RPC layer so those side effects still fire. It also lets you call **model actions** (the equivalent of clicking a button in the Odoo UI), which is impossible from raw SQL:

```php
$saleOrder = SaleOrder::find(1);
$saleOrder->action('action_confirm');
// Triggers the "Confirm" button on sale.order — see Odoo's sale/models/sale_order.py
```

## Requirements

- PHP `^8.2`
- Laravel `^12.0`
- An Odoo instance with JSON-RPC access enabled and valid credentials (username + API key or password).

## Installation

```bash
composer require sefirosweb/laravel-odoo-connector:^12.0
```

The service provider auto-registers via Laravel's package discovery and registers an `odoo` driver on Laravel's connection manager.

## Configuration

### 1. Declare the connection in `config/database.php`

```php
'connections' => [
    // ...
    'odoo' => [
        'driver'   => 'odoo',
        'host'     => env('ODOO_HOST',     'https://your-odoo-host.com'),
        'database' => env('ODOO_DB',       'db_name'),
        'username' => env('ODOO_USERNAME', 'user'),
        'password' => env('ODOO_PASSWORD', 'api_key'),
        'defaultOptions' => [
            'timeout' => 20,
            'context' => [
                'lang' => 'es_ES',
            ],
        ],
    ],
],
```

And in `.env`:

```dotenv
ODOO_HOST=https://your-odoo-host.com
ODOO_DB=db_name
ODOO_USERNAME=user
ODOO_PASSWORD=api_key
```

### 2. (Optional) Publish the config to override shipped models

```bash
php artisan vendor:publish --provider="Sefirosweb\LaravelOdooConnector\LaravelOdooConnectorServiceProvider" --tag=config --force
```

Then in `config/laravel-odoo-connector.php`:

```php
return [
    'ProductProduct'  => App\Odoo\CustomProductProduct::class,
    'ProductTemplate' => Sefirosweb\LaravelOdooConnector\Http\Models\ProductTemplate::class,
    'ResLang'         => Sefirosweb\LaravelOdooConnector\Http\Models\ResLang::class,
    // ...
];
```

Every model in the package resolves related classes through this config, so replacing `ProductProduct` here propagates to any relation that targets it.

### 3. Test the connection

```bash
php artisan test:odoo
```

This runs a smoke test that fetches the first `MrpProduction` and dumps its `mrp_immediate_production_lines` relation. It's a quick way to verify auth + connectivity.

## Usage

### Basic Eloquent

The shipped models under `Sefirosweb\LaravelOdooConnector\Http\Models\*` cover the most common Odoo models (product, mrp, sale, purchase, stock, mail, etc.). Use them like any Eloquent model:

```php
use Sefirosweb\LaravelOdooConnector\Http\Models\ProductProduct;

$products = ProductProduct::where('name', 'like', '%widget%')
    ->with('mrp_bom')
    ->get();

$product = ProductProduct::find(1);
$product->name = 'New name';
$product->save();

$created = ProductProduct::create([
    'name'        => 'Product X',
    'description' => 'Flagship SKU',
    'list_price'  => 100,
]);
```

Supported Eloquent methods include `find`, `where`, `whereHas`, `with`, `create`, `update`, `delete`, `get`, `first`, etc.

### Customising a model

Extend the shipped model and register your override through the config:

```php
namespace App\Odoo;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sefirosweb\LaravelOdooConnector\Http\Models\ProductProduct as BaseProductProduct;

class CustomProductProduct extends BaseProductProduct
{
    protected $table = 'product.product';

    public function our_custom_belongs(): BelongsTo
    {
        return $this->belongsTo(OurCustomModel::class, 'our_field_id');
    }
}
```

Then point `config/laravel-odoo-connector.php:ProductProduct` at `App\Odoo\CustomProductProduct::class`.

### Soft-deletes (the Odoo `active` flag)

Odoo's equivalent of a soft-delete is the `active` boolean column. Mirror it with the shipped trait:

```php
use Sefirosweb\LaravelOdooConnector\Http\Models\OdooModel;
use Sefirosweb\LaravelOdooConnector\Http\Traits\SoftDeleteOdoo;

class MyModel extends OdooModel
{
    use SoftDeleteOdoo;
    // ...
}
```

### Fetching large collections

Odoo JSON-RPC times out on unbounded queries. Use `get_all` to chunk automatically (500 records per batch by default):

```php
$products = ProductProduct::get_all('id', 'name', 100);
// Behaves like ::all() but paginates under the hood.
```

### Model actions (Odoo server-side buttons)

Trigger the equivalent of a UI button:

```php
$saleOrder = SaleOrder::find(1);
$saleOrder->action('action_confirm');
```

Pass extra arguments when the action needs them:

```php
$args = [[['id' => 1]]];
SaleOrder::model_action('action_custom', $args);
```

### Multiple Odoo connections

Define multiple `odoo` connections in `config/database.php` and target one from a model:

```php
use Sefirosweb\LaravelOdooConnector\Http\Models\OdooModel;

class SecondaryOdooModel extends OdooModel
{
    protected $connection = 'other_odoo';

    public function getConnection()
    {
        return app('db')->connection('other_odoo');
    }
}
```

## Testing

```bash
composer install
./vendor/bin/phpunit
```

The Orchestra Testbench suite currently covers the service provider boot and the `DB::extend('odoo', …)` driver registration.

> ⚠️ Full integration testing against a real Odoo instance is not shipped here because it requires network access to an Odoo server. Expand the suite with your own tests pointed at a staging Odoo when you need coverage of specific models.

When working from the [laravel-test](https://github.com/sefirosweb/laravel-test) harness with Sail:

```bash
docker exec -w /var/www/html/packages/laravel-odoo-connector laravel-test-laravel.test-1 ./vendor/bin/phpunit
```

## Roadmap

- Add the remaining Odoo models (POS, payroll, etc.).
- Cover more methods with tests once a public-facing Odoo test instance is available.

## Versioning

Major versions are aligned with Laravel majors (`12.x`, `11.x`, `9.x` …). See the root [CLAUDE.md](https://github.com/sefirosweb/laravel-test/blob/12.0/CLAUDE.md) of the test harness for the full policy.

## License

MIT.
