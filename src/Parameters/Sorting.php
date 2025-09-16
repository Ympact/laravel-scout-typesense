<?php

namespace Ympact\Typesense\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Parameters\Blueprint as Parameters;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Services\Fields;

/**
 * Sorting parameters
 *
 * @see https://typesense.org/docs/27.1/api/search.html#ranking-and-sorting-parameters
 */
class Sorting implements Arrayable
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
     * query_by_weights: The relative weight to give each query_by field when ranking results. Values can be between 0 and 127. This can be used to boost fields in priority, when looking for matches.
     * Default: If no explicit weights are provided, fields earlier in the query_by list will be considered to have greater weight.
     */
    protected ?Fields $queryByWeights = null;

    /**
     * text_match_type: In a multi-field matching context, this parameter determines how the representative text match score of a record is calculated.
     * Default: max_score
     */
    protected ?string $textMatchType = null;

    /**
     * sort_by: A list of fields and their corresponding sort orders that will be used for ordering your results. Separate multiple fields with a comma.
     * Default sorting: _text_match:desc,default_sorting_field:desc
     */
    protected ?Fields $sortBy = null;

    /**
     * prioritize_exact_match: By default, Typesense prioritizes documents whose field value matches exactly with the query. Set this parameter to false to disable this behavior.
     * Default: true
     */
    protected ?bool $prioritizeExactMatch = null;

    /**
     * prioritize_token_position: Make Typesense prioritize documents where the query words appear earlier in the text.
     * Default: false
     */
    protected ?bool $prioritizeTokenPosition = null;

    /**
     * prioritize_num_matching_fields: Make Typesense prioritize documents where the query words appear in more number of fields.
     * Default: true
     */
    protected ?bool $prioritizeNumMatchingFields = null;

    /**
     * pinned_hits: A list of records to unconditionally include in the search results at specific positions.
     * Default: none
     */
    protected ?string $pinnedHits = null;

    /**
     * hidden_hits: A list of records to unconditionally hide from search results.
     * Default: none
     */
    protected ?string $hiddenHits = null;

    /**
     * filter_curated_hits: Whether the filter_by condition of the search query should be applicable to curated results (override definitions, pinned hits, hidden hits, etc.).
     * Default: false
     */
    protected ?bool $filterCuratedHits = null;

    /**
     * enable_overrides: If you have some overrides defined but want to disable all of them for a particular search query, set enable_overrides to false.
     * Default: true
     */
    protected ?bool $enableOverrides = null;

    /**
     * override_tags: You can trigger particular override rules that you've tagged using their tag name(s) in this search parameter.
     * Default: none
     */
    protected ?string $overrideTags = null;

    /**
     * enable_synonyms: If you have some synonyms defined but want to disable all of them for a particular search query, set enable_synonyms to false.
     * Default: true
     */
    protected ?bool $enableSynonyms = null;

    /**
     * synonym_prefix: Allow synonym resolution on word prefixes in the query.
     * Default: false
     */
    protected ?bool $synonymPrefix = null;

    /**
     * max_candidates: Control the number of similar words that Typesense considers for prefix and typo searching.
     *
     * @default: 4 (or 10000 if exhaustive_search is enabled).
     */
    protected ?int $maxCandidates = null;

    public function __construct(
        Model $model,
        Schema $schema,
        Parameters $parameters,

        Fields|array|string|null $queryByWeights = null,
        ?string $textMatchType = null,
        Fields|array|string|null $sortBy = null,
        ?bool $prioritizeExactMatch = null,
        ?bool $prioritizeTokenPosition = null,
        ?bool $prioritizeNumMatchingFields = null,
        ?string $pinnedHits = null,
        ?string $hiddenHits = null,
        ?bool $filterCuratedHits = null,
        ?bool $enableOverrides = null,
        ?string $overrideTags = null,
        ?bool $enableSynonyms = null,
        ?bool $synonymPrefix = null,
        ?int $maxCandidates = null
    ) {
        $this->model = $model;
        $this->schema = $schema;
        $this->parameters = $parameters;

        $this->queryByWeights = Fields::from($model, $queryByWeights);
        $this->textMatchType = $textMatchType ?? $this->textMatchType;
        $this->sortBy = Fields::from($model, $sortBy);
        $this->prioritizeExactMatch = $prioritizeExactMatch ?? $this->prioritizeExactMatch;
        $this->prioritizeTokenPosition = $prioritizeTokenPosition ?? $this->prioritizeTokenPosition;
        $this->prioritizeNumMatchingFields = $prioritizeNumMatchingFields ?? $this->prioritizeNumMatchingFields;
        $this->pinnedHits = $pinnedHits ?? $this->pinnedHits;
        $this->hiddenHits = $hiddenHits ?? $this->hiddenHits;
        $this->filterCuratedHits = $filterCuratedHits ?? $this->filterCuratedHits;
        $this->enableOverrides = $enableOverrides ?? $this->enableOverrides;
        $this->overrideTags = $overrideTags ?? $this->overrideTags;
        $this->enableSynonyms = $enableSynonyms ?? $this->enableSynonyms;
        $this->synonymPrefix = $synonymPrefix ?? $this->synonymPrefix;
        $this->maxCandidates = $maxCandidates ?? $this->maxCandidates;
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return array_filter([
            'query_by_weights' => $this->queryByWeights->get('weight'),
            'text_match_type' => $this->textMatchType,
            'sort_by' => $this->sortBy->get('name'),
            'prioritize_exact_match' => $this->prioritizeExactMatch,
            'prioritize_token_position' => $this->prioritizeTokenPosition,
            'prioritize_num_matching_fields' => $this->prioritizeNumMatchingFields,
            'pinned_hits' => $this->pinnedHits,
            'hidden_hits' => $this->hiddenHits,
            'filter_curated_hits' => $this->filterCuratedHits,
            'enable_overrides' => $this->enableOverrides,
            'override_tags' => $this->overrideTags,
            'enable_synonyms' => $this->enableSynonyms,
            'synonym_prefix' => $this->synonymPrefix,
            'max_candidates' => $this->maxCandidates,
        ], fn ($value) => ! is_null($value));
    }

    /**
     * Parameter	Required	Description
query_by_weights	no	The relative weight to give each query_by field when ranking results. Values can be between 0 and 127. This can be used to boost fields in priority, when looking for matches.

Separate each weight with a comma, in the same order as the query_by fields. For eg: query_by_weights: 1,1,2 with query_by: field_a,field_b,field_c will give equal weightage to field_a and field_b, and will give twice the weightage to field_c comparatively.

Default: If no explicit weights are provided, fields earlier in the query_by list will be considered to have greater weight.
text_match_type	no	In a multi-field matching context, this parameter determines how the representative text match score of a record is calculated.

Possible values: max_score (default), max_weight or sum_score.

In the default max_score mode, the best text match score across all fields are used as the representative score of this record. Field weights are used as tie breakers when 2 records share the same text match score.

In the max_weight mode, the text match score of the highest weighted field is used as the representative text relevancy score of the record.

The sum_score mode sums the field-level text match scores to arrive at a holistic document-level score.

Read more on text match scoring.
sort_by	no	A list of fields and their corresponding sort orders that will be used for ordering your results. Separate multiple fields with a comma.

Up to 3 sort fields can be specified in a single search query, and they'll be used as a tie-breaker - if the first value in the first sort_by field ties for a set of documents, the value in the second sort_by field is used to break the tie, and if that also ties, the value in the 3rd field is used to break the tie between documents. If all 3 fields tie, the document insertion order is used to break the final tie.

E.g. num_employees:desc,year_started:asc

This results in documents being sorted by num_employees in descending order, and if two records have the same num_employees, the year_started field is used to break the tie.

The text similarity score is exposed as a special _text_match field that you can use in the list of sorting fields.

If one or two sorting fields are specified, _text_match is used for tie breaking, as the last sorting field.

Default:

If no sort_by parameter is specified, results are sorted by: _text_match:desc,default_sorting_field:desc.

Sorting on String Values: Read more here.

Sorting on Missing Values: Read more here.

Sorting Based on Conditions (aka Optional Filtering): Read more here.

GeoSort: When using GeoSearch, documents can be sorted around a given lat/long using location_field_name(48.853, 2.344):asc. You can also sort by additional fields within a radius. Read more here.
prioritize_exact_match	no	By default, Typesense prioritizes documents whose field value matches exactly with the query. Set this parameter to false to disable this behavior.

Default: true
prioritize_token_position	no	Make Typesense prioritize documents where the query words appear earlier in the text.

Default: false
prioritize_num_matching_fields	no	Make Typesense prioritize documents where the query words appear in more number of fields.

Default: true
pinned_hits	no	A list of records to unconditionally include in the search results at specific positions.

An example use case would be to feature or promote certain items on the top of search results.

A comma separated list of record_id:hit_position. Eg: to include a record with ID 123 at Position 1 and another record with ID 456 at Position 5, you'd specify 123:1,456:5.

You could also use the Overrides feature to override search results based on rules. Overrides are applied first, followed by pinned_hits and finally hidden_hits.
hidden_hits	no	A list of records to unconditionally hide from search results.

A comma separated list of record_ids to hide. Eg: to hide records with IDs 123 and 456, you'd specify 123,456.

You could also use the Overrides feature to override search results based on rules. Overrides are applied first, followed by pinned_hits and finally hidden_hits.
filter_curated_hits	no	Whether the filter_by condition of the search query should be applicable to curated results (override definitions, pinned hits, hidden hits, etc.).

Default: false
enable_overrides	no	If you have some overrides defined but want to disable all of them for a particular search query, set enable_overrides to false.

Default: true
override_tags	no	You can trigger particular override rules that you've tagged using their tag name(s) in this search parameter. Read more here.
enable_synonyms	no	If you have some synonyms defined but want to disable all of them for a particular search query, set enable_synonyms to false.

Default: true
synonym_prefix	no	Allow synonym resolution on word prefixes in the query.

Default: false
max_candidates	no	Control the number of similar words that Typesense considers for prefix and typo searching .

Default: 4 (or 10000 if exhaustive_search is enabled).

For e.g. Searching for "ap", will match records with "apple", "apply", "apart", "apron", or any of hundreds of similar words that start with "ap" in your dataset. Also, searching for "jofn", will match records with "john", "joan" and all similar variations that are 1-typo away in your dataset.

But for performance reasons, Typesense will only consider the top 4 prefixes and typo variations by default. The 4 is what is configurable using the max_candidates search parameter.

In short, if you search for a short term like say "a", and not all the records you expect are returned, you want to increase max_candidates to a higher value and/or change the default_sorting_field in the collection schema to define "top" using some popularity score in your records.
     */
}
