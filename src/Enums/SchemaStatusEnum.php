<?php

namespace Ympact\Typesense\Enums;

enum SchemaStatusEnum: string
{
    case OUTDATED = 'outdated';

    case BASE = 'base';

    case CUSTOMIZED = 'customized';

    case NOT_AVAILABLE = 'not_available';

    public static function details($case): array
    {
        return match ($case) {
            self::OUTDATED => [
                'label' => 'Outdated',
                'description' => 'The schema is outdated and needs to be updated.',
            ],
            self::BASE => [
                'label' => 'Base',
                'description' => 'The schema is the base schema.',
            ],
            self::CUSTOMIZED => [
                'label' => 'Customized',
                'description' => 'The schema has been manually updated and is not in sync with the base schema.',
            ],
            self::NOT_AVAILABLE => [
                'label' => 'Not Available',
                'description' => 'The schema is not available in Typesense.',
            ],
        };
    }
}
