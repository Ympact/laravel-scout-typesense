<?php

namespace Ympact\Typesense\Services;

use Illuminate\Database\Eloquent\Model;

class CollectionService
{
    public $engine;

    public function __construct(
        public Model $model
    ) {
        $this->engine = typesense();
    }

    /**
     * Delete an item from the collection.
     */
    public function delete($id): void
    {
        $this->engine->deleteDocument(
            $this->engine->getCollection($this->model),
            $id
        );
    }
}
