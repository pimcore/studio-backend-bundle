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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Service;

use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;

/**
 * Pure, container-independent transformations on an OpenAPI spec in array form.
 * Used by the export pipeline (OpenApiService::exportConfig) and usable from a
 * plain CLI context without a kernel.
 *
 * @internal
 */
final class OpenApiSpecProcessor
{
    /**
     * Keys whose object order must be kept when sorting: the API client codegen
     * output depends on the order of paths, properties and responses.
     */
    private const array ORDER_PRESERVING_KEYS = ['paths', 'properties', 'responses'];

    /** securitySchemes are referenced via `security`, not `$ref`, so they are always kept. */
    private const array ALWAYS_KEPT_COMPONENT_BUCKETS = ['securitySchemes'];

    private const string REF_PATTERN = '~^#/components/([^/]+)/(.+)$~';

    private function __construct()
    {
    }

    /**
     * Deterministic tag order: core tags alphabetically, then "Bundle *" tags
     * alphabetically. The previous comparator returned 0 for two core tags, so
     * their order depended on the scan order.
     */
    public static function compareTagNames(string $a, string $b): int
    {
        $aIsBundle = str_starts_with($a, 'Bundle');
        $bIsBundle = str_starts_with($b, 'Bundle');
        if ($aIsBundle !== $bIsBundle) {
            return $aIsBundle ? 1 : -1;
        }

        return $a <=> $b;
    }

    /**
     * Keeps only the paths under the given path prefixes — segment-aware, so
     * "/bundle/data-hub" does not match "/bundle/data-hub-file-export" — plus
     * every component they transitively reference and every tag they use.
     * Source order is kept everywhere. No scopes means no restriction.
     */
    public static function restrictToScopes(array $spec, array $scopes): array
    {
        if ($scopes === []) {
            return $spec;
        }

        $paths = array_filter(
            $spec['paths'] ?? [],
            static function (string $path) use ($scopes): bool {
                foreach ($scopes as $scope) {
                    $scope = rtrim((string) $scope, '/');
                    if ($path === $scope || str_starts_with($path, $scope . '/')) {
                        return true;
                    }
                }

                return false;
            },
            ARRAY_FILTER_USE_KEY
        );
        $spec['paths'] = $paths;

        if (isset($spec['components']) && is_array($spec['components'])) {
            $spec['components'] = self::filterComponents($spec['components'], $paths);
        }

        if (isset($spec['tags']) && is_array($spec['tags'])) {
            $usedTags = self::collectUsedTags($paths);
            $spec['tags'] = array_values(array_filter(
                $spec['tags'],
                static fn (array $tag): bool => in_array($tag['name'] ?? null, $usedTags, true)
            ));
        }

        return $spec;
    }

    /**
     * With $sortAllKeys, sorts every object's keys canonically while keeping
     * the order of paths (including the methods below them), properties and
     * responses, which the API client codegen depends on. Without it the spec
     * is returned unchanged — swagger-php output is already deterministic.
     */
    public static function sortStable(array $spec, bool $sortAllKeys = false): array
    {
        if (!$sortAllKeys) {
            return $spec;
        }

        return self::sortRecursive($spec, false, false);
    }

    private static function sortRecursive(array $value, bool $preserveOrder, bool $preserveChildOrder): array
    {
        if (!$preserveOrder && !array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as $key => $child) {
            if (is_array($child)) {
                $value[$key] = self::sortRecursive(
                    $child,
                    $preserveChildOrder || in_array($key, self::ORDER_PRESERVING_KEYS, true),
                    // children of `paths` are path items whose method order is
                    // kept too; the preservation goes no deeper than that
                    $key === 'paths'
                );
            }
        }

        return $value;
    }

    private static function filterComponents(array $components, array $paths): array
    {
        $keep = [];
        $stack = [$paths];
        while ($stack !== []) {
            $current = array_pop($stack);
            if (!is_array($current)) {
                continue;
            }
            foreach ($current as $key => $value) {
                if (
                    $key === '$ref'
                    && is_string($value)
                    && preg_match(self::REF_PATTERN, $value, $matches) === 1
                ) {
                    [, $bucket, $name] = $matches;
                    if (!isset($keep[$bucket][$name]) && isset($components[$bucket][$name])) {
                        $keep[$bucket][$name] = true;
                        $stack[] = $components[$bucket][$name];
                    }
                } elseif (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        $result = [];
        foreach ($components as $bucket => $entries) {
            if (in_array($bucket, self::ALWAYS_KEPT_COMPONENT_BUCKETS, true)) {
                $result[$bucket] = $entries;

                continue;
            }
            $kept = array_filter(
                $entries,
                static fn (string $name): bool => isset($keep[$bucket][$name]),
                ARRAY_FILTER_USE_KEY
            );
            if ($kept !== []) {
                $result[$bucket] = $kept;
            }
        }

        return $result;
    }

    private static function collectUsedTags(array $paths): array
    {
        $tags = [];
        foreach ($paths as $pathItem) {
            if (!is_array($pathItem)) {
                continue;
            }
            foreach ($pathItem as $operation) {
                if (is_array($operation) && array_key_exists('tags', $operation) && is_array($operation['tags'])) {
                    foreach ($operation['tags'] as $tag) {
                        if (is_string($tag) && !in_array($tag, $tags, true)) {
                            $tags[] = $tag;
                        }
                    }
                }
            }
        }

        return $tags;
    }
}
