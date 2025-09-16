<?php

namespace Ympact\Typesense\Results;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Ympact\Typesense\Parameters\Parameters;

class Results
{
    /**
     * "facet_counts": [],
  "found": 1,
  "out_of": 1,
  "page": 1,
  "request_params": {
    "collection_name": "companies",
    "per_page": 10,
    "q": ""
    }
    "hits": [
          "highlights": [
        {
          "field": "company_name",
          "snippet": "<mark>Stark</mark> Industries",
          "matched_tokens": ["Stark"]
        }
      ],
      "document": {
        "id": "124",
        "company_name": "Stark Industries",
        "num_employees": 5215,
        "country": "USA"
      },
      "text_match": 130916
    ]
      "search_time_ms": 1,
  "grouped_hits": [
    "found": 3,
      "group_key": ["USA"],
      "hits": [
        {
          "highlights": [
            {
              "field": "company_name",
              "matched_tokens": ["Stark"],
              "snippet": "<mark>Stark</mark> Industries"
            }
          ],
          "document": {
            "id": "124",
            "company_name": "Stark Industries",
            "num_employees": 5215,
            "country": "USA"
          },
          "text_match": 130916
        }
      ]
    ]
     */

    /**
     * Summary of rawResults
     */
    protected array $rawResults;

    /**
     * The search results (hits)
     * collection of Hit
     *
     * @var EloquentCollection<Model>
     */
    public EloquentCollection $hits;

    /**
     * whether the results are grouped
     */
    public bool $isGrouped = false;

    /**
     * whether the results are from a federated/mutli search
     */
    public bool $isFederated = false;

    /**
     * The total number of hits
     */
    public int $found = 0;

    /**
     * The total number of hits
     */
    public int $outOf = 0;

    /**
     * The current page number
     */
    public int $page = 1;

    /**
     * The model on which the search was performed
     *
     * @var array<string,Model>
     */
    public array $models = [];

    /**
     * The search request parameters
     *
     * @var array<string,Parameters>
     */
    public array $parameters = [];

    /**
     * The facet fields and their values
     */
    public Facets $facets;

    /**
     * query callback: optional query callback
     *
     * @var callable
     */
    public $queryCallback = null;

    /**
     * Facet count
     */
    public int $facetCount;

    /**
     * constructor
     */
    public function __construct(
        array $results,
        Model|array|string $models,
        Parameters|array $parameters,
        ?bool $multisearch = false
    ) {
        $this->rawResults = $results;
        $this->hits = $this->map($results);
        $this->isGrouped = isset($results['grouped_hits']) && ! empty($results['grouped_hits']);
        $this->isFederated = $multisearch;
        $this->found = $results['found'];
        $this->outOf = $results['out_of'];
        $this->page = $results['page'];
        $this->parameters = Parameters::from($parameters);
        $this->facets = Facets::from($results);
        $this->facetCount = $this->facets->count();
    }

    /**
     * we append dynamic attributes to the Models in the collection
     * $model->append('search')
     */

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits'])
            ->pluck('document.id')
            ->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map($results, $model)
    {
        if ($this->getTotalCount($results) === 0) {
            return $model->newCollection();
        }

        $hits = isset($results['grouped_hits']) && ! empty($results['grouped_hits'])
            ? $results['grouped_hits']
            : $results['hits'];

        $pluck = isset($results['grouped_hits']) && ! empty($results['grouped_hits'])
            ? 'hits.0.document.id'
            : 'document.id';

        $objectIds = collect($hits)
            ->pluck($pluck)
            ->values()
            ->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds($builder, $objectIds)
            ->filter(static function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds, false);
            })
            ->sortBy(static function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })
            ->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap($results, $model)
    {
        if ((int) ($results['found'] ?? 0) === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = collect($results['hits'])
            ->pluck('document.id')
            ->values()
            ->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds($builder, $objectIds)
            ->cursor()
            ->filter(static function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds, false);
            })
            ->sortBy(static function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })
            ->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return (int) ($results['found'] ?? 0);
    }
}
