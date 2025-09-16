<?php

namespace Ympact\Typesense\Services\Contracts;

use Ympact\Typesense\Parameters\Blueprint as ParametersBlueprint;
use Ympact\Typesense\Schema\Blueprint as SchemaBlueprint;

interface ModelSearchInterface
{
    public function toSearchableArray();

    public function schema(): SchemaBlueprint;

    // public function typesenseSearchParameters();
    public function parameters(): ParametersBlueprint;

    public function shouldBeSearchable(): bool;

    public function typesenseSynonyms(): ?array;
}
