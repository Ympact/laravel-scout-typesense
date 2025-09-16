<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

class Blueprint implements Arrayable
{
    use Conditionable, Macroable, Tappable;

    /**
     * Model
     */
    protected Model $model;

    /**
     * Schema
     */
    protected Schema $schema;

    /**
     * Caching parameters
     */
    protected Caching $caching;

    /**
     * Facet parameters
     */
    protected Facet $facet;

    /**
     * Filter parameters
     */
    protected Filters $filters;

    /**
     * Grouping parameters
     */
    protected Grouping $grouping;

    /**
     * Pagination parameters
     */
    protected Pagination $pagination;

    /**
     * Query parameters
     */
    protected Query $query;

    /**
     * Result parameters
     */
    protected Result $result;

    /**
     * Snippet parameters
     */
    protected Snippet $snippet;

    /**
     * Sorting parameters
     */
    protected Sorting $sorting;

    /**
     * Typo parameters
     */
    protected Typo $typo;

    /**
     * Constructor
     */
    public function __construct(
        Model $model
    ) {
        $this->model = $model;
        $this->schema = $model->searchService->schema();

        $this->caching = new Caching($model, $this->schema, $this);
        $this->facet = new Facet($model, $this->schema, $this);
        $this->filters = new Filters($model, $this->schema, $this);
        $this->grouping = new Grouping($model, $this->schema, $this);
        $this->pagination = new Pagination($model, $this->schema, $this);
        $this->query = new Query($model, $this->schema, $this);
        $this->result = new Result($model, $this->schema, $this);
        $this->snippet = new Snippet($model, $this->schema, $this);
        $this->sorting = new Sorting($model, $this->schema, $this);
        $this->typo = new Typo($model, $this->schema, $this);
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return array_merge(
            $this->caching->toArray(),
            $this->facet->toArray(),
            $this->filters->toArray(),
            $this->grouping->toArray(),
            $this->pagination->toArray(),
            $this->query->toArray(),
            $this->result->toArray(),
            $this->snippet->toArray(),
            $this->sorting->toArray(),
            $this->typo->toArray()
        );
    }

    /**
     * Caching parameters
     */
    public function caching(): Caching
    {
        return $this->caching;
    }

    /**
     * Facet parameters
     */
    public function facet(): Facet
    {
        return $this->facet;
    }

    public function facetBy(string|array $fields)
    {
        return $this->facet->facetBy($fields);
    }

    /**
     * Filter parameters
     */
    public function filter(): Filters
    {
        return $this->filters;
    }

    /**
     * Grouping parameters
     */
    public function grouping(): Grouping
    {
        return $this->grouping;
    }

    /**
     * Pagination parameters
     */
    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Query parameters
     */
    public function query(): Query
    {
        return $this->query;
    }

    public function queryBy(array|Fields|string $fields)
    {
        $this->query->queryBy($fields);
    }

    /**
     * Result parameters
     */
    public function result(): Result
    {
        return $this->result;
    }

    /**
     * Snippet parameters
     */
    public function snippet(): Snippet
    {
        return $this->snippet;
    }

    /**
     * Sorting parameters
     */
    public function sorting(): Sorting
    {
        return $this->sorting;
    }

    /**
     * Typo parameters
     */
    public function typo(): Typo
    {
        return $this->typo;
    }
}
