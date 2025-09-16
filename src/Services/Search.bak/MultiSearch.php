<?php

namespace App\Services\TypeSense;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;

/**
 * Class MultiSearch
 */
class MultiSearch
{
    // use the laravel Conditional trait to conditionally append search parameters
    use Conditionable;

    protected \Laravel\Scout\Engines\TypesenseEngine $client;

    // make a chainable multisearch

    /**
     * The built search querie
     *
     * @var array
     */
    protected $searches = [];

    /**
     * @todo implement storing each collection and their search params in the class
     */
    protected Collection $collections;

    protected $errors = [];

    /**
     * combine search results
     */
    protected $combine = true;

    protected $sort_by = 'text_match'; // text_match_info.best_field_score

    protected int $page = 1;

    protected $defaultSearchParams = [
        'enable_highlight_v1' => false,
    ];

    protected $commonSearchParams;

    /**
     * MultiSearch constructor.
     *
     * @param  \Laravel\Scout\Engines\TypesenseEngine  $client  The typesense client
     * @param  string|null  $query  The common search query
     * @param  array|null  $defaultSearchParams  The common search parameters
     */
    public function __construct(
        \Laravel\Scout\Engines\TypesenseEngine $client,
        ?string $query = null,
        ?array $defaultSearchParams = null
    ) {
        $this->client = $client;

        $this->commonSearchParams = new SearchParams;
        if ($defaultSearchParams) {
            $this->defaultSearchParams = $defaultSearchParams;
        }
        $this->commonSearchParams->raw($this->defaultSearchParams);

        if ($query) {
            $this->commonSearchParams->query($query);
        }
    }

    public function page(int $int = 1): static
    {
        $this->page = $int;

        $this->commonSearchParams->page($this->page);

        return $this;
    }

    /**
     * Allow for search params on the specific collection
     *
     * @todo implement weight and mapping
     *
     * @param  Model|string  $model  the model or collection name
     * @param  callable  $params  specific search params for this collection
     * @param  float|null  $weight  adding a weight to the results of this collection
     * @param  array|null  $map  mapping the results of this collection
     */
    public function collection(
        Model|string $model,
        ?callable $params,
        bool $defaults = true,
        callable|bool $condition = true,
        // ?float $weight,
        // ?array $map
    ): static {

        // condition can either be a function that evaluates to a boolean or a boolean
        if (is_callable($condition)) {
            $condition = call_user_func($condition, $model);
        }
        if (! $condition) {
            return $this;
        }

        $searchable = $this->isSearchableModel($model);

        if ($searchable === null) {
            return throw new \Exception('Invalid model: '.$model);
        }
        $collectionName = $this->getCollectionName($searchable);

        if ($collectionName == null) {
            // there is no collection present in TypeSense, hence we can't add it to the search query.
            return $this;
        }

        $defaultParams = [];
        if ($defaults) {
            if (method_exists($searchable, 'typesenseSearchParameters')) {
                $defaultParams = $searchable->typesenseSearchParameters();
            }
            // otherwise get those from scout config
            else {
                $defaultParams = config('scout.typesense.model-settings.'.get_class($searchable).'.search-parameters') ?? [];
            }
        }

        $paramColllection = new SearchParams($defaultParams);
        if ($params) {
            call_user_func($params, $paramColllection);
        }

        $this->searches[] = array_merge(
            ['collection' => $collectionName],
            $paramColllection->getParams()
        );

        return $this;
    }

    /**
     * Sets the collection(s) to be searched.
     *
     * @param  iterable|Model  $models  A single Model instance or an array of Model instances.
     * @param  bool  $defaults  Whether to use the default search parameters from the model.
     * @return $this
     */
    public function collections(iterable|Model $models, bool $defaults = true): static
    {
        collect($models)->each(function ($model) use ($defaults) {
            $this->collection($model, null, $defaults, true);
        });

        return $this;
    }

    /**
     * Get the collection
     */
    private function getCollectionName(Model|string $model): ?string
    {

        if ($model = $this->isSearchableModel($model)) {
            $name = $model->searchableAs();
            $collection = $this->client->getCollections()[$name];
        } elseif (is_string($model)) {
            $name = $model;
            $collection = $this->client->getCollections()[$model];
        }

        if ($collection->exists() === true) {
            return $name;
        } else {
            // throw new \Exception('Invalid collection');
            return null;
        }
    }

    /**
     * Get the collection
     */
    private function getCollection(Model|string $model)
    {
        // check if $collection is a class
        $name = $this->getCollectionName($model);
        $collection = $this->client->getCollections()[$name];

        if ($collection->exists() === true) {
            return $collection;
        } else {
            throw new \Exception('Invalid collection');
        }
    }

    /**
     * Checks if the given variable is an Eloquent model or a class name that extends Eloquent model.
     *
     * @param  Model|string  $model  The variable to check.
     * @return Model|null True if it's an Eloquent model or extends Eloquent model, false otherwise.
     */
    private function isSearchableModel(Model|string $model): ?Model
    {
        // Check if it's an object instance of Model
        if (is_object($model) && $model instanceof Model) {
            return $model;
        }

        // Check if it's a string and a valid class name that extends Model
        if (is_string($model) && class_exists($model)) {
            // $reflection = new ReflectionClass($model);
            if (in_array(\Laravel\Scout\Searchable::class, class_uses_recursive($model))) {
                return resolve($model); // Using Laravel's service container to resolve and return the model instance
            }
            /*if ($reflection->isSubclassOf(Model::class) || $model === Model::class) {
                return resolve($model); // Using Laravel's service container to resolve and return the model instance
            }*/
        }

        return null;
    }

    /**
     * Get the raw search query
     */
    public function rawQuery(): array
    {

        return [
            'searchRequests' => ['searches' => $this->searches],
            'commonSearchParams' => $this->commonSearchParams->getParams(),
        ];
    }

    /**
     * Perform the search
     *
     * @return Collection search results [hits, found, out_of, search_time_ms, search_time_ms]
     */
    public function search(): ?Collection
    {

        // make sure we have at least one collection to search
        if (count($this->searches) == 0) {
            return null;
        }

        $results = $this->client->getMultiSearch()->perform(
            $this->rawQuery()['searchRequests'],
            $this->rawQuery()['commonSearchParams']
        );

        return $this->parseResults($results);
    }

    /**
     * Combine search results
     *
     * @param  string  $sort_by
     * @return $this
     */
    public function combine($sort_by = 'text_match_info.score'): static
    {
        $this->sort_by = $sort_by;
        $this->combine = true;

        return $this;
    }

    /**
     * Do not combine the multisearch into a single result
     *
     * @return MultiSearch
     */
    public function seperate(): static
    {
        $this->combine = false;

        return $this;
    }

    /**
     * Parse the search results
     */
    private function parseResults(array $results): Collection
    {
        $results = collect($results['results']);

        // check if there is an error code 404 in each of the results
        // TODO implement error handling
        $errors = $results->filter(function ($item, $key) {
            if (array_key_exists('error', $item)) {
                return $item;
            }
        });

        if (count($errors)) {
            dump($errors);
            throw new \Exception('Error in search results');
        } else {
            if ($this->combine) {
                // add collection name to each hit
                $results = $results->map(function ($colResults, $key) {
                    // if there are no hits within the collection, continue
                    if (! isset($colResults['hits'])) {
                        return;
                    }

                    // keep individual stats
                    $colResults['collection_stats'][$colResults['request_params']['collection_name']] = $colResults['found'];

                    $colResults['hits'] = Arr::map($colResults['hits'], function ($hit, $key) use ($colResults) {
                        $hit['collection'] = $colResults['request_params']['collection_name'];

                        return $hit;
                    });

                    return $colResults;
                });

                $combined = collect();

                $stats = collect([
                    'results' => $results->pluck('found')->sum(),
                    'out_if' => $results->pluck('out_of')->sum(),
                    'search_time_ms' => $results->pluck('search_time_ms')->sum(),
                ]);

                $pageInfo = collect([
                    'per_page_max' => $results->pluck('request_params.per_page')->sum(),
                    'pages' => $results->map(function ($collection) {
                        if ($collection['request_params']['per_page'] == 0) {
                            return 0;
                        }

                        return $collection['found'] / $collection['request_params']['per_page'];
                    })->max(),
                    'page' => $this->page,
                ]);

                $combined
                    ->put('hits', $results->pluck('hits')->flatten(1)->filter()->collect()->sortByDesc($this->sort_by))
                    // ->put('found', $results->pluck('found')->sum())
                    ->put('stats', $stats)
                    ->put('collection_stats', $results->pluck('collection_stats'))
                    ->put('facets', $this->collectFacets($results->pluck('facet_counts')))
                    ->put('page_info', $pageInfo);
                // ->put('facet_counts', $results->pluck('facet_counts'))
                /*
                ->put('out_of', $results->pluck('out_of')->sum())
                ->put('search_time_ms', $results->pluck('search_time_ms')->sum())
                ->put('per_page_max', $results->pluck('request_params.per_page')->sum())
                ->put('pages', $results->map(function ($collection) {
                    if($collection['request_params']['per_page'] == 0){
                        return 0;
                    }
                    return $collection['found'] / $collection['request_params']['per_page'];
                })->max())
                ->put('page', $this->page);
                */

                // dump($combined);
                // text_match
                // sort the hits by
                // $combined['hits'] = collect($combined['hits'])->sortBy($this->sort_by);

                return $combined;
            }
        }

        return $results;
    }

    public function collectFacets($facets)
    {
        // dd($facets);
        $groupedByFieldNameAndValue = $facets
            ->flatten(1)
            ->filter(function ($item) {
                return isset($item['field_name']);
            })
            ->groupBy('field_name')
            ->map(function ($itemsByFieldName) {
                // For each field_name group, further group by value and prepare the structure
                return $itemsByFieldName->flatMap(function ($subItem) {
                    return collect($subItem['counts']);
                })
                    ->groupBy('value')
                    ->map(function ($items, $value) {
                        // Summing the 'count' values for each value group
                        $totalCount = $items->sum('count');

                        // Preparing the final array structure for each value
                        return [
                            'count' => $totalCount,
                            'highlighted' => $items->pluck('highlighted')->first(),
                            'value' => $items->pluck('value')->first(),
                        ];
                    });
            });

        return $groupedByFieldNameAndValue;
    }

    /**
     * Dynamically proxy search params onto the common search params
     *
     * @return $this
     */
    public function __call($method, $parameters): static
    {
        if (is_array($parameters) && array_key_exists(0, $parameters) && $parameters[0] !== null) {
            $this->commonSearchParams->$method($parameters[0]);
        }

        return $this;
    }
}
