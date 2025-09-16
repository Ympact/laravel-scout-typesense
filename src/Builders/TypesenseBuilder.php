<?php

namespace Ympact\Typesense\Builders;

use Illuminate\Support\Arr;
use Laravel\Scout\Builder;
// use Laravel\Scout\Engines\TypesenseEngine as BaseTypesenseEngine;
use Ympact\ValueObjects\Types\Location\Point;

class TypesenseBuilder extends Builder
{
    /**
     * Store facet fields the user wants to query.
     * e.g. [ 'themes', 'type' ]
     */
    protected array $facetFields = [];

    /**
     * How to sort facet values: 'count' or 'alphabetical'.
     */
    protected ?string $sortFacetValuesBy = null;

    /**
     * For numeric fields, define custom ranges, e.g.:
     * [
     *   'score' => [
     *     "label_1" => ['from' => 0, 'to' => 50],
     *     "label_2" => ['from' => 50, 'to' => 100]
     *   ]
     * ]
     */
    protected array $facetRanges = [];

    /**
     * Add (or override) facets.
     */
    public function facetBy(array $fields): static
    {
        $this->facetFields = $fields;

        return $this;
    }

    /**
     * Sort facet values by 'count' or 'alphabetical'.
     */
    public function sortFacetValuesBy(string $type): static
    {
        $this->sortFacetValuesBy = $type;

        return $this;
    }

    /**
     * Define facet ranges for numeric fields.
     */
    public function facetRanges(array $ranges): static
    {
        $this->facetRanges = $ranges;

        return $this;
    }

    /**
     * Get the facets from the last search results in a simple array format.
     */
    public function getFacets(): array
    {
        // Perform the actual search (or re-run if needed).
        $results = $this->engine()->search($this);

        // The 'facet_counts' field from Typesense response:
        // [
        //   [
        //     'field_name' => 'themes',
        //     'counts'     => [
        //       ['count' => 2, 'value' => 'climate'],
        //       ...
        //     ]
        //   ],
        //   ...
        // ]
        $facetCounts = Arr::get($results, 'facet_counts', []);

        $parsedFacets = [];
        foreach ($facetCounts as $facet) {
            $field = $facet['field_name'];
            // Typically 'counts' => [ [ 'count' => int, 'value' => 'string' ], ... ]
            $parsedFacets[$field] = $facet['counts'];
        }

        return $parsedFacets;
    }

    /**
     * Get the results of the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function get()
    {
        return $this->engine()->get($this);
    }

    /*
    public function get(){
        parent::get();
        //return $this->engine()->search($this);
    }
        */

    /**
     * Override build query parameters to incorporate custom facets.
     */
    public function buildCustomFacetParameters(array $params): array
    {
        // Merge with or override 'facet_by' from the model’s typesenseSearchParameters()
        if (! empty($this->facetFields)) {
            $params['facet_by'] = implode(',', $this->facetFields);
        }

        // Add optional sorting
        if ($this->sortFacetValuesBy) {
            $params['sort_facet_values_by'] = $this->sortFacetValuesBy;
        }

        // Define numeric ranges for fields (each range turns into a facet_query: e.g. rating: [0..3])
        // In Typesense, you can pass multiple facet queries with comma separation
        // or an array for each field.
        // e.g. facet_query=price:=[0..50],price:=[50..100]
        $facetQueries = [];
        foreach ($this->facetRanges as $field => $ranges) {
            foreach ($ranges as $range) {
                $from = $range['from'];
                $to = $range['to'];
                // For an open-ended range (e.g. [50..]), you can omit the second boundary
                $facetQueries[] = sprintf('%s:=[%d..%d]', $field, $from, $to);
            }
        }

        if (! empty($facetQueries)) {
            $params['facet_query'] = implode(',', $facetQueries);
        }

        return $params;
    }

    public function labeledRangeFacet(array $ranges)
    {
        // $ranges might look like:
        // [
        //    'rating' => [
        //        ['label' => 'Average', 'from' => 0, 'to' => 3],
        //        ['label' => 'Good', 'from' => 3, 'to' => 4],
        //        ['label' => 'Great', 'from' => 4, 'to' => 5],
        //    ]
        // ]

        // We need to build something like rating(Average:[0..3], Good:[3..4], Great:[4..5])
        $facetParts = [];
        foreach ($ranges as $field => $labels) {
            $labelRanges = [];
            foreach ($labels as $entry) {
                $label = $entry['label'];
                $from = $entry['from'];
                $to = $entry['to'];
                $labelRanges[] = sprintf('%s:[%d..%d]', $label, $from, $to);
            }
            // rating(Average:[0..3], Good:[3..4], Great:[4..5])
            $facetParts[] = sprintf('%s(%s)', $field, implode(', ', $labelRanges));
        }

        // If we have multiple fields, they'd be comma separated in the final facet_by
        // rating(Average:[0..3]...),price(Cheap:[..100],Medium:[101..500],Expensive:[501..])
        $this->options['facet_by'] = implode(',', $facetParts);

        return $this;
    }

    /**
     * Summary of withoutSnippets
     *
     * @return TypesenseBuilder
     */
    public function withoutSnippets(): static
    {
        // $parameters = new SnippetParameters;
        // $parameters->disableSnippetting();

        $this->options = array_merge($this->options); // , $parameters->toArray());

        return $this;
    }

    /**
     * Summary of withSnippets
     *
     * @return TypesenseBuilder
    public function withSnippets(SnippetParameters $parameters): static
    {
        $this->options = array_merge($this->options, $parameters->toArray());

        return $this;
    }
     */

    /**
     * Enable exhaustive search
     */
    public function exhaustive(): static
    {
        $this->options['exhaustive_search'] = true;

        return $this;
    }

    /**
     * geo filter: withinRadius
     * TODO add dtos for geopoints
     */
    public function withinRadius($field, Point $point, float $radius): static
    {
        // format: location:(48.90615915923891, 2.3435897727061175, 5.1 km)
        // use [Latitude, Longitude]
        $this->where($field, "{$point->lat},{$point->lng},{$radius}");

        return $this;
    }

    /**
     * geo filter within polygon
     * TODO add dtos for geopoints
     */
    public function withinArea($field, array $points): static
    {
        // format: 'filter_by' : 'field:(48.8662, 2.3255, 48.8581, 2.3209, 48.8561, 2.3448, 48.8641, 2.3469)'
        // use [Latitude, Longitude]
        $this->where($field, implode(',', $points));

        return $this;
    }

    /**
     * geo sort near
     * exclude_radius
     * Sometimes, it's useful to sort nearby places within a radius based on another attribute like popularity, and then sort by distance outside this radius. You can use the exclude_radius option for that.
     * format:  'sort_by' : 'location(48.853, 2.344, exclude_radius: 2mi):asc'
     * TODO add dtos for geopoints
     */
    public function near($field, float $lat, float $lng, ?string $excludeRadius = null): static
    {
        $sort = "{$field}({$lat},{$lng}";
        if ($excludeRadius) {
            $sort .= ",exclude_radius:{$excludeRadius}";
        }
        $sort .= ')';
        $this->orderBy($sort, 'asc');

        return $this;
    }
}
