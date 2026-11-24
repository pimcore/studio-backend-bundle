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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

/**
 * @internal
 */
readonly class ClassificationStoreConfig
{
    public function __construct(private int $group, private int $key, private ?string $variant = null)
    {
    }

    public function getGroupId(): int
    {
        return $this->group;
    }

    public function getKeyId(): int
    {
        return $this->key;
    }

    /**
     * The variant the column addresses, null for the default slot.
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }
}
