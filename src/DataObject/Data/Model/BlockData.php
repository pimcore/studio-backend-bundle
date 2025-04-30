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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

/**
 * @internal
 */
final readonly class BlockData
{
    public function __construct(
        private array $blockData,
        private array $contextData
    ) {
    }

    public function getBlockData(): array
    {
        return $this->blockData;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
