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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\CoreLocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Exception;

/**
 * @internal
 */
final class AssetResolver implements
    ColumnResolverInterface,
    StudioElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;
    use CoreLocalizedValueTrait;

    public function resolveForExport(Column $column, ElementInterface $element): ColumnData
    {
        $asset = $this->getCoreLocalizedValue($column, $element);

        if (!$asset instanceof Asset) {
            return $this->getColumnData($column, null, $this->getType());
        }

        return $this->getColumnData($column, $asset->getFullPath(), $this->getType());
    }


    public function __construct(
        private readonly AssetServiceInterface $assetService
    ) {
    }

    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        $asset = $this->getLocalizedValue($column, $element);

        if (!isset($asset['asset'])) {
            return $this->getColumnData($column, null, $this->getType());
        }

        try {
            $relatedAsset = $this->assetService->getAsset(
                reset($asset['asset']),
                false
            );
        } catch (NotFoundException) {
            return $this->getColumnData($column, null, $this->getType());
        }

        return $this->getColumnData(
            $column,
            [
                'id' => $relatedAsset->getId(),
                'subtype' => $relatedAsset->getType(),
                'type' => 'asset',
                'fullPath' => $relatedAsset->getFullPath(),
            ],
            $this->getType()
        );
    }

    public function getType(): string
    {
        return ColumnType::METADATA_ASSET->value;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
