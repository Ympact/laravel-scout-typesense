<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

/**
 * Result parameters
 */
class Result implements Arrayable
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
     * Comma-separated list of fields from the document to include in the search result.
     */
    protected ?Fields $includeFields = null;

    /**
     * Comma-separated list of fields from the document to exclude in the search result.
     */
    protected ?Fields $excludeFields = null;

    /**
     * Maximum number of hits that can be fetched from the collection. Eg: 200
     */
    protected ?int $limitHits = null;

    /**
     * Typesense will attempt to return results early if the cutoff time has elapsed. This is not a strict guarantee and facet computation is not bound by this parameter.
     *
     * @default no search cutoff happens.
     */
    protected ?int $searchCutoffMs = null;

    /**
     * Setting this to true will make Typesense consider all variations of prefixes and typo corrections of the words in the query exhaustively, without stopping early when enough results are found (drop_tokens_threshold and typo_tokens_threshold configurations are ignored).
     *
     * @default false
     */
    protected ?bool $exhaustiveSearch = null;

    /**
     * Constructor
     */
    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        Fields|array|string|null $includeFields = null,
        Fields|array|string|null $excludeFields = null,
        ?int $limitHits = null,
        ?int $searchCutoffMs = null,
        ?bool $exhaustiveSearch = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->includeFields = Fields::from($model, $includeFields);
        $this->excludeFields = Fields::from($model, $excludeFields);
        $this->limitHits = $limitHits ?? $this->limitHits;
        $this->searchCutoffMs = $searchCutoffMs ?? $this->searchCutoffMs;
        $this->exhaustiveSearch = $exhaustiveSearch ?? $this->exhaustiveSearch;
    }

    /**
     * set include fields
     */
    public function include(Fields|array|string $fields): static
    {
        $this->includeFields = Fields::from($this->model, $fields);

        return $this;
    }

    /**
     * set exclude fields
     */
    public function exclude(Fields|array|string $fields): static
    {
        $this->excludeFields = Fields::from($this->model, $fields);

        return $this;
    }

    /**
     * set limit hits
     */
    public function limit(int $limitHits): static
    {
        $this->limitHits = $limitHits;

        return $this;
    }

    /**
     * set search cutoff ms
     */
    public function searchCutoff(int $searchCutoffMs): static
    {
        $this->searchCutoffMs = $searchCutoffMs;

        return $this;
    }

    /**
     * set exhaustive search
     */
    public function exhaustive(bool $exhaustiveSearch = true): static
    {
        $this->exhaustiveSearch = $exhaustiveSearch;

        return $this;
    }

    /**
     * toArray
     */
    public function toArray(): array
    {
        return array_filter([
            'include_fields' => $this->includeFields->get(),
            'exclude_fields' => $this->excludeFields->get(),
            'limit_hits' => $this->limitHits,
            'search_cutoff' => $this->searchCutoffMs,
            'exhaustive' => $this->exhaustiveSearch,
        ], fn ($value) => ! is_null($value));
    }
}
