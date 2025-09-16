<?php

namespace Ympact\Typesense\Schema;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Ympact\Typesense\Enums\FieldTypeEnum;

/**
 * Field
 *
 * @see https://typesense.org/docs/27.1/api/collections.html#field-parameters
 */
class Field implements Arrayable
{
    /**
     * Name: The name of the field
     */
    protected string $name;

    /**
     * Type: The data type of the field
     *
     * @options string, int, float, bool, string[]
     */
    protected FieldTypeEnum $type;

    /**
     * Facet: Enables faceting on the field
     *
     * @default false
     */
    protected ?bool $facet = null;

    /**
     * Optional: When set to true, the field can have empty, null or missing values
     *
     * @default false
     */
    protected ?bool $optional = null;

    /**
     * Index: When set to false, the field will not be indexed in any in-memory index (e.g. search/sort/filter/facet)
     *
     * @default true
     */
    protected ?bool $index = null;

    /**
     * Store: When set to false, the field value will not be stored on disk
     *
     * @default true
     */
    protected ?bool $store = null;

    /**
     * Sort: When set to true, the field will be sortable
     *
     * @default true for numbers, false otherwise
     */
    protected ?bool $sort = null;

    /**
     * Infix: When set to true, the field value can be infix-searched. Incurs significant memory overhead
     *
     * @default false
     */
    protected ?bool $infix = null;

    /**
     * Locale: For configuring language specific tokenization, e.g. jp for Japanese
     * uses two letter ISO 639 language codes (https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes)
     *
     * @default en which also broadly supports most European languages
     */
    protected ?string $locale = null;

    /**
     * NumDim: Set this to a non-zero value to treat a field of type float[] as a vector field
     */
    protected ?int $numDim = null;

    /**
     * VecDist: The distance metric to be used for vector search
     *
     * @default cosine
     */
    protected ?string $vecDist = null;

    /**
     * Reference: Name of a field in another collection that should be linked to this collection so that it can be joined during query
     *
     * @var array<string>
     */
    protected ?array $reference = null;

    /**
     * RangeIndex: Enables an index optimized for range filtering on numerical fields (e.g. rating:>3.5)
     *
     * @default false
     */
    protected ?bool $rangeIndex = null;

    /**
     * Stem: Values are stemmed before indexing in-memory
     *
     * @default false
     */
    protected ?bool $stem = null;

    /**
     * vector embeddings
     */
    protected ?array $embed = null;

    public function __construct(
        string $name,
        FieldTypeEnum $type,
    ) {
        $this->name = $name;
        $this->type = $type;

        // set defaults (according to the docs)
        $this->facetable(false);
        $this->optional(false);
        $this->index(true);
        $this->store(true);
        $this->sortable($this->isNumeric());
        $this->infixable(false);

        if ($this->isString()) {
            $this->locale(config('app.locale', 'en'));
        }
        $this->rangeIndex(false);
        $this->stemmable(false);
    }

    /**
     * Convert the field to an array
     */
    public function toArray(): array
    {
        return array_filter([
            // mandatory fields
            'name' => $this->name,
            'type' => $this->type->value,
            'facet' => $this->facet,
            'optional' => $this->optional,
            'index' => $this->index,
            'store' => $this->store,
            'sort' => $this->sort,
            'infix' => $this->infix,
            'locale' => $this->locale,
            'num_dim' => $this->numDim,
            'vec_dist' => $this->vecDist,
            'reference' => $this->makeReference(),
            'range_index' => $this->rangeIndex,
            'stem' => $this->stem,
            'embed' => $this->embed,
        ], fn ($value) => $value !== null);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): FieldTypeEnum
    {
        return $this->type;
    }

    public function type(FieldTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isFacetable(): ?bool
    {
        return $this->facet;
    }

    public function facetable(bool $facet = true): static
    {
        $this->facet = $facet;

        return $this;
    }

    public function isOptional(): ?bool
    {
        return $this->optional;
    }

    public function optional(bool $optional = true): static
    {
        $this->optional = $optional;

        return $this;
    }

    public function isIndex(): ?bool
    {
        return $this->index;
    }

    public function index(bool $index = true): static
    {
        $this->index = $index;

        return $this;
    }

    public function isStored(): ?bool
    {
        return $this->store;
    }

    public function store(bool $store = true): static
    {
        $this->store = $store;

        return $this;
    }

    public function isSortable(): ?bool
    {
        return $this->sort;
    }

    public function sortable(bool $sort = true): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function isInfixable(): ?bool
    {
        return $this->infix;
    }

    public function infixable(bool $infix = true): static
    {
        $this->infix = $infix;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function locale(string $locale): static
    {
        // only possible on string fields
        if (! $this->isString()) {
            throw new \InvalidArgumentException('Field ('.$this->getName().') must be of type string to have a locale');
        }

        // uses two letter ISO 639 language codes
        // validate, string must be 2 characters [a-z], non-numeric
        if (! preg_match('/^[a-z]{2}$/', $locale)) {
            throw new \InvalidArgumentException('Locale must be a two letter ISO 639 language code');
        }
        $this->locale = $locale;

        return $this;
    }

    public function getNumDim(): ?int
    {
        return $this->numDim;
    }

    public function numDim(int $numDim): static
    {
        $this->numDim = $numDim;

        return $this;
    }

    public function getVecDist(): ?string
    {
        return $this->vecDist;
    }

    public function vecDist(string $vecDist): static
    {
        // Default: cosine. You can also use ip for inner product.
        // can only be set on vector fields
        if ($this->type !== FieldTypeEnum::FLOAT_ARRAY) {
            throw new \InvalidArgumentException('Field must be of type float array to have a vector distance');
        }
        $this->vecDist = $vecDist;

        return $this;
    }

    public function getReference(): ?array
    {
        return $this->reference;
    }

    public function makeReference(): ?string
    {
        if (! $this->isReference()) {
            return null;
        }
        $model = (new $this->reference[0]);

        return "{$model->searchableAs()}.{$this->reference[1]}";
    }

    public function isReference(): bool
    {
        return isset($this->reference);
    }

    public function reference(string|Model $model, ?string $field = null): static
    {
        // reference can only be set on string fields
        // should be a reference to a field on another collection
        // should be in the format collection.field,
        // in case field is not provided, $collection should already include the field name
        if (((class_exists($model) && is_subclass_of($model, Model::class)) || $model instanceof Model)
            && method_exists($model, 'searchableAs')
        ) {
            if ($model instanceof Model) {
                $model = $model::class;
            }
            $this->reference = [$model, $field];

            return $this;
        }

        throw new \InvalidArgumentException('Model `'.$model.'` must be searchable');
    }

    public function isRangeIndex(): ?bool
    {
        return $this->rangeIndex;
    }

    public function rangeIndex(bool $rangeIndex = true): static
    {
        // can only be true on numeric fields
        if ($rangeIndex && ! in_array($this->type, [FieldTypeEnum::INT32, FieldTypeEnum::INT64, FieldTypeEnum::FLOAT], true)) {
            throw new \InvalidArgumentException('Field must be of type int or float to have a range index');
        }
        $this->rangeIndex = $rangeIndex;

        return $this;
    }

    public function isStemmable(): ?bool
    {
        return $this->stem;
    }

    public function stemmable(bool $stem = true): static
    {
        $this->stem = $stem;

        return $this;
    }

    public function autoEmbed(array $from, array|string $model): static
    {

        $model = is_array($model) ? $model : ['model_name' => $model];

        $this->embed = [
            'from' => $from,
            'model_config' => $model,
        ];

        return $this;
    }

    public function isNumeric()
    {
        return in_array($this->type, [
            FieldTypeEnum::INT32,
            FieldTypeEnum::INT64,
            FieldTypeEnum::FLOAT,
        ], true);
    }

    public function isString()
    {
        return in_array($this->type, [
            FieldTypeEnum::STRING,
            FieldTypeEnum::STRING_ARRAY,
            FieldTypeEnum::STRING_STAR,
        ], true);
    }

    public function isArray()
    {
        return in_array($this->type, [
            FieldTypeEnum::STRING_ARRAY,
            FieldTypeEnum::INT32_ARRAY,
            FieldTypeEnum::INT64_ARRAY,
            FieldTypeEnum::FLOAT_ARRAY,
            FieldTypeEnum::BOOL_ARRAY,
            FieldTypeEnum::GEOPOINT_ARRAY,
            FieldTypeEnum::OBJECT_ARRAY,
        ], true);
    }

    public function isObject()
    {
        return in_array($this->type, [
            FieldTypeEnum::OBJECT,
            FieldTypeEnum::OBJECT_ARRAY,
        ], true);
    }
}
