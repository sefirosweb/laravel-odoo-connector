<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Database\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany as BaseHasMany;

class HasMany extends BaseHasMany
{

    protected function buildDictionary(Collection $results)
    {
        // Odoo have the first key "0" the ID of the record
        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(function ($result) use ($foreign) {
            return [$this->getDictionaryKey($result->{$foreign}[0]) => $result];
        })->all();
    }
}
