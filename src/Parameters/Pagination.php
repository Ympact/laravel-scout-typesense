<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;

/**
 * Pagination
 */
class Pagination implements Arrayable
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
     * Page number
     *
     * @default 1
     */
    public ?int $page = null;

    /**
     * Number of hits to fetch
     *
     * @default 10
     */
    public ?int $perPage = null;

    /**
     * Identifies the starting point to return hits from a result set
     *
     * @default 0
     */
    public ?int $offset = null;

    /**
     * Number of hits to fetch
     *
     * @default 10
     */
    public ?int $limit = null;

    /**
     * Summary of __construct
     *
     * @param  mixed  $page
     * @param  mixed  $perPage
     */
    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        ?int $page = null,
        ?int $perPage = null,
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->page = $page ? $this->page($page) : $this->page;
        $this->perPage = $perPage ? $this->perPage($perPage) : $this->perPage;
    }

    /**
     * Summary of toArray
     *
     * @return array{offset: int, limit: int}|array{page: int, per_page: null}
     */
    public function toArray(): array
    {
        // either use offset or page
        if ($this->offset) {
            $result = [
                'offset' => $this->offset,
                'limit' => $this->limit,
            ];
        } else {
            $result = [
                'page' => $this->page,
                'per_page' => $this->perPage,
            ];
        }

        return array_filter($result, fn ($value) => ! is_null($value));
    }

    /**
     * Summary of page
     *
     * @param  mixed  $page
     */
    public function page($page): static
    {
        // min 1
        if ($page < 1) {
            throw new \Exception('Page must be greater than 0');
        }
        $this->page = $page;

        return $this;
    }

    /**
     * Summary of perPage
     *
     * @param  mixed  $perPage
     */
    public function perPage($perPage): static
    {
        // min 1
        if ($perPage < 0 || $perPage > 250) {
            throw new \Exception('Per page must be between 0 and 250');
        }
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Summary of offset
     *
     * @param  mixed  $offset
     */
    public function offset($offset): static
    {
        // min 0
        if ($offset < 0) {
            throw new \Exception('Offset must be greater than or equal to 0');
        }
        $this->offset = $offset;

        return $this;
    }

    /**
     * Summary of limit
     *
     * @param  mixed  $limit
     */
    public function limit($limit): static
    {
        // min 0 max 250
        if ($limit < 0 || $limit > 250) {
            throw new \Exception('Limit must be between 0 and 250');
        }
        $this->limit = $limit;

        return $this;
    }
}
