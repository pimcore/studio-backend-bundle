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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils;

/**
 * @internal
 */
final readonly class ClassificationStoreFilterValue
{
    public function __construct(
        private readonly int $groupId,
        private readonly int $keyId,
        private readonly string|array $value,
        private readonly ?string $variant = null
    ) {

    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    /**
     * The variant of a `multiValue` key the filter addresses, null for the key's own (default) slot.
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * The name of the index field the filter searches: the key's own, or its `<key>_<variant>` sibling.
     */
    public function getIndexFieldName(string $keyName): string
    {
        return $this->variant === null ? $keyName : $keyName . '_' . $this->variant;
    }

    public function getValue(): string|array
    {
        return $this->value;
    }
}
