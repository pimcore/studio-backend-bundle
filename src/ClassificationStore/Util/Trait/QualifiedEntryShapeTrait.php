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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializerInterface;
use function array_key_exists;
use function is_array;

/**
 * What one entry of a qualified slot looks like, asked the same way on both sides of the boundary:
 * by {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser},
 * which reads what a write supplied, and by
 * {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer},
 * which reads what the row holds. The two read a *list* differently - leniently at the request
 * boundary, and out of storage only where the key's declaration says the slot holds one and then
 * strictly, down to the members an entry may carry
 * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer::isStoredEntry()})
 * - and each owns its own reading, but the entry those readings are built out of is one thing and
 * is declared once, here.
 *
 * @internal
 */
trait QualifiedEntryShapeTrait
{
    /**
     * One entry object: an array carrying the value member. The qualifiers are both optional - an
     * absent variant is the default one and an absent position is the first - so carrying the value
     * is the whole of what makes an entry.
     */
    private function isEntry(mixed $entry): bool
    {
        return is_array($entry) && array_key_exists(QualifiedValueSerializerInterface::VALUE_MEMBER, $entry);
    }
}
