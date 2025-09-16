<?php

namespace Ympact\Typesense\Parameters\Filters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stringable;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Schema\Field;

class Filter implements Stringable
{
    /**
     * Model
     */
    protected Model $model;

    /**
     * Schema
     */
    protected Schema $schema;

    /**
     * Summary of field
     */
    public Field $field;

    /**
     * Summary of value
     */
    public Value $value;

    /**
     * exact match: only for string types
     * use := or : operator
     */
    public bool $exactMatch = false;

    /**
     * Summary of negate
     * use ! operator
     */
    public bool $negate = false;

    /**
     * startsWith: only for string types
     */
    public bool $startsWith = false;

    public function __construct(
        Model $model,
        Schema $schema,
        Field|string|null $field = null,
        Value|string|array|null $value = null,
        ?bool $exactMatch = false,
        ?bool $negate = false
    ) {
        $this->model = $model;
        $this->schema = $schema;

        $this->field = is_string($field) ? $this->getField($field) : $field;
        $this->value = new Value($this->model, $this->field, $value);

        $this->exactMatch = $exactMatch;
        $this->negate = $negate;
    }

    /**
     * Summary of __toString
     */
    public function __toString(): string
    {
        $field = $this->field->getName();
        $value = $this->value->get();
        $exactMatch = $this->exactMatch ? ':=' : ':';
        $negate = $this->negate ? '!' : '';

        if ($this->startsWith) {
            $value = Str::finish($value, '*');
        }

        return "{$negate}{$field}{$exactMatch}{$value}";
    }

    /** basic operators */

    /**
     * Summary of not
     *
     * @return Filter
     */
    public function not(): static
    {
        $this->negate = true;

        return $this;
    }

    /**
     * the filter must be an exact match
     *
     * @return Filter
     *
     * @throws \Exception
     */
    public function exact(bool $value = true): static
    {
        if (! $this->field->isString()) {
            throw new \Exception('Filter must be a string type to use exact');
        }
        $this->exactMatch = $value;

        return $this;
    }

    /**
     * Summary of startsWith
     *
     * @return Filter
     *
     * @throws \Exception
     */
    public function startsWith(bool $value = true): static
    {
        if (! $this->field->isString()) {
            throw new \Exception('Filter must be a string type to use startsWith');
        }

        $this->startsWith = $value;

        if ($value) {
            $this->exact(true);
        }

        return $this;
    }

    /**
     * contains: only for array fields
     * containsAny
     *
     * @todo
     */
    public function contains(): static
    {
        if (! $this->field->isArray()) {
            throw new \Exception('Filter must be an array type to use contains');
        }
        $this->exactMatch = true;

        // $this->value->value = $this->value->get();
        return $this;
    }

    /** Composite operators */

    /** Geo operators */

    /**
     * get the field from the schema
     */
    private function getField(string $name): Field
    {
        return $this->schema->getField($name);
    }
}
