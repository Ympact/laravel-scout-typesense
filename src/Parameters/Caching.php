<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;

/**
 * Caching parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#caching-parameters
 */
class Caching implements Arrayable
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
     * use_cache: Enable server side caching of search query results. By default, caching is disabled.
     *
     * @default false
     */
    protected ?bool $useCache = null;

    /**
     * cache_ttl: The duration (in seconds) that determines how long the search query is cached. This value can only be set as part of a scoped API key.
     *
     * @default 60
     */
    protected ?int $cacheTtl = null;

    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        ?bool $useCache = null,
        ?int $cacheTtl = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->useCache = $useCache ?? $this->useCache;
        $this->cacheTtl = $cacheTtl ?? $this->cacheTtl;
    }

    /**
     * set cache
     */
    public function cache(bool $useCache = true): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * setCacheTtl
     */
    public function cacheTtl(int $cacheTtl = 60): self
    {
        $this->cacheTtl = $cacheTtl;

        return $this;
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return array_filter([
            'use_cache' => $this->useCache,
            'cache_ttl' => $this->cacheTtl,
        ], fn ($value) => ! is_null($value));
    }
}
