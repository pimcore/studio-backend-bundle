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
    public function isIndexed(?string $keyDefinition): bool
    {
        if ($keyDefinition === null) {
            return true;
        }

        $definition = json_decode($keyDefinition, true);

        if (!is_array($definition)) {
            return true;
        }

        return ($definition[self::INDEXED_MEMBER] ?? true) !== false;
    }
}
