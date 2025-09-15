<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Http\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockQuant extends OdooModel
{
    protected $table = 'stock.quant';

    public function product_product(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class, 'product_id');
    }
}
