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
