<?php

namespace Ympact\Typesense\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Stringable;
use Ympact\Typesense\Schema\Blueprint as Schema;
use Ympact\Typesense\Schema\Field;
use Ympact\ValueObjects\Types\Strings\CSV;

/**
 * a (subset) collection of fields that are present in the schema
 */
class Fields implements Arrayable, Stringable
{
    /**
     * Model $model
     */
    protected Model $model;

    /**
     * Schema $schema
     */
    protected Schema $schema;

    /**
     * Summary of fields
     *
     * @var Collection<array>
     */
    public ?Collection $fields;

    public function __construct(Model $model, string|array|null $fields = null)
    {
        $this->model = $model;
        $this->schema = $model->searchService->schema();

        $this->fields = collect();
        if ($fields) {
            $this->add($fields);
        }
    }

    /**
     * Create a new Fields instance
     */
    public static function from(Model $model, string|array|Fields|null $fields = null): Fields
    {
        if ($fields instanceof Fields) {
            return $fields;
        }

        return new static($model, $fields);
    }

    /**
     * select all fields from the schema
     */
    public function all(): static
    {
        $this->schema->getFields()->each(function ($field) {
            $this->add($field);
        });

        return $this;
    }

    /**
     * get the fields
     */
    public function selectFields($fields)
    {
        $fields = $this->schema->selectFields($fields);
        if (! $fields) {
            throw new \Exception('Fields do not exist in the schema');
        }

        return $fields;
    }

    /**
     * add a field from another document through a join operation
     * should be from a collection that is joined/referenced
     * output format should be $collection(field,field2)
     * can also be nested $collectionA(field,$collectionB(field2))
     *
     * @param  Field|string  $field  the field on which the join is performed
     * @param  Fields|array|Field|string  $joinFields  the fields to join
     */
    public function join(Field|string $field, Fields|array|Field|string $joinFields): self
    {
        $reference = null;
        // check if field exists in the schema and is a reference
        if (is_string($field)) {
            $field = $this->schema->getField($field);
            // todo? fallback: probably the foreignModel was passed instead

            if (! $field) {
                throw new \Exception("Field $field does not exist in the schema");
            }
            if (! $field->isReference()) {
                throw new \Exception("Field {$field->getName()} is not a reference field");
            }
            $reference = $field->getReference();

        }
        // check if field exists in the schema
        if ($joinFields instanceof Field) {
            $joinFields = $joinFields->getName();
        }
        if ($joinFields instanceof Fields) {
            $joinFields = $joinFields->get();
        }
        $foreignModel = new $reference[0];

        $joinFields = $foreignModel->searchService->schema()->selectFields($joinFields);
        if (! $joinFields) {
            throw new \Exception("Field $joinFields does not exist in the schema");
        }

        $key = $foreignModel->searchableAs();
        $reference = Fields::from($foreignModel, $joinFields->toArray());

        $this->updateOrPush([
            'key' => $key,
            'type' => 'join',
            'reference' => $reference,
            'params' => null,
        ]);

        return $this;
    }

    /**
     * Summary of add
     *
     * @throws \Exception
     */
    public function add(Field|string|array $field, array $params = []): self
    {

        if (is_array($field)) {
            foreach ($field as $key => $f) {
                // if we have a key value pair, we should use join instead of add
                if (is_string($key)) {
                    $this->join($key, $f);

                    continue;
                }
                $this->add($f);
            }

            return $this;
        }

        // check if field exists in the schema
        if (is_string($field)) {
            $fieldName = $field;
            $field = $this->schema->getField($field);

            if (! $field) {
                throw new \Exception("Field '$fieldName' does not exist in the schema `{$this->schema->getName()}`");
            }
        }
        $this->updateOrPush([
            'key' => $field->getName(),
            'type' => 'field',
            'reference' => $field,
            'params' => $params,
        ]);

        return $this;
    }

    /**
     * set a param on a field
     *
     * @param  mixed  $param
     * @param  mixed  $value
     * @param  mixed  $strategy
     * @return Fields
     */
    public function param(Field|string $fieldName, $param, $value): static
    {
        // add a property to a field
        $fieldName = $fieldName instanceof Field ? $fieldName->getName() : $fieldName;

        $field = $this->fields->get($fieldName);
        if (! $field) {
            throw new \Exception("Field $fieldName does not exist in the schema");
        }

        $field['params'] = array_merge($field['params'], [$param => $value]);

        // update the field in fields
        $this->updateOrPush($field);

        return $this;
    }

    /**
     * remove a field from the fields
     */
    public function remove(Field|string $field): self
    {
        if (is_string($field)) {
            $field = $this->schema->getField($field);
        }
        $this->fields = $this->fields->reject(function ($key, $item) use ($field) {
            return $key === $field->getName();
        });

        return $this;
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return $this->mapped()->toArray();
    }

    /**
     * get the fields as a collection
     */
    public function mapped(): Collection
    {
        return $this->fields->map(function ($item) {
            if ($item['type'] === 'join') {
                $collection = $item['key'];
                $fields = $item['reference']->get();

                return [$collection => $fields];
            }

            // if the type is a field, just get the name from the reference
            return $item['reference']->getName();

        });
    }

    /**
     * Get all the field names as a string
     *
     * @todo get the props of the fields
     */
    public function get($prop = false): string
    {
        return $this->__toString();
    }

    /**
     * Convert the fields to a concatenated string
     */
    public function __toString()
    {
        // return the names of the fields as a comma separated string
        // if the type  is a join field, get the fields from the reference
        // format is "${collectionname}(field1,field2)"
        // "\${$collection}({$fields})";

        return collect($this->toArray())->map(function ($item) {
            if (is_array($item)) {
                $collection = key($item);
                $fields = $item[$collection];

                return "\${$collection}({$fields})";
            }

            return $item;
        })->implode(',');
    }

    /**
     * sort the fields manually
     * use case: the query_by parameter in Typesense
     */
    public function sort(array|CSV|Fields $fields): self
    {
        // sort the fields according to the $fields array
        $fields = $fields instanceof Fields ? $fields->get() : $fields;
        $this->fields = $this->fields->sortBy(function ($item) use ($fields) {
            return array_search($item['field']->getName(), $fields);
        });

        return $this;
    }

    public function getParams($param)
    {
        // get the fields with a property
        return $this->fields->filter(function ($item) use ($param) {
            return array_key_exists($param, $item['params']);
        })->pluck("params.{$param}");
    }

    public function getWithParams($param)
    {
        // get the fields with a property
    }

    /**
     * Summary of updateOrPush
     *
     * @return void
     */
    private function updateOrPush(array $props)
    {
        $key = $this->fields->search(function ($item) use ($props) {
            return $item['key'] === $props['key'];
        });
        if ($key === false) {
            $this->fields->push($props);
        } else {
            $this->fields->replace([$key => $props]);
        }
    }
}
