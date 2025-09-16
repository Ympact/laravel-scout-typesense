<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Schema\Field;
use Ympact\Typesense\Services\Fields;
use Ympact\ValueObjects\Types\Strings\CSV;

/**
 * Query parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#query-parameters
 */
class Query implements Arrayable
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
     * q: The search query
     * can be null on multisearch
     *
     * @default ''
     */
    protected ?string $q = null;

    /**
     * query_by: One or more field names that should be queried against
     */
    protected Fields $queryBy;

    /**
     * prefix: Indicates that the last word in the query should be treated as a prefix, and not as a whole word
     *
     * @default true
     */
    protected ?bool $prefix = null;

    /**
     * infix: Infix search can be used to find documents that contains a piece of text that appears in the middle of a word
     *
     * @default null
     */
    protected ?string $infix = null;

    /**
     * pre_segmented_query: Set this parameter to true if you wish to split the search query into space separated words yourself
     */
    protected ?bool $preSegmentedQuery = null;

    /**
     * preset: The name of the Preset to use for this search
     */
    protected ?string $preset = null;

    /**
     * vector_query: Perform a nearest-neighbor vector query
     */
    protected ?string $vectorQuery = null;

    /**
     * voice_query: Transcribe the base64-encoded speech recording, and do a search with the transcribed query
     */
    protected ?string $voiceQuery = null;

    /**
     * stopwords: A comma separated list of words to be dropped from the search query while searching
     */
    protected ?CSV $stopwords = null;

    /**
     * Summary of __construct
     *
     * @param  mixed  $prefix
     * @param  mixed  $infix
     * @param  mixed  $preSegmentedQuery
     * @param  mixed  $preset
     * @param  mixed  $vectorQuery
     * @param  mixed  $voiceQuery
     * @param  mixed  $stopwords
     */
    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        ?string $q = null,
        Fields|array|string|null $queryBy = null,
        ?bool $prefix = null,
        ?string $infix = null,
        ?bool $preSegmentedQuery = null,
        ?string $preset = null,
        ?string $vectorQuery = null,
        ?string $voiceQuery = null,
        ?CSV $stopwords = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->q = $q;
        $this->queryBy = Fields::from($model, $queryBy);
        $this->prefix = $prefix ?? $this->prefix;
        $this->infix = $infix ?? $this->infix;
        $this->preSegmentedQuery = $preSegmentedQuery ?? $this->preSegmentedQuery;
        $this->preset = $preset ?? $this->preset;
        $this->vectorQuery = $vectorQuery ?? $this->vectorQuery;
        $this->voiceQuery = $voiceQuery ?? $this->voiceQuery;
        $this->stopwords = $stopwords ?? $this->stopwords;
    }

    /**
     * query
     */
    public function query(string $q): static
    {
        $this->q = $q;

        return $this;
    }

    /**
     * queryBy
     */
    public function queryBy(Fields|array|string $fields): static
    {
        $this->queryBy = Fields::from($this->model, $fields);

        return $this;
    }

    /**
     * get
     */
    public function get(): Fields
    {
        return $this->queryBy;
    }

    /**
     * getQueryBy
     */
    public function getQueryBy(): array
    {
        return $this->queryBy->toArray();
    }

    /**
     * set order of queryBy fields
     */
    public function sort(Fields|array|string $fields): static
    {
        $this->queryBy->sort($fields);

        return $this;
    }

    /**
     * set a param on a queryBy field
     *
     * @param  string  $param
     * @param  mixed  $value
     * @param  mixed  $default
     */
    public function fieldParam(Field|string $field, $param, $value, $default = null): static
    {
        $this->queryBy->param($field, $param, $value, $default);

        return $this;
    }

    /**
     * set a param on all queryBy fields
     */

    /**
     * toArray
     */
    public function toArray(): array
    {
        return array_filter([
            'q' => $this->q,
            'query_by' => $this->queryBy->get(),
            'prefix' => $this->prefix,
            'infix' => $this->infix,
            'pre_segmented_query' => $this->preSegmentedQuery,
            'preset' => $this->preset,
            'vector_query' => $this->vectorQuery,
            'voice_query' => $this->voiceQuery,
            'stopwords' => $this->stopwords ? $this->stopwords->get() : null,
        ], fn ($value) => ! is_null($value));
    }
}
