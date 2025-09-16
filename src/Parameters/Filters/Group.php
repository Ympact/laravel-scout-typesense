<?php

namespace Ympact\Typesense\Parameters\Filters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Stringable;
use Ympact\Typesense\Schema\Blueprint as Schema;

class Group implements Stringable
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
     * Summary of fields
     *
     * @var Collection<Filter|Group>
     */
    public Collection $fields;

    /**
     * AND OR operators
     */
    public string $operator;

    /**
     * Summary of __construct
     *
     * @param  string  $operator
     */
    public function __construct(Model $model, Schema $schema)
    {
        $this->model = $model;
        $this->schema = $schema;
        $this->fields = collect();
    }

    /**
     * __toString
     */
    public function __toString(): string
    {
        return $this->fields->map(function ($field) {
            return (string) $field;
        })->implode(' ');
    }

    /**
     * and operator
     */
    public function and(): static
    {
        $this->operator = 'AND';

        return $this;
    }

    /**
     * or operator
     */
    public function or(): static
    {
        $this->operator = 'OR';

        return $this;
    }

    /**
     * add a nested group
     * return the nested group
     */
    public function group(?string $operator, callable $callback): Group
    {
        $group = new Group($this->model, $this->schema);
        $callback($group);

        $this->fields->push($group);

        return $group;
    }

    /**
     * add a filter to the group
     *
     * @todo use a callback to create filters
     */
    public function field(string $name, mixed $value = null, bool $exactMatch = false, bool $negate = false): Filter
    {
        $field = new Filter($this->model, $this->schema);
        $this->fields->push($field);

        return $field;
    }

    /**
     * filter and value functions
     */

    /**
     * current field being added to the group
     */
    private function current(): Filter|Group
    {
        return $this->fields->last();
    }

    /**
     * magic forward field and value functions on the current filter that was added to the group
     */
    public function __call(string $name, array $arguments): static
    {
        $field = $this->current()->$name(...$arguments);

        return $field;
    }
}
