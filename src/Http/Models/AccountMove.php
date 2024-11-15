<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Http\Models;

use Sefirosweb\LaravelOdooConnector\Http\Traits\SoftDeleteOdoo;

class AccountMove extends OdooModel
{
    use SoftDeleteOdoo;

    protected $table = 'account.move';
}