<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelOdooConnector\Http\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Sefirosweb\LaravelOdooConnector\Database\OdooEloquentBuilder;
use Sefirosweb\LaravelOdooConnector\Database\Relelations\BelongsTo;
use Sefirosweb\LaravelOdooConnector\Database\Relelations\BelongsToMany;
use Sefirosweb\LaravelOdooConnector\Database\Relelations\HasMany;

use Sefirosweb\LaravelOdooConnector\Rpc\OdooJsonRpc;

class OdooModel extends Model
{
    protected $connection = 'odoo';
    public $timestamps = false;

    public function getConnection()
    {
        return app('db')->connection($this->getConnectionName());
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array|string  $columns
     * @param  int  $chunks
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function get_all($columns = ['*'], $chunks = 500)
    {
        $response = new Collection();
        $offset = 0;
        while (true) {
            $data = static::query()
                ->offset($offset)
                ->limit($chunks)
                ->get(
                    is_array($columns) ? $columns : func_get_args()
                );

            if ($data->isEmpty()) {
                break;
            }

            $offset += $chunks;
            $response = $response->merge($data);
        }

        return $response;
    }

    public function action(string $action, $kwargs = null)
    {
        if (!$this->id) {
            throw new \Exception('The model must have an id to perform this action');
        }

        $dataToSend =  [
            [$this->id],
        ];

        if ($kwargs !== null) {
            $dataToSend = array_merge($dataToSend, $kwargs);
        }

        return OdooJsonRpc::execute_kw(
            $this->getTable(),
            $action,
            $dataToSend
        );
    }

    public static function model_action(string $action, array $args = [], array $kwargs = [])
    {
        $instance = new static();
        return OdooJsonRpc::execute_kw(
            $instance->getTable(),
            $action,
            $args,
            $kwargs
        );
    }

    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        return new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        return new MorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName, $inverse);
    }

    public function newEloquentBuilder($query)
    {
        return new OdooEloquentBuilder($query);
    }
}
