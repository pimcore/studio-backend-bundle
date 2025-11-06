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

namespace Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsageBaseItem;

/**
 * @internal
 */
final readonly class ReplaceAssignmentParameter
{
    public function __construct(
        private string $targetType,
        private int $targetId,
        /** @var ElementUsageBaseItem[] */
        private array $elements
    ) {

    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    /** @return ElementUsageBaseItem[] */
    public function getElements(): array
    {
        return $this->elements;
    }
}
