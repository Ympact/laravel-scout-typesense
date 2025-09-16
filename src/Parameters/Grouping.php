<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

/**
 * Grouping parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#grouping-parameters
 */
class Grouping implements Arrayable
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
     * You can aggregate search results into groups or buckets by specify one or more group_by fields. Separate multiple fields with a comma.
     */
    protected ?Fields $groupBy = null;

    /**
     * Maximum number of hits to be returned for every group. If the group_limit is set as K then only the top K hits in each group are returned in the response.
     *
     * @default 3
     */
    protected ?int $groupLimit = null;

    /**
     * Setting this parameter to true will place all documents that have a null value in the group_by field, into a single group. Setting this parameter to false, will cause each document with a null value in the group_by field to not be grouped with other documents.
     *
     * @default true
     */
    protected ?bool $groupMissingValues = null;

    /**
     * Summary of __construct
     *
     * @param  mixed  $groupLimit
     * @param  mixed  $groupMissingValues
     */
    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        Fields|array|string|null $groupBy = null,
        ?int $groupLimit = null,
        ?bool $groupMissingValues = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->groupBy = Fields::from($model, $groupBy);
        $this->groupLimit = $groupLimit ?? $this->groupLimit;
        $this->groupMissingValues = $groupMissingValues ?? $this->groupMissingValues;
    }

    /**
     * set order of groups
     */
    public function sort(Fields|array|string $fields): static
    {
        $this->groupBy->sort($fields);

        return $this;
    }

    /**
     * Summary of toArray
     *
     * @return array{group_by: string, group_limit: int, group_missing_values: bool}
     */
    public function toArray(): array
    {
        return array_filter([
            'group_by' => $this->groupBy->get(),
            'group_limit' => $this->groupLimit,
            'group_missing_values' => $this->groupMissingValues,
        ], fn ($value) => ! is_null($value));
    }
}
