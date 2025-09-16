<?php

namespace Ympact\Typesense\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\TypesenseEngine as BaseTypesenseEngine;
use Typesense\Client as Typesense;
use Typesense\Collection as TypesenseCollection;
use Typesense\Exceptions\TypesenseClientError;
use Ympact\Typesense\Builders\TypesenseBuilder;
use Ympact\Typesense\Traits\TypesenseClientTrait;
use Ympact\ValueObjects\Types\Strings\CSV;

class TypesenseEngine extends BaseTypesenseEngine
{
    use TypesenseClientTrait;

    protected ?EloquentCollection $hits = null;

    /**
     * Create new Typesense engine instance.
     */
    public function __construct(Typesense $typesense, int $maxTotalResults = 1000)
    {
        parent::__construct($typesense, $maxTotalResults);
    }

    /**
     * get all collections, include:
     * - mapped to the model using Schemamanager
     * - its aliases
     */
    public function allSchemas(): SupportCollection
    {
        return TypesenseSchemaManager::getSchemaDetails();
    }

    /**
     * Build the search parameters for a given Scout query builder.
     */
    public function buildSearchParameters(Builder $builder, int $page, ?int $perPage): array
    {
        // Wrap the builder into a TypesenseBuilder if not already
        $typesenseBuilder = $builder instanceof TypesenseBuilder
            ? $builder
            : $this->convertToTypesenseBuilder($builder);

        // Extract default search parameters from the parent method
        $parameters = parent::buildSearchParameters($builder, $page, $perPage);

        $parameters = [
            'q' => $builder->query,
            'query_by' => config('scout.typesense.model-settings.'.get_class($builder->model).'.search-parameters.query_by') ?? '',
            'filter_by' => $this->filters($builder),
            'per_page' => $perPage,
            'page' => $page,

            'exhaustive_search' => false,
            'use_cache' => false,
            'cache_ttl' => 60,
            'prioritize_exact_match' => true,
            'enable_overrides' => true,
        ];

        // Merge custom TypesenseBuilder parameters (e.g., facets, sorting)
        $parameters = array_merge($parameters, $typesenseBuilder->buildCustomFacetParameters($parameters));

        // by default we don't want to return snippets
        $typesenseBuilder->withoutSnippets();

        // get the default search parameters from the model
        if (method_exists($builder->model, 'typesenseSearchParameters')) {
            $parameters = array_merge($parameters, $builder->model->typesenseSearchParameters());
        }

        if (! empty($builder->options)) {
            $parameters = array_merge($parameters, $builder->options);
        }

        if (! empty($builder->orders)) {
            if (! empty($parameters['sort_by'])) {
                $parameters['sort_by'] .= ',';
            } else {
                $parameters['sort_by'] = '';
            }

            $parameters['sort_by'] .= $this->parseOrderBy($builder->orders);
        }

        return $parameters;
    }

    /**
     * Summary of enableUpdatingSchema
     *
     * @param  mixed  $alias
     * @param  mixed  $oldCollectionName
     * @param  mixed  $newCollectionName
     */
    public function enableUpdatingSchema($alias, $oldCollectionName, $newCollectionName): void
    {
        // save this in cache
        $value = CSV::from([$oldCollectionName, $newCollectionName])->get();
        Cache::put('typesense_updating.'.$alias, $value, $seconds = 10 * 60);
    }

    /**
     * Summary of getUpdatingSchema
     *
     * @param  mixed  $alias
     */
    public function getUpdatingSchema($alias): ?array
    {
        $value = Cache::get('typesense_updating.'.$alias);
        if ($value) {
            return CSV::from($value)->toArray();
        }

        return null;
    }

    /**
     * Summary of disableUpdatingSchema
     *
     * @param  mixed  $alias
     */
    public function disableUpdatingSchema($alias): void
    {
        Cache::forget('typesense_updating.'.$alias);
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>|Model[]  $models
     *
     * @throws \Http\Client\Exception
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @noinspection NotOptimalIfConditionsInspection
     */
    public function update($models)
    {
        if (! config('typesense.schema.dual_writes')) {
            return parent::update($models);
        }

        if ($models->isEmpty()) {
            return;
        }

        if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
            $models->each->pushSoftDeleteMetadata();
        }

        $objects = $models->map(function ($model) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return null;
            }

            return array_merge(
                $searchableData,
                $model->scoutMetadata(),
            );
        })->filter()->values()->all();

        $model = $models->first();

        $collection = $this->getOrCreateCollectionFromModel($model);

        // while we are updating the schema, and a new document is added, we need to update both new and old schema
        $collections = $this->getUpdatingSchema($model->searchableAs()) ?? [$collection]; // ->retrieve()['name']

        if (! empty($objects)) {
            foreach ($collections as $collection) {

                $this->importDocuments(
                    $collection,
                    $objects
                );
            }
        }
    }

    /**
     * Import the given documents into the index.
     *
     * @param  TypesenseCollection  $collectionIndex
     * @param  array  $documents
     * @param  string  $action
     * @return \Illuminate\Support\Collection
     *
     * @throws \JsonException
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
    protected function importTypesenseDocuments(TypesenseCollection $collectionIndex, array $documents, string $action = 'upsert'): Collection
    {
        log('200:documents', [print_r($documents, true)]);
        log('201:collectionIndex', [print_r($collectionIndex, true)]);

        $importedDocuments = $collectionIndex->getDocuments()->import($documents, ['action' => $action]);

        log( '205:imported docs', [print_r($importedDocuments, true)]);

        $results = [];

        foreach ($importedDocuments as $importedDocument) {
            if (! $importedDocument['success']) {
                throw new TypesenseClientError("Error importing document: {$importedDocument['error']} {$collectionIndex->retrieve()['name']}");
            }

            $results[] = $this->createImportSortingDataObject(
                $importedDocument
            );
        }

        return collect($results);
    }
     */

    /**
     * Get the results of the given query mapped onto models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get(Builder $builder)
    {
        $builder = $this->convertToTypesenseBuilder($builder);

        return $this->mapTypesense(
            $builder, $this->search($builder), $builder->model
        );
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function mapTypesense(TypesenseBuilder $builder, $results, $model)
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

        $this->hits = $this->mapToModels($model, $builder, $objectIds, $objectIdPositions);

        // add support for facets: map, count, etc

        return $this->hits;
    }

    /**
     * Summary of mapToModels
     *
     * @param  mixed  $model
     * @param  mixed  $builder
     * @param  mixed  $objectIds
     * @param  mixed  $objectIdPositions
     * @return mixed
     */
    public function mapToModels($model, $builder, $objectIds, $objectIdPositions)
    {
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
     * Delete the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>|Model[]  $models
     *
     * @throws \Http\Client\Exception
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @todo: implement dual writes for delete
    public function delete($models): void
    {
        $primaryCollection = $models->first()->searchableAs();
        $ids = $models->map->getScoutKey()->toArray();

        // Delete from the primary collection
        $this->typesense->collections[$primaryCollection]->documents->delete([
            'filter_by' => sprintf('id:[%s]', implode(',', $ids)),
        ]);

        // Delete from the secondary collection if dual writes are enabled
        if ($this->newCollectionSuffix) {
            $secondaryCollection = $primaryCollection.'_'.$this->newCollectionSuffix;
            $this->typesense->collections[$secondaryCollection]->documents->delete([
                'filter_by' => sprintf('id:[%s]', implode(',', $ids)),
            ]);
        }
    }
     */
    public function getCollectionFromModel($model, bool $indexOperation = true): ?TypesenseCollection
    {
        if (! method_exists($model, 'searchableAs')) {
            throw new \Exception('Model does not implement search: '.get_class($model));
        }

        $aliasName = $model->searchableAs();
        $currentCollectionName = $this->getCurrentCollectionNameFromAlias(model: $model);

        // Check if the alias already exists
        if ($currentCollectionName) {
            $collection = $this->typesense->getCollections()->{$currentCollectionName};

            try {
                $collection->retrieve();

                // No error means this collection exists on the server...
                $collection->setExists(true);

                return $collection;
            } catch (TypesenseClientError $e) {
                //
            }
        }

        // lets try aliasname as collection name before creating a new collection
        $collection = $this->typesense->getCollections()->{$aliasName};
        try {
            $collection->retrieve();

            // No error means this collection exists on the server...
            $collection->setExists(true);

            return $collection;
        } catch (TypesenseClientError $e) {
            //
        }

        return null;
    }

    /**
     * Get collection from model or create a new one, ensuring an alias is used.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \Http\Client\Exception
     */
    protected function getOrCreateCollectionFromModel($model, ?string $collectionName = null, bool $indexOperation = true): TypesenseCollection
    {
        if (! config('typesense.schema.dual_writes')) {
            return parent::getOrCreateCollectionFromModel($model, $collectionName, $indexOperation);
        }

        if ($collection = $this->getCollectionFromModel($model, $indexOperation)) {
            return $collection;
        }

        // if it does not exists, create a new collection
        $aliasName = $model->searchableAs();

        // Retrieve schema from the model
        if (method_exists($model, 'typesenseCollectionSchema')) {
            /**
             * @var \Ympact\Typesense\Schema\Blueprint $schema
             */
            $schema = $model->typesenseCollectionSchema();
        } else {
            throw new \Exception('Schema not found for model '.get_class($model));
        }
        // using config file is deprecated
        // else {
        //    $schema = config('scout.typesense.model-settings.'.get_class($model).'.collection-schema') ?? [];
        // }

        // create a collection name using the version of the schema
        $version = $schema->getVersion() ?? null;
        if (! $version) {
            throw new \Exception('Schema version is required for dual writes');
        }
        $newCollectionName = $aliasName.'_'.$version;
        $schema->name($newCollectionName);

        // Create the new collection in Typesense
        $this->typesense->getCollections()->create($schema->toArray());

        // Update the alias to point to the new collection
        $this->typesense->getAliases()->upsert($aliasName, ['collection_name' => $newCollectionName]);

        // Return the newly created collection
        $collection = $this->typesense->getCollections()->{$newCollectionName};
        $collection->setExists(true);

        return $collection;
    }

    /**
     * getCurrentCollectionNameFromAlias
     */
    protected function getCurrentCollectionNameFromAlias(?string $aliasName = null, ?Model $model = null): ?string
    {
        if ($model) {
            $aliasName = $model->searchableAs();
        }
        try {
            $alias = $this->typesense->getAliases()->{$aliasName}->retrieve();

            return $alias['collection_name'] ?? null;
        } catch (TypesenseClientError $e) {
            return null;
        }
    }

    public function deleteItem($model, $id)
    {
        $this->deleteDocument(
            $this->getOrCreateCollectionFromModel($model),
            $id
        );

    }

    /**
     * upsertSynonyms
     *
     * @param  mixed  $model
     * @param  mixed  $synonyms
     * @return void
     */
    public function upsertSynonyms($model, $synonyms = [])
    {
        if (method_exists($model, 'searchableAs')) {

            // make sure all synonyms are unique, no empty values or null values, and reset the keys after cleaning up to prevent errors
            $synonyms = array_values(array_unique(array_filter($synonyms)));

            if (count($synonyms) > 1) {
                // make sure the we have at least two items in $synonyms to be able to register synonyms
                $this->typesense->collections[$model->searchableAs()]->synonyms->upsert(
                    $synonyms[0].'-synonyms',
                    ['synonyms' => $synonyms]
                );
            }
            if (
                count($synonyms) == 1 &&
                $this->typesense->collections[$model->searchableAs()]->synonyms->offsetExists($synonyms[0].'-synonyms')
            ) {
                // if there is only one item in the synonym and the id is present, remove the synonym
                $this->typesense->collections[$model->searchableAs()]->synonyms[$synonyms[0].'-synonyms']->delete();
            }
        }
    }

    /**
     * Convert a regular Laravel Scout Builder into a TypesenseBuilder.
     */
    protected function convertToTypesenseBuilder(Builder $builder): TypesenseBuilder
    {
        // Instantiate the custom TypesenseBuilder with core properties
        $typesenseBuilder = new TypesenseBuilder(
            model: $builder->model,
            query: $builder->query,
            callback: $builder->callback,
            softDelete: array_key_exists('__soft_deleted', $builder->wheres)
        );

        // Copy 'where' constraints
        foreach ($builder->wheres as $field => $value) {
            $typesenseBuilder->where($field, $value);
        }

        // Copy 'whereIn' constraints
        foreach ($builder->whereIns as $field => $values) {
            $typesenseBuilder->whereIn($field, $values);
        }

        // Copy 'whereNotIn' constraints
        foreach ($builder->whereNotIns as $field => $values) {
            $typesenseBuilder->whereNotIn($field, $values);
        }

        // Copy 'orders' for sorting
        foreach ($builder->orders as $order) {
            $typesenseBuilder->orderBy($order['column'], $order['direction']);
        }

        // Set additional options
        $typesenseBuilder->options($builder->options);

        // Copy custom index if specified
        if ($builder->index) {
            $typesenseBuilder->within($builder->index);
        }

        // Copy query callback if present
        if ($builder->queryCallback) {
            $typesenseBuilder->query($builder->queryCallback);
        }

        // Copy limit
        if ($builder->limit) {
            $typesenseBuilder->take($builder->limit);
        }

        return $typesenseBuilder;
    }

    public function getCollection($model): TypesenseCollection
    {
        return $this->getOrCreateCollectionFromModel($model);
    }

    public function getCollectionFields(TypesenseCollection|Model $collection)
    {
        if ($collection instanceof Model) {
            $collection = $this->getCollectionFromModel($collection);
        }
        if ($collection instanceof TypesenseCollection) {
            return collect($collection->retrieve()['fields']);
        }

        return null;
    }

    public function getOrphanedDocuments($model): ?SupportCollection
    {
        // Get the collection name from the model
        $perPage = 10;
        $page = 1;
        $orphaned = [];
        $found = [];

        $collection = $this->getCollectionFromModel($model);
        if (! $collection) {
            return null;
        }

        $fields = $this->getCollectionFields($collection)->where('type', 'string')->pluck('name');

        while (true) {
            $results = $collection->getDocuments()->search([
                'q' => '*',
                'query_by' => $fields->join(','),
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $count = $results['found'] ?? 0;
            if ($count === 0) {
                // No results found, exit the loop
                break;
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

            $mapped = $model::whereIn($model->getKeyName(), $objectIds)->pluck('id')->all();
            $found = array_merge($found, $mapped);
            $orphaned = array_merge($orphaned, array_diff($objectIds, $mapped));

            if ($count < $page * $perPage) {
                // No more results or no orphaned documents found
                break;
            }
            $page++;

        }

        // Return the orphaned documents as a collection of IDs
        return collect($orphaned);
    }
}
