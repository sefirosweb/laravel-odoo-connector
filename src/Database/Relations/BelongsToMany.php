<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Database\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;

class BelongsToMany extends BaseBelongsToMany
{
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate our pivot
        // models with the result of those columns as a separate model relation.
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        $models = $builder->getModels();

        $this->hydratePivotRelation($models);

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    public function qualifyPivotColumn($column)
    {
        return $column;
    }

    protected function addWhereConstraints()
    {
        $this->query->whereIn(
            $this->getQualifiedForeignPivotKeyName(),
            [$this->parent->{$this->parentKey}]
        );

        return $this;
    }

    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // parent models. Then we should return these hydrated models back out.
        foreach ($models as $model) {
            $keys = $this->getDictionaryKey($model->{$this->relatedPivotKey});
            if (!$keys) {
                continue;
            }

            $itemsFound = [];
            foreach ($keys as $key) {
                if (isset($dictionary[$key])) {
                    $itemsFound[] = $dictionary[$key];
                }
            }

            $model->setRelation(
                $relation,
                $this->related->newCollection($itemsFound)
            );
        }

        return $models;
    }

    protected function buildDictionary(Collection $results)
    {
        // First we'll build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to the
        // parents without having a possibly slow inner loop for every model.
        $dictionary = [];

        foreach ($results as $result) {
            $value = $this->getDictionaryKey($result->{$this->relatedKey});
            $dictionary[$value] = $result;
        }

        return $dictionary;
    }
}
