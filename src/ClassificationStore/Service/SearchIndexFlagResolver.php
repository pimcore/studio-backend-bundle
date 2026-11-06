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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use function array_key_exists;
use function is_array;
use function json_decode;

/**
 * The one place the reserved `indexed` member is spelled and read, so that the write surfaces, the
 * configuration apply and the search index itself cannot drift apart on what it means.
 *
 * @internal
 */
final readonly class SearchIndexFlagResolver implements SearchIndexFlagResolverInterface
{
    public function isIndexed(?array $metadata): bool
    {
        if ($metadata === null) {
            return true;
        }

        return ($metadata[self::INDEXED_MEMBER] ?? true) !== false;
    }

    public function carriesFlag(?array $metadata): bool
    {
        return $metadata !== null && array_key_exists(self::INDEXED_MEMBER, $metadata);
    }

    public function withKeyDefault(?array $metadata, string $keyDefinition): ?array
    {
        $definition = json_decode($keyDefinition, true);

        if (!is_array($definition) || !array_key_exists(self::INDEXED_MEMBER, $definition)) {
            return null;
        }

        $metadata ??= [];
        $metadata[self::INDEXED_MEMBER] = $definition[self::INDEXED_MEMBER];

        return $metadata;
    }
}
