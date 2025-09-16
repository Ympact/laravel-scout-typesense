<?php

namespace Ympact\Typesense\Enums;

enum FieldTypeEnum: string
{
    case STRING = 'string';

    case STRING_ARRAY = 'string[]';

    case INT32 = 'int32';

    case INT32_ARRAY = 'int32[]';

    case INT64 = 'int64';

    case INT64_ARRAY = 'int64[]';

    case FLOAT = 'float';

    case FLOAT_ARRAY = 'float[]';

    case BOOL = 'bool';

    case BOOL_ARRAY = 'bool[]';

    case GEOPOINT = 'geopoint';

    case GEOPOINT_ARRAY = 'geopoint[]';

    case OBJECT = 'object';

    case OBJECT_ARRAY = 'object[]';

    case STRING_STAR = 'string*';

    case IMAGE = 'image';

    case AUTO = 'auto';

    public static function details($case): array
    {
        return match ($case) {
            self::STRING => [
                'label' => 'String',
                'description' => 'String values',
            ],
            self::STRING_ARRAY => [
                'label' => 'String Array',
                'description' => 'Array of strings',
            ],
            self::INT32 => [
                'label' => 'Int32',
                'description' => 'Integer values up to 2,147,483,647',
            ],
            self::INT32_ARRAY => [
                'label' => 'Int32 Array',
                'description' => 'Array of int32',
            ],
            self::INT64 => [
                'label' => 'Int64',
                'description' => 'Integer values larger than 2,147,483,647',
            ],
            self::INT64_ARRAY => [
                'label' => 'Int64 Array',
                'description' => 'Array of int64',
            ],
            self::FLOAT => [
                'label' => 'Float',
                'description' => 'Floating point / decimal numbers',
            ],
            self::FLOAT_ARRAY => [
                'label' => 'Float Array',
                'description' => 'Array of floating point / decimal numbers',
            ],
            self::BOOL => [
                'label' => 'Bool',
                'description' => 'true or false',
            ],
            self::BOOL_ARRAY => [
                'label' => 'Bool Array',
                'description' => 'Array of booleans',
            ],
            self::GEOPOINT => [
                'label' => 'Geopoint',
                'description' => 'Latitude and longitude specified as [lat, lng].',
            ],
            self::GEOPOINT_ARRAY => [
                'label' => 'Geopoint Array',
                'description' => 'Arrays of Latitude and longitude specified as [[lat1, lng1], [lat2, lng2]].',
            ],
            self::OBJECT => [
                'label' => 'Object',
                'description' => 'Nested objects.',
            ],
            self::OBJECT_ARRAY => [
                'label' => 'Object Array',
                'description' => 'Arrays of nested objects.',
            ],
            self::STRING_STAR => [
                'label' => 'String Star',
                'description' => 'Special type that automatically converts values to a string or string[].',
            ],
            self::IMAGE => [
                'label' => 'Image',
                'description' => 'Base64 encoded image data.',
            ],
            self::AUTO => [
                'label' => 'Auto',
                'description' => 'Automatically detect the type of the field.',
            ],
        };
    }

    /**
     * get the array representation of a FieldTypeEnum in case it exists: ie STRING -> STRING_ARRAY
     *
     * @usage FieldTypeEnum::STRING->asArray() // returns FieldTypeEnum::STRING_ARRAY
     */
    public function asArray(): FieldTypeEnum
    {
        return match ($this) {
            self::STRING => self::STRING_ARRAY,
            self::INT32 => self::INT32_ARRAY,
            self::INT64 => self::INT64_ARRAY,
            self::FLOAT => self::FLOAT_ARRAY,
            self::BOOL => self::BOOL_ARRAY,
            self::GEOPOINT => self::GEOPOINT_ARRAY,
            self::OBJECT => self::OBJECT_ARRAY,
            // default => self::class,
        };
    }
}
