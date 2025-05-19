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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function in_array;

/**
 * @internal
 */
trait ExportConfigValidationTrait
{
    private function validateConfig(): void
    {

        if (empty($this->getColumns())) {
            throw new InvalidArgumentException('No columns provided');
        }

        if (empty($this->getConfig())) {
            throw new InvalidArgumentException('No settings provided');
        }

        if (!in_array($this->getElementType(), ElementTypes::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Invalid type provided');
        }
    }

    private function getValidElementType(string $elementType): string
    {
        if ($elementType === ElementTypes::TYPE_DATA_OBJECT) {
            return ElementTypes::TYPE_OBJECT;
        }

        return $elementType;
    }
}
