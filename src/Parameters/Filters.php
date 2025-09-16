<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Parameters\Filters\Filter;
use Ympact\Typesense\Parameters\Filters\Group;
use Ympact\Typesense\Schema\Blueprint as Schema;

/**
 * Filter parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#filter-parameters
 */
class Filters implements Arrayable
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
     * Parameters
     */
    protected Parameters $parameters;

    /**
     * Filter conditions for refining your search results.
     *
     * @var Collection<string,Group|Filter>
     */
    protected Collection $filters;

    /**
     * Applies the filtering operation incrementally / lazily. Set this to true when you are potentially filtering on large values but the tokens in the query are expected to match very few documents. Default: false.
     */
    protected ?bool $enableLazyFilter = false;

    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->filters = collect();
    }

    /**
     * enable lazy filter
     */
    public function lazy(bool $enableLazyFilter = true): static
    {
        $this->enableLazyFilter = $enableLazyFilter;

        return $this;
    }

    /**
     * add a named filter
     * to allow for updating or removing specific filters
     */
    public function add(Group|callable $filter, $name = null): static
    {
        if ($filter instanceof Group) {
            $this->upsert($name, $filter);
        } else {
            $group = new Group($this->model, $this->schema);
            $filter($group);
            $this->upsert($name, $group);
        }

        return $this;
    }

    /**
     * Continue building from previous Filters instance
     *
     * @param  Filter  $filter
     */
    public static function from(Filters $filters, ?callable $callback = null): Filters
    {
        $group = new Group($filters->model, $filters->schema);
        $callback($group);

        $filters->upsert(group: $group);

        return $filters;
    }

    /**
     * Create a new filter instance.
     *
     * @param  Model  $model  optionally pass the name or model to create a schema for
     * @param  callable  $callback  the schema blueprint
     * @return Blueprint
     */
    public static function make(Model $model, Schema $schema, Parameters $parameters, ?callable $callback = null): static
    {
        $group = new Group($model, $schema);
        $callback($group);

        $filter = new static($model, $schema, $parameters);
        $filter->upsert(group: $group);

        return $filter;
    }

    /**
     * update or insert a filter
     */
    public function upsert(?string $name, Group $group): static
    {
        $name = $name ?? $this->filters->count();

        // todo, in case of update (name exists), replace the filter
        $this->filters->put($name, $group);

        return $this;
    }

    /**
     * Summary of toArray
     *
     * @return array{enable_lazy_filter: bool|null, filter_by: array}
     */
    public function toArray(): array
    {
        return array_filter([
            'filter_by' => $this->filters->toArray(),
            'enable_lazy_filter' => $this->enableLazyFilter,
        ], fn ($value) => ! is_null($value));
    }
}
