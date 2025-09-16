<?php

namespace Ympact\Typesense\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Ympact\Typesense\Enums\FieldTypeEnum;
use Ympact\ValueObjects\Types\Strings\CSV;

class Blueprint
{
    use Conditionable, Macroable, Tappable;

    /**
     * Name: The name of the collection
     */
    protected string $name;

    /**
     * Label: The human readable name of the collection
     */
    protected string $label;

    /**
     * Fields: The fields in the collection
     *
     * @var Field[]
     */
    protected array $fields = [];

    /**
     * Default sorting field: The field to use for sorting if no sort_by parameter is provided
     */
    protected ?string $defaultSort = null;

    /**
     * Metadata: Additional metadata to store with the collection
     */
    protected ?array $metadata = null;

    /**
     * Nested fields: Whether to enable nested fields
     */
    protected bool $enableNestedFields = true;

    /**
     * Default sorting field: The field to use for sorting if no sort_by parameter is provided
     *
     * @var array<string>
     */
    protected ?array $tokenSeparators = null;

    /**
     * Symbols to index: The symbols to index
     *
     * @var array<string>
     */
    protected ?array $symbolsToIndex = null;

    /**
     * Set the name of the collection
     */
    public function name(string|Model $name): self
    {
        if ($name instanceof Model) {
            $name = $name->searchableAs();
        }
        $this->name = $name;

        return $this;
    }

    //
    // Common fields
    //
    /**
     * Add an id field to the schema
     */
    public function id(string $name = 'id'): Field
    {
        return $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::STRING
        ));
    }

    /**
     * Add a title field to the schema
     * Allows for stemming
     */
    public function title(string $name = 'title', ?string $locale = null): Field
    {
        // use the default locale of the laravel app
        $locale = $locale ?? config('app.locale');
        $field = new Field(
            name: $name,
            type: FieldTypeEnum::STRING
        );

        return $this->addField($field->locale($locale)->sortable()->stemmable());
    }

    /**
     * Add a simple foreign field to the schema that references tags, themes, etc.
     * Supports stemming, sorting and faceting
     */
    public function tags(string|Model $model, string $name = 'tags', ?string $locale = null): Field
    {
        // use the default locale of the laravel app
        $locale = $locale ?? config('app.locale');
        $field = new Field(
            name: $name,
            type: FieldTypeEnum::STRING_ARRAY
        );

        return $this->addField($field->reference($model, 'id')->facetable()->locale($locale)->stemmable());
    }

    /**
     * Add a created_at field to the schema
     */
    public function createdAt(string $name = 'created_at'): Field
    {
        $field = new Field(
            name: $name,
            type: FieldTypeEnum::INT32
        );

        return $this->addField($field->sortable());
    }

    /**
     * Add an updated_at field to the schema
     */
    public function updatedAt(string $name = 'updated_at'): Field
    {
        $field = new Field(
            name: $name,
            type: FieldTypeEnum::INT32
        );

        return $this->addField($field->sortable());
    }

    /**
     * Add default laravel timestamps to the schema
     */
    public function timestamps(): self
    {
        $this->createdAt();
        $this->updatedAt();

        return $this;
    }

    /**
     * Add a soft delete field to the schema
     */
    public function hasSoftDelete(string $name = '__soft_deleted'): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::INT32
        ));

        return $this;
    }

    //
    // Custom fields (by type)
    //
    /**
     * Add a string field to the schema
     */
    public function string(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::STRING
        ));

        return $this;
    }

    /**
     * Add an integer field to the schema
     */
    public function integer(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::INT32
        ));

        return $this;
    }

    /**
     * Add a big integer field to the schema
     */
    public function bigInteger(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::INT64
        ));

        return $this;
    }

    /**
     * Add a float field to the schema
     */
    public function float(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::FLOAT
        ));

        return $this;
    }

    /**
     * Add a boolean field to the schema
     */
    public function boolean(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::BOOL
        ));

        return $this;
    }

    /**
     * Add an object field to the schema
     */
    public function object(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::OBJECT
        ));

        return $this;
    }

    /**
     * Add a geometry point or polygon field to the schema
     */
    public function geometry(string $name, ?string $type = 'point'): self
    {
        $this->addField(new Field(
            name: $name,
            type: $type == 'point' ? FieldTypeEnum::GEOPOINT : FieldTypeEnum::GEOPOINT_ARRAY
        ));

        return $this;
    }

    /**
     * Add an image field to the schema
     *
     * @see https://typesense.org/docs/27.1/api/image-search.html#create-a-collection
     */
    public function image(string $name): self
    {
        $image = new Field(
            name: $name,
            type: FieldTypeEnum::IMAGE
        );

        $this->addField($image->store(false));
        $this->autoEmbed([$name], 'ts/clip-vit-b-p32', 'embedding');

        return $this;
    }

    /**
     * Add a timestamp field to the schema
     */
    public function timestamp(string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::INT32
        ));

        return $this;
    }

    /**
     * Add a vector field to the schema
     */
    public function vector(string $name, int $dimensions): self
    {
        $field = new Field(
            name: $name,
            type: FieldTypeEnum::FLOAT_ARRAY
        );
        $this->addField($field->numDim($dimensions));

        return $this;
    }

    /**
     * Add an embedding field to the schema
     * Used for LLM's and other embeddings
     *
     * @see https://typesense.org/docs/27.1/api/vector-search.html#option-a-importing-externally-generated-embeddings-into-typesense
     */
    public function embed(string $name = 'embedding', int $dimensions = 256): self
    {
        $this->vector($name, $dimensions);

        return $this;
    }

    /**
     * Add an auto embedding field to the schema
     * Used for LLM's and other embeddings
     *
     * @see https://typesense.org/docs/27.1/api/vector-search.html#option-b-auto-embedding-generation-within-typesense
     */
    public function autoEmbed(array $from, string|array $model, string $name = 'embedding'): self
    {
        // the $from array should contain the fields to embed
        // validate that all fields in the $from array are in the fields array
        $availableFields = array_map(fn ($f) => $f->getName(), $this->fields);
        foreach ($from as $f) {
            if (! in_array($f, $availableFields)) {
                throw new \InvalidArgumentException("Field $f does not exist in the schema");
            }
        }

        $field = new Field(
            name: $name,
            type: FieldTypeEnum::FLOAT_ARRAY
        );
        $this->addField($field->autoEmbed($from, $model));

        return $this;
    }

    /**
     * Add a custom type field to the schema
     */
    public function type(?string $type, ?string $name): self
    {
        $this->addField(new Field(
            name: $name,
            type: FieldTypeEnum::from($type)
        ));

        return $this;
    }

    //
    // Field modifiers
    //
    /**
     * Set the field as an array
     */
    public function asArray(): self
    {
        $field = $this->currentField();

        $this->updateField($field->type(
            $field->getType()->asArray()
        ));

        return $this;
    }

    /**
     * Enable faceting on the field
     */
    public function facetable(bool $value = true): self
    {
        $this->updateField($this->currentField()->facetable($value));

        return $this;
    }

    /**
     * Set the field as optional
     */
    public function optional(bool $value = true): self
    {
        $this->updateField($this->currentField()->optional($value));

        return $this;
    }

    /**
     * Mark the field as indexable
     */
    public function index(bool $value = true): self
    {
        $this->updateField($this->currentField()->index($value));

        return $this;
    }

    /**
     * Store the field in the collection
     */
    public function store(bool $value = true): self
    {
        $this->updateField($this->currentField()->store($value));

        return $this;
    }

    /**
     * Make the field sortable
     */
    public function sortable(?bool $value = true, ?bool $default = false): self
    {
        $this->updateField($this->currentField()->sortable($value));

        // if the field is set as default, set it as the default sorting field
        if ($value && $default) {
            $this->sort($this->currentField()->getName());
        }

        return $this;
    }

    /**
     * Make the field infixable
     */
    public function infixable(bool $value = true): self
    {
        $this->updateField($this->currentField()->infixable($value));

        return $this;
    }

    /**
     * Set the locale of the field
     * Especially useful for string fields and in combination with stemming
     */
    public function locale(string $locale): self
    {
        $this->updateField($this->currentField()->locale($locale));

        return $this;
    }

    /**
     * Set the number of dimensions for a vector field
     */
    public function dimensions(int $numDim): self
    {
        $this->updateField($this->currentField()->numDim($numDim));

        return $this;
    }

    /**
     * Set the distance metric for a vector field
     */
    public function distance(string $type = 'cosine'): self
    {
        // type should either be 'cosine' or 'ip' (internal product)
        if (! in_array($type, ['cosine', 'ip'])) {
            throw new \InvalidArgumentException('Distance type should be either cosine or ip');
        }

        $this->updateField($this->currentField()->vecDist($type));

        return $this;
    }

    /**
     * Reference a field in another collection
     */
    public function reference(string|Model $model, string $field): self
    {
        $this->foreignField($model, $field);

        return $this;
    }

    /**
     * Alias for reference
     */
    public function foreignField(string|Model $foreignModel, string $foreignField): self
    {
        // model must be a subclass of Model and has a searchableAs method
        if (((class_exists($foreignModel) && is_subclass_of($foreignModel, Model::class)) || $foreignModel instanceof Model)
            && method_exists($foreignModel, 'searchableAs')
        ) {
            $this->updateField($this->currentField()->reference($foreignModel, $foreignField));

            return $this;
        }

        throw new \InvalidArgumentException('Model `'.$foreignModel.'` must be searchable');
    }

    /**
     * Set the id of a foreign collection as reference
     */
    public function foreignId(string|Model $foreignModel): self
    {
        $this->updateField($this->currentField()->reference($foreignModel, 'id'));

        return $this;
    }

    /**
     * Set the field as a range index: useful for filtering
     */
    public function range(bool $value = true): self
    {
        $this->updateField($this->currentField()->rangeIndex($value));

        return $this;
    }

    /**
     * Set the field as a stem field
     */
    public function stemmable(bool $value = true): self
    {
        $this->updateField($this->currentField()->stemmable($value));

        return $this;
    }

    //
    // Schema modifiers
    //
    /**
     * Enable nested fields
     */
    public function enableNestedFields(bool $value = true): self
    {
        $this->enableNestedFields = $value;

        return $this;
    }

    /**
     * Set the default sorting field for this collection
     */
    public function sort(string|array $fieldName): self
    {
        // validate that the field name is present in the fields array and has sorting enabled
        // (numeric fields are sortable by default)
        if (collect($this->fields)->filter(fn (Field $f) => $f->getName() === $fieldName && $f->isSortable() && $f->isNumeric())->isEmpty()) {
            throw new \InvalidArgumentException('Field '.$fieldName.' is either not present or not sortable');
        }

        $this->defaultSort = $fieldName;

        return $this;
    }

    /**
     * Set the token separators
     *
     * @param  array<string>  $separators
     */
    public function tokenSeparators(array $separators): self
    {
        $this->tokenSeparators = $separators;

        return $this;
    }

    /**
     * Set the symbols to index
     *
     * @param  array<string>  $symbols
     */
    public function symbolsToIndex(array $symbols): self
    {
        $this->symbolsToIndex = $symbols;

        return $this;
    }

    /**
     * Set the version of the schema
     *
     * @param  string  $key
     * @param  string  $value
     */
    public function version(string $version): self
    {
        $this->metadata['version'] = $version;

        return $this;
    }

    /**
     * Set metadata for the schema
     */
    public function metadata(string $key, string $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Convert the blueprint to the schema array
     */
    public function toArray(): array
    {
        // validate if certain fiels are set
        // name, metadata['version'] and have at least created_at fields
        if (! isset($this->name)) {
            throw new \InvalidArgumentException('Name is required');
        }
        if (! isset($this->metadata) && ! $this->metadata['version']) {
            throw new \InvalidArgumentException('Metadata version is required');
        }
        if (! in_array('created_at', array_map(fn (Field $f) => $f->getName(), $this->fields))) {
            throw new \InvalidArgumentException('created_at field is required');
        }

        return array_filter([
            'name' => $this->name,
            'fields' => array_map(fn (Field $f) => $f->toArray(), $this->fields),
            'metadata' => $this->metadata,
            'default_sorting_field' => $this->defaultSort,
            'token_separators' => $this->tokenSeparators,
            'symbols_to_index' => $this->symbolsToIndex,
            'enable_nested_fields' => $this->enableNestedFields,
        ], fn ($value) => ! is_null($value));

        /*
        $result = [
            'name' => $this->name,
            'fields' => array_map(fn (Field $f) => $f->toArray(), $this->fields),
            'metadata' => $this->metadata,
        ];

        if (isset($this->defaultSort)) {
            $result['default_sorting_field'] = $this->defaultSort;
        }
        if (isset($this->tokenSeparators)) {
            $result['token_separators'] = $this->tokenSeparators;
        }
        if (isset($this->symbolsToIndex)) {
            $result['symbols_to_index'] = $this->symbolsToIndex;
        }
        if (isset($this->enableNestedFields)) {
            $result['enable_nested_fields'] = $this->enableNestedFields;
        }
        return $result;
        */
    }

    /**
     * get name
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return Arr::get($this->metadata, 'version');
    }

    /**
     * Add a field to the schema
     */
    private function addField(Field $field): Field
    {
        // we need to validate that the field name is unique
        if (in_array($field->getName(), array_map(fn (Field $f) => $f->getName(), $this->fields))) {
            throw new \InvalidArgumentException('Field name '.$field->getName().' already defined.');
        }
        $this->fields[] = $field;

        return $field;
    }

    /**
     * updateField
     */
    private function updateField(Field $field): void
    {
        $this->fields[array_key_last($this->fields)] = $field;
    }

    /**
     * currentField
     */
    private function currentField(): Field
    {
        return $this->fields[array_key_last($this->fields)];
    }

    /**
     * Does the field exist in the schema
     */
    public function hasField(string|Field $field): bool
    {
        if ($field instanceof Field) {
            return in_array($field, $this->fields, true);
        }

        return in_array($field, array_map(fn (Field $f) => $f->getName(), $this->fields));
    }

    /**
     * Does the schema have all the fields
     *
     * @param  array<string>|array<Field>|CSV|string  $fields
     */
    public function hasFields(array|CSV|string $fields): bool
    {
        $fields = CSV::from($fields);

        return collect($fields)->filter(fn ($f) => $this->hasField($f))->count() === count($fields->toArray());
    }

    /**
     * get a single field from the schema
     */
    public function getField(string $name): ?Field
    {
        return collect($this->fields)->first(fn (Field $f) => $f->getName() === $name);
    }

    /**
     * get a selection of fields from the schema
     *
     * @param  array<string>|array<Field>|CSV|string  $names
     * @return Collection<Field>|null
     */
    public function selectFields(array|CSV|string $fields): ?Collection
    {
        if (is_array($fields)) {
            return collect($fields)->filter(fn ($f) => $this->hasField($f));
        }
        $fields = CSV::from($fields);

        return collect($this->fields)->filter(fn (Field $f) => in_array($f->getName(), $fields->toArray()));
    }

    /**
     * getFields
     *
     * @return Collection<Field>
     */
    public function getFields(): Collection
    {
        return collect($this->fields);
    }

    /**
     * getNestedFields
     * either of type object or have a reference
     *
     * @return Collection<Field>
     */
    public function getNestedFields(): Collection
    {
        return collect($this->fields)->filter(fn (Field $f) => $f->isObject() || $f->isReference());
    }
}
