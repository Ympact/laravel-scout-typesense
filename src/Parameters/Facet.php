<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

/**
 * Facet parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#faceting-parameters
 */
class Facet
{
    /**
     * Model
     */
    protected Model $model;

    /**
     * Parameters
     */
    protected Parameters $parameters;

    /**
     * Schema
     */
    protected Schema $schema;

    /**
     * facet_by: A list of fields that will be used for faceting your results on. Separate multiple fields with a comma.
     */
    protected ?Fields $facetBy = null;

    /**
     * facet_strategy: Typesense supports two strategies for efficient faceting, and has some built-in heuristics to pick the right strategy for you. The valid values for this parameter are exhaustive, top_values and automatic (default).
     *
     * @default automatic
     */
    protected ?string $facetStrategy = null; // exhaustive, top_values, automatic

    /**
     * max_facet_values: Maximum number of facet values to be returned.
     *
     * @default 10
     */
    protected ?int $maxFacetValues = null;

    /**
     * facet_query: Facet values that are returned can now be filtered via this parameter. The matching facet text is also highlighted. For example, when faceting by category, you can set facet_query=category:shoe to return only facet values that contain the prefix "shoe".
     *
     * For facet queries, if a per_page parameter is not specified, it will default to 0, thereby returning only facets and not hits. If you want hits as well, be sure to set per_page to a non-zero value.
     *
     * Use the facet_query_num_typos parameter to control the fuzziness of this facet value filter.
     */
    protected ?string $facetQuery = null;

    /**
     * facet_query_num_typos: Controls the fuzziness of the facet query filter.
     *
     * @default 2
     */
    protected ?int $facetQueryNumTypos = null;

    /**
     * facet_return_parent: Pass a comma separated string of nested facet fields whose parent object should be returned in facet response. For e.g. when you set this to "color.name", this will return the parent color object as parent property in the facet response.
     *
     * @note: this does not work yet when using reference fields.
     */
    protected ?Fields $facetReturnParent = null;

    /**
     * facet_sample_percent: Percentage of hits that will be used to estimate facet counts.
     *
     * Facet sampling is helpful to improve facet computation speed for large datasets, where the exact count is not required in the UI.
     *
     * @default 100 (sampling is disabled by default)
     */
    protected ?int $facetSamplePercent = null;

    /**
     * facet_sample_threshold: Minimum number of hits above which the facet counts are sampled.
     *
     * Facet sampling is helpful to improve facet computation speed for large datasets, where the exact count is not required in the UI.
     *
     * @default 0
     */
    protected ?int $facetSampleThreshold = null;

    /**
     * constructor
     */
    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        Fields|array|string|null $facetBy = null,
        ?string $facetStrategy = null,
        ?int $maxFacetValues = null,
        ?string $facetQuery = null,
        ?int $facetQueryNumTypos = null,
        Fields|array|string|null $facetReturnParent = null,
        ?int $facetSamplePercent = null,
        ?int $facetSampleThreshold = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->facetBy = Fields::from($model, $facetBy);

        $this->facetStrategy = $facetStrategy ?? $this->facetStrategy;
        $this->maxFacetValues = $maxFacetValues ?? $this->maxFacetValues;
        $this->facetQuery = $facetQuery ?? $this->facetQuery;
        $this->facetQueryNumTypos = $facetQueryNumTypos ?? $this->facetQueryNumTypos;
        $this->facetReturnParent = Fields::from($model, $facetReturnParent) ?? $this->determineParents();
        $this->facetSamplePercent = $facetSamplePercent ?? $this->facetSamplePercent;
        $this->facetSampleThreshold = $facetSampleThreshold ?? $this->facetSampleThreshold;
    }

    /**
     * Get the array representation of the object
     */
    public function toArray(): array
    {
        return array_filter([
            'facet_by' => $this->facetBy->get(),
            'facet_strategy' => $this->facetStrategy,
            'max_facet_values' => $this->maxFacetValues,
            'facet_query' => $this->facetQuery,
            'facet_query_num_typos' => $this->facetQueryNumTypos,
            'facet_return_parent' => $this->facetReturnParent->get(),
            'facet_sample_percent' => $this->facetSamplePercent,
            'facet_sample_threshold' => $this->facetSampleThreshold,
        ], fn ($value) => ! is_null($value));
    }

    /**
     * facet by
     */
    public function facetBy(string|array $fields): static
    {
        $fields = Fields::from($this->model, $fields);

        // check if the fields are present in the schema
        if (! $this->schema->hasFields($fields)) {
            throw new \InvalidArgumentException('Field(s) not found in schema for '.$this->model::class);
        }
        $this->facetBy = $fields;

        return $this;
    }

    /**
     * set the strategy
     *
     * @param  string  $strategy  exhaustive, top_values, automatic
     */
    public function strategy(string $strategy): static
    {
        // must be of value exhaustive, top_values, automatic
        if (! in_array($strategy, ['exhaustive', 'top_values', 'automatic'])) {
            throw new \InvalidArgumentException('Strategy must be one of exhaustive, top_values, automatic');
        }
        $this->facetStrategy = $strategy;

        return $this;
    }

    /**
     * get facet parent for nested/referenced fields
     * use the schema to determine the parent fields
     */
    private function determineParents(): Fields
    {
        // get the names of the facetBy fields that either are of type object or have a reference
        return Fields::from($this->model, $this->schema->getNestedFields());
    }

    /**
     * disable sampling
     */
    public function sampling(bool|int $value): static
    {
        if ($value === false) {
            $this->facetSamplePercent = 100;

            return $this;
        }

        // percent must be between 0 and 100
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Percent must be between 0 and 100');
        }
        $this->facetSamplePercent = $value;

        return $this;
    }

    /**
     * enable sampling
     */
    public function samplingThreshold(int $hits): static
    {
        $this->facetSampleThreshold = $hits;

        return $this;
    }
}
