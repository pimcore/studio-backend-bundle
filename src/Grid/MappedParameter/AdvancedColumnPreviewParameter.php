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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter;


/**
 * @internal
 */
final readonly class AdvancedColumnPreviewParameter
{
    public function __construct(
        private int $objectId,
        private array $column,
    ) {
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getColumn(): array
    {
        return $this->column;
    }
}
