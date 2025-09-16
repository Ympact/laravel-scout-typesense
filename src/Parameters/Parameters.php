<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Database\Eloquent\Model;

class Parameters
{
    /**
     * Create a new schema.
     *
     * @param  Model  $model  optionally pass the name or model to create a schema for
     * @param  callable  $callback  the schema blueprint
     */
    public static function create(Model $model, ?callable $callback = null): Blueprint
    {
        $blueprint = new Blueprint($model);

        $callback($blueprint);

        return $blueprint;
    }
}
