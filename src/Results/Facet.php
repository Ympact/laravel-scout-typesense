<?php

namespace Ympact\Typesense\Results;

class Facet
{
    public int $count;

    public string $highlighted;

    public string $value;

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
]
     */
}
