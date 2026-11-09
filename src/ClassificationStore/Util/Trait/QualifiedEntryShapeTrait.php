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
use function array_is_list;
use function array_key_exists;
use function is_array;

/**
 * What one entry of a qualified slot looks like, asked the same way on both sides of the boundary:
 * by {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser},
 * which reads what a write supplied, and by
 * {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer},
 * which reads what the row holds. The two read a list differently - leniently at the request
 * boundary, strictly out of storage - and they are only allowed to differ in that, so the two
 * predicates they build those readings out of are declared once, here.
 *
 * @internal
 */
trait QualifiedEntryShapeTrait
{
    /**
     * Whether a value could be a list of entries at all: a non-empty list. The empty array is no
     * list of entries, so a slot holding one keeps travelling whole.
     */
    private function isCandidateList(mixed $payload): bool
    {
        return is_array($payload) && $payload !== [] && array_is_list($payload);
    }

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
