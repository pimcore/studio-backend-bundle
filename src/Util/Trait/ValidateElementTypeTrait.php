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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function in_array;

/**
 * @internal
 */
trait ValidateElementTypeTrait
{
    private function getElementType(?string $elementType): ?string
    {
        if ($elementType === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }

        return $elementType;
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function validate(?string $elementType): void
    {
        if ($elementType === null) {
            return;
        }

        if (!in_array($elementType, ElementTypes::ALLOWED_TYPES, true)) {
            throw new InvalidElementTypeException($elementType);
        }
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function validateStudioTypes(string $elementType): void
    {
        if (!in_array($elementType, ElementTypes::ALLOWED_STUDIO_TYPES, true)) {
            throw new InvalidElementTypeException($elementType);
        }
    }

    private function getCoreElementType(string $elementType): string
    {

        return $this->getElementType($elementType);
    }
}
