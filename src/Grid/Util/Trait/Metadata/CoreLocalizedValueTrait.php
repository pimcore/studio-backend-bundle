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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;

trait CoreLocalizedValueTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function getCoreLocalizedValue(Column $column, ElementInterface $element): mixed
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('Element must be an instance of Asset');
        }

        if ($column->getLocale()) {
            return $element->getMetadata($column->getKey(), $column->getLocale(), true);
        }

        return $element->getMetadata($column->getKey());
    }
}
