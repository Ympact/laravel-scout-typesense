<?php

namespace Ympact\Typesense\Traits;

use Illuminate\Support\Facades\Cache;

trait TypesenseClientTrait
{
    /**
     * Summary of allCollections
     */
    public function allCollections(): \Illuminate\Support\Collection
    {
        return collect($this->typesense->collections->retrieve());
        // should we use cache here?
        // return Cache::flexible('typesenseCollections', [5,30], fn() => collect($this->typesense->collections->retrieve()));
    }

    /**
     * hasCollections
     */
    public function hasCollections(): bool
    {
        return $this->allCollections()->isNotEmpty();
    }

    /**
     * isCollection
     */
    public function isCollection(string $collectionName): bool
    {
        return $this->allCollections()->where('name', $collectionName)->count() > 0;
    }

    /**
     * Summary of allAliases
     */
    public function allAliases(): \Illuminate\Support\Collection
    {
        return collect($this->typesense->aliases->retrieve()['aliases']);
    }

    /**
     * hasAliases
     */
    public function hasAliases(): bool
    {
        return $this->allAliases()->isNotEmpty();
    }

    /**
     * collection has alias?
     */
    public function hasAlias(string $collectionName): bool
    {
        return $this->allAliases()->where('collection_name', $collectionName)->count() > 0;
    }

    /**
     * isAlias
     */
    public function isAlias(string $aliasName): bool
    {
        return $this->allAliases()->where('name', $aliasName)->count() > 0;
    }

    /**
     * get alias from collection name
     */
    public function getAliasFromCollection(string $collectionName): ?string
    {
        return $this->allAliases()->where('collection_name', $collectionName)->pluck('name')->first();
    }

    /**
     * get collection from alias name
     */
    public function getCollectionFromAlias(string $aliasName): ?string
    {
        return $this->allAliases()->where('name', $aliasName)->pluck('collection_name')->first();
    }
}
