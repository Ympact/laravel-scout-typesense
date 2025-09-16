<?php

namespace Ympact\Typesense;

use Laravel\Scout\EngineManager;
use Ympact\Typesense\Services\TypesenseEngine;

if (! function_exists('typesense')) {
    /**
     * Get the typesenseEngine
     */
    function typesense(): TypesenseEngine
    {
        /**
         * @var \Ympact\Typesense\Services\TypesenseEngine $ts
         *
         * @see \Typesense\Client
         */
        $ts = resolve(EngineManager::class)->engine('typesense-extended');

        return $ts;
    }
}
