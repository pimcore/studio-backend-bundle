<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;

/**
 * Repairs two systematic defects in the generated tool input schemas.
 *
 * Both come from the same place: a #[Schema] attribute declares a parameter's type as a single
 * string, which overrides the nullable type the SDK would otherwise infer from the PHP
 * signature. The declared type then contradicts the `default: null` that the SDK still emits
 * from the parameter's default value, and clients that honour the advertised default are
 * rejected by the schema that advertised it.
 *
 * 1. An optional parameter whose advertised default is null has to accept null.
 * 2. An object parameter has to accept `array` as well. The transport decodes the payload
 *    associatively, so an empty object arrives as an empty PHP array and cannot be told apart
 *    from an empty list - rejecting `array` therefore rejects `{}`, the natural way for a
 *    client UI to say "no value" when it cannot omit the key. Because that also lets a
 *    populated list through, tools guard their object parameters with
 *    {@see ObjectParameterNormalizer}.
 *
 * Apply this centrally, where tools are registered with the MCP server, rather than per
 * parameter: in the Pimcore Agent Bundle the defect affected 35 parameters across 15 tools, and
 * normalising at registration keeps it fixed for tools added later.
 */
final class ToolInputSchemaNormalizer
{
    private function __construct()
    {
        // static-only utility class, must not be instantiated
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public static function normalize(array $schema): array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return $schema;
        }

        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        foreach ($properties as $name => $property) {
            if (is_array($property)) {
                $properties[$name] = self::widenType($property, in_array($name, $required, true));
            }
        }

        $schema['properties'] = $properties;

        return $schema;
    }

    /**
     * @param array<string, mixed> $property
     *
     * @return array<string, mixed>
     */
    private static function widenType(array $property, bool $isRequired): array
    {
        $declared = $property['type'] ?? null;
        if ($declared === null) {
            return $property;
        }

        $types = is_array($declared) ? array_values($declared) : [$declared];

        if (in_array('object', $types, true) && !in_array('array', $types, true)) {
            $types[] = 'array';
        }

        if (self::advertisesNullDefault($property, $isRequired) && !in_array('null', $types, true)) {
            $types[] = 'null';
        }

        $property['type'] = count($types) === 1 ? $types[0] : $types;

        return $property;
    }

    /**
     * @param array<string, mixed> $property
     */
    private static function advertisesNullDefault(array $property, bool $isRequired): bool
    {
        return !$isRequired
            && array_key_exists('default', $property)
            && $property['default'] === null;
    }
}
