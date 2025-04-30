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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final readonly class WorkflowDetailsParameters
{
    public function __construct(
        private int $elementId,
        private string $elementType
    ) {
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        if ($this->elementType === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }

        return $this->elementType;
    }
}
