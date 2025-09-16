<?php

namespace Ympact\Typesense\Parameters\Filters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Stringable;
use Ympact\Typesense\Schema\Field as SchemaField;

/**
 * examples
Text
[TextA, TextB]
<40
[10..100]
[<10, >100]
[10..100, 140] (Filter docs where value is between 10 to 100 or exactly 140).
[10, 100, 140] (Filter docs where value is NOT 10, 100 or 140).
[`Running Shoes, Men`, `Sneaker (Men)`, Boots] // escape special characters with backticks
 */
class Value implements Stringable
{
    /**
     * Model
     */
    protected Model $model;

    /**
     * Field on which the filter is applied
     */
    public SchemaField $field;

    /**
     * values
     */
    public Collection $values;

    public function __construct(Model $model, SchemaField $field, mixed $value = null)
    {
        $this->model = $model;
        $this->field = $field;
        $this->values = $value ? $this->addValue($value) : null;
    }

    /**
     * Add value to values
     */
    protected function addValue($value)
    {
        if (is_array($value)) {
            // $this->type = 'array';
            return $this->values->each(function ($v) {
                // check for types

                $this->addValue(['value' => $v]);
            });
        }

        return $this->values->push([$value]);
    }

    /**
     * minmax
     * [10..100]
     *
     * @param  int  $min
     * @param  int  $max
     */
    public function minmax($min, $max): static
    {
        $this->addValue([$min, $max]);

        return $this;
    }

    // exact match

    // larger than
    // >40

    // less than
    // <40

    /**
     * Get the type of value
     */
    protected function getType($value): string
    {
        // string fields should always be treated as strings
        if ($this->field->isString()) {
            return 'string';
        }

        if (is_string($value)) {
            // if it contains [0-9]..[0-9] it is a minmax
            if (preg_match('/\d+\.\.\d+/', $value)) {
                return 'minmax';
            }
            // if it is an operator with a number it is a threshold
            if (preg_match('/[><]=?\d+/', $value)) {
                return 'threshold';
            }

            return 'string';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_array($value)) {
            return 'array';
        }

        // treat as string by default
        return 'string';
    }

    public function get(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        // in case of a single value, just return it
        if ($this->values->count() === 1) {
            if ($this->field->isString()) {
                return $this->escapeString($this->values->first()['value']);
            }

        }

        // we have an array of values to return
        // in case of numeric field

        // in case of string field
        // escape special characters with backticks

        return '';
    }

    public function escapeString($value): string
    {
        // in case the field is a string, surround the value with backticks
        if ($this->field->isString()) {
            return "`$value`";
        }

        return $value;
    }
}
