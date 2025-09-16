# ScoutTypesense

This is a Laravel Scout Typesense package.
The package is still in beta.

## Aim of the pacakge

- Support many of the Typesense features
- Easy mangement of schema's and documents
- Support non-model based schema's and documents
- Supporting seemless updating of schema's without downtime (using aliases)
- Supporting LLM

## Example

Below are some examples of how the schema defintion and default parameter definition look like. They are defined in a `Search` class within a Search directory.

### Schema

Defining a schema for Typesense is very similar to a migration schema

```php
public function schema(): SchemaBlueprint
{
    return Schema::create(BlogPost::class, function (SchemaBlueprint $collection, $model) {
        // default fields
        $collection->id();
        $collection->title(); // is sortable by default

        // custom fields
        $collection->string('content')->optional()
            ->from($this->model->content->toString()); 

        $collection->string('themes')->asArray()->foreignId(Theme::class)->optional()->facetable()
            ->from($this->model->themes->pluck('id')); 
        $collection->string('tags')->asArray()->foreignId(Tag::class)->optional()->facetable()
            ->from($this->model->tags->pluck('id')); 

        $collection->hasSoftDelete();
        $collection->timestamps();

        $collection->enableNestedFields();

        // use for versioning: in case schema is changed, update this date
        $collection->version('2025.02.26');
    });
}
```

### Default search parameters

```php
public function parameters(): ParametersBlueprint
{
    $params = Parameters::create($this->model, function (ParametersBlueprint $parameters) {
        $parameters->query()->queryBy(['title', 'content']);
        $parameters->snippet()->fields('title');
        $parameters->result()->include([
            'themes' => ['title'],
            'tags' => ['title'],
        ]);
        $parameters->facet()->facetBy(['themes', 'type']);
    });

    return $params;
}
```

## Roadmap
- [x] Easy mangement of schema's and documents
- [x] Supporting seemless updating of schema's without downtime (using aliases)
- [ ] Support non-model based schema's and documents
- [ ] Support LLM



<!--
[![Packagist Version](https://img.shields.io/packagist/v/ympact/boilplate)](https://packagist.org/packages/ympact/ScoutTypesense)


## Installation

```cmd
composer require --dev ympact/laravel-scout-typesense
```
-->