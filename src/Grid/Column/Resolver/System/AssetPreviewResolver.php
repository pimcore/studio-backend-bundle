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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\ThumbnailPathInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final class AssetPreviewResolver implements ColumnResolverInterface, StudioElementColumnResolverInterface
{
    use ColumnDataTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        if (!$element instanceof Asset) {
            throw new InvalidArgumentException('Element must be an instance of ' . Asset::class);
        }

        $thumbnail = null;
        if ($element instanceof ThumbnailPathInterface) {
            $thumbnail = $element->getImageThumbnailPath();
        }

        return $this->getColumnData(
            $column,
            [
                'thumbnail' => $thumbnail,
                'icon' => $element->getIcon(),
            ],
            $this->getType()
        );
    }

    public function getType(): string
    {
        return 'system.preview';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
