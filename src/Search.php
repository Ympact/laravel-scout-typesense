<?php

namespace Ympact\Typesense;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated use ModelSearch instead
 */
abstract class Search
{
    protected Model $model;

    /**
     * Default implmentation
     * Can be overridden in the child class
     */
    public function shouldBeSearchable(): bool
    {
        // if the model has a isPublished method, use this by default, otherwise return true
        if (method_exists($this->model, 'isPublished')) {
            return $this->model->isPublished();
        }

        return true;
    }
}
