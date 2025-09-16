<?php

namespace Ympact\Typesense\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Ympact\Typesense\Builders\TypesenseBuilder;
use Ympact\Typesense\Exceptions\SearchServiceNotFoundException;
use Ympact\Typesense\Schema\Blueprint as SchemaBlueprint;
use Ympact\Typesense\Services\CollectionService;

trait HasSearch
{
    // override the search function to use the custom builder
    use Searchable {
        search as scoutSearch;
        searchableAs as scoutSearchableAs;
        getScoutModelsByIds as scoutGetModelsByIds;
        queryScoutModelsByIds as scoutQueryModelsByIds;
    }

    /**
     * @var \Ympact\Typesense\Services\ModelSearch
     */
    public $searchService;

    public $searchCollection;

    // initialize the search service
    protected function initializeHasSearch()
    {
        $searchService = $this->getModelSearchService();

        // set the searchService property
        // if searchService is null throw an exception
        if (! $searchService || ! class_exists($searchService)) {
            throw new SearchServiceNotFoundException('Search service not found for '.get_class($this));
        }
        $this->searchService = app()->make($searchService, ['model' => $this]);

        $this->searchCollection = app()->make(CollectionService::class, ['model' => $this]);
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @param  string  $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder<static>
     */
    public static function search($query = '', $callback = null)
    {
        return app(static::$scoutBuilder ?? TypesenseBuilder::class, [
            'model' => new static,
            'query' => $query,
            'callback' => $callback,
            'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }

    /**
     * Get the requested models from an array of object IDs.
     *
     * @return mixed
     */
    public function getScoutModelsByIds(TypesenseBuilder $builder, array $ids)
    {
        return $this->queryScoutModelsByIds($builder, $ids)->get();
    }

    /**
     * Get a query builder for retrieving the requested models from an array of object IDs.
     *
     * @return mixed
     */
    public function queryScoutModelsByIds(TypesenseBuilder $builder, array $ids)
    {
        $query = static::usesSoftDelete()
            ? $this->withTrashed() : $this->newQuery();

        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }

        $whereIn = in_array($this->getScoutKeyType(), ['int', 'integer']) ?
            'whereIntegerInRaw' :
            'whereIn';

        return $query->{$whereIn}(
            $this->qualifyColumn($this->getScoutKeyName()), $ids
        );
    }

    /**
     * The method for typesense to convert the model data to an array that can be indexed correctly.
     * i.e. set the id as string, load relation data, etc
     *
     * @return mixed
     */
    public function toSearchableArray(): array
    {
        return $this->searchService->toSearchableArray();
    }

    /**
     * The method to get the name of the collection
     **/
    public function searchableAs()
    {
        // if the getSearchService() has a searchableAs method, use it
        if (method_exists($this->searchService, 'searchableAs')) {
            return $this->searchService->searchableAs();
        }

        // otherwise, use the searchableAs from the Scout Searchable trait.
        return $this->scoutSearchableAs();
    }

    /**
     * Summary of typesenseCollectionSchema
     * Scout engine calls this method on the model, should not be renamed!
     */
    public function typesenseCollectionSchema(): ?SchemaBlueprint
    {
        if (method_exists($this->searchService, 'schema')) {
            return $this->searchService->schema();
        }

        // fallback for deprecated method
        return null; // $this->searchService->typesenseCollectionSchema();
    }

    /**
     * Determine whether the model should be searchable
     * Scout engine calls this method on the model, should not be renamed!
     *
     * @return mixed
     */
    public function shouldBeSearchable(): bool
    {
        return $this->searchService->shouldBeSearchable();
    }

    /**
     * Summary of typesenseSearchParameters
     *
     * @todo return Parameters object
     *
     * @return mixed
     */
    public function typesenseSearchParameters()
    {
        if (method_exists($this->searchService, 'parameters')) {
            return $this->searchService->parameters()->toArray();
        }

        // fallback for deprecated method
        if (method_exists($this->searchService, 'typesenseSearchParameters')) {
            return $this->searchService->typesenseSearchParameters();
        }

        return [];
    }

    /**
     * Summary of typesenseSynonyms
     *
     * @return mixed
     */
    public function typesenseSynonyms()
    {
        if (method_exists($this->searchService, 'typesenseSynonyms')) {
            return $this->searchService->typesenseSynonyms();
        }

        return [];
    }

    /**
     * Resolve the search service class for the model using the trait.
     *
     * @return mixed
     */
    protected function getModelSearchService()
    {
        // Get the full namespace of the model
        $fullClassPath = get_class($this);

        // Extract the base namespace (assuming your models are stored in a 'Models' directory within each module)
        $baseNamespace = Str::of($fullClassPath)->before('\Models');

        // Replace 'Models' with 'Services' and append 'SearchService' to the class name
        $searchClass = $baseNamespace.'\Search\\'.class_basename($this).'Search';

        return class_exists($searchClass) ? $searchClass : null;
    }

    /**
     * get documents in the serach index that are not linked to a model anymore.
     */
    public function getOrphanedDocuments(): ?Collection
    {
        return typesense()->getOrphanedDocuments(new static);
    }
}
