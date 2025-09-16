<?php

namespace Ympact\Typesense\Results;

use Ympact\Typesense\Schema\Field;

class Facets
{
    /***
     * facet_counts
     * [
    [
      "counts" => [
        [
          "count" => 9,
          "highlighted" => "41",
          "value" => "41",
        ],
        [
          "count" => 5,
          "highlighted" => "16",
          "value" => "16",
        ],
        [
          "count" => 3,
          "highlighted" => "40",
          "value" => "40",
        ],
        [
          "count" => 2,
          "highlighted" => "38",
          "value" => "38",
        ],
        [
          "count" => 2,
          "highlighted" => "20",
          "value" => "20",
        ],
        [
          "count" => 1,
          "highlighted" => "7",
          "value" => "7",
        ],
        [
          "count" => 1,
          "highlighted" => "15",
          "value" => "15",
        ],
      ],
      "field_name" => "themes",
      "sampled" => false,
      "stats" => [
        "total_values" => 7,
      ],
    ],

    /**
     * Facet counts
     * @var array<Facet>
     */
    public array $facets;

    /**
     * the field on which the facet is applied
     */
    public Field $field;

    /**
     * sampled
     */
    public bool $sampled;

    /**
     * stats
     */
    public array $stats;

    /**
     * Facets constructor.
     */
    public function __construct() {}
}
