<?php

namespace Ympact\Typesense\Schema;

use Illuminate\Database\Eloquent\Model;

class Schema
{
    /**
     * Create a new schema.
     *
     * @param  mixed  $mixed  optionally pass the name or model to create a schema for
     * @param  callable  $callback  the schema blueprint
     */
    public static function create(string|Model|callable $mixed, ?callable $callback = null): Blueprint
    {
        $blueprint = new Blueprint;

        if (is_callable($mixed)) {
            $callback = $mixed;
        }

        if ($mixed instanceof Model || is_string($mixed)) {
            $blueprint->name($mixed);
            $model = $mixed instanceof Model ? $mixed : null;
        }

        $callback($blueprint, $model ?? null);

        return $blueprint;

    }
}
