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

namespace Pimcore\Bundle\StudioBackendBundle\Icon\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Model\DataObject;

interface IconServiceInterface
{
    public function getIconForElement(ElementSearchResultItemInterface $resultItem): ElementIcon;

    public function getIconForAsset(string $assetType, string $mimeType): ElementIcon;

    public function getIconForDataObject(DataObjectSearchResultItem|DataObject $dataObject): ElementIcon;

    public function getIconForTag(): string;

    public function getIconForClassDefinition(?string $iconPath): ElementIcon;

    public function getIconForLayout(?string $iconPath): ?ElementIcon;

    public function getIconForValue(?array $iconData = null): ElementIcon;
}
