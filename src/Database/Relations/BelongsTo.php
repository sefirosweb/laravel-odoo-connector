<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Database\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;

class BelongsTo extends BaseBelongsTo
{
    public function addConstraints()
    {
        if (static::$constraints) {
            // Odoo have the first key "0" the ID of the record

            $this->query->where($this->ownerKey, '=', $this->child->{$this->foreignKey}[0]);
        }
    }

    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // Odoo have the first key "0" the ID of the record
        foreach ($models as $model) {
            if (! is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        sort($keys);

        return array_values(array_unique(array_column($keys, 0)));
    }

    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $attribute = $this->getDictionaryKey($result->getAttribute($owner));

            $dictionary[$attribute] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            if (!$model->{$foreign}) {
                continue;
            }
            $attribute = $this->getDictionaryKey($model->{$foreign}[0] ?? false);

            if (isset($dictionary[$attribute])) {
                $model->setRelation($relation, $dictionary[$attribute]);
            }
        }

        return $models;
    }
}
