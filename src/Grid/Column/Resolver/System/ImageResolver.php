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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementInterface;

/**
 * @internal
 */
final class ImageResolver implements ColumnResolverInterface
{
    use ColumnDataTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Asset) {
            return $this->getColumnData(
                $column,
                null
            );
        }

        return $this->getColumnData(
            $column,
            $element->getFullPath()
        );
    }

    public function getType(): string
    {
        return 'system.image';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
