<?php

namespace Ympact\Typesense\Services;

use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Services\Contracts\ModelSearchInterface;

abstract class ModelSearch implements ModelSearchInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function toSearchableArray()
    {
        return array_merge($this->model->toArray(), [
            'id' => (string) $this->model->id,
            'created_at' => $this->model->created_at->timestamp,
            'updated_at' => $this->model->updated_at->timestamp ?? 0,
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        if (method_exists($this->model, 'isPublished')) {
            return $this->model->isPublished();
        }

        return true;
    }

    public function typesenseSynonyms(): ?array
    {
        return null;
    }
}
