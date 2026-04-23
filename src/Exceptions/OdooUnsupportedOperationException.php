<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Exceptions;

use RuntimeException;

/**
 * Thrown when the caller invokes an Eloquent / Query Builder operation that
 * cannot be compiled to Odoo's JSON-RPC `domain` filters.
 *
 * The most common trigger is `whereHas()` / `has()` / `whereDoesntHave()`,
 * which compile to SQL `EXISTS` — a correlated subquery Odoo's RPC layer
 * does not support. Users need to resolve parent IDs first and then filter
 * the outer query with `whereIn`.
 */
class OdooUnsupportedOperationException extends RuntimeException
{
}
