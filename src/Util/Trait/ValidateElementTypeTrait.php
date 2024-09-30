<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
}
