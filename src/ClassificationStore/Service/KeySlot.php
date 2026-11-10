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

use Pimcore\Model\DataObject\Classificationstore;

/**
 * The address of one slot: a key, in its group, in one language, on one container - and the store
 * the key's declaration is read from.
 *
 * The write path carries the address as one value rather than as four arguments, because every step
 * of it addresses the same slot: the stored entries are read from it, the capabilities of its store
 * decide what may be written to it, and core's setter is handed it last.
 *
 * {@see QualifiedValue} is one value INSIDE such a slot, addressed by the qualifiers it carries.
 *
 * @internal
 */
final readonly class KeySlot
{
    public function __construct(
        private Classificationstore $container,
        private int $storeId,
        private int $groupId,
        private int $keyId,
        private string $language,
    ) {
    }

    public function getContainer(): Classificationstore
    {
        return $this->container;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}
