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
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

final class IconService implements IconServiceInterface
{
    private string $defaultIcon = 'unknown';

    public function getIconForAsset(string $assetType, ?string $mimeType): ElementIcon
    {
        if ($assetType === 'document' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx-csv',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'txt-docs',
                'application/pdf' => 'pdf',
                default => $this->defaultIcon
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        if ($assetType === 'text' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/json' => 'json',
                'application/type9' => 'pdf',
                'text/plain' => 'txt-docs',
                'text/csv' => 'xlsx-csv',
                default => $this->defaultIcon
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        $value = match ($assetType) {
            'folder' => 'folder',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            default => $this->defaultIcon
        };

        return new ElementIcon(ElementIconTypes::NAME->value, $value);
    }

    public function getIconForDataObject(DataObjectSearchResultItem $dataObject): ElementIcon
    {
        if ($dataObject->getClassDefinitionIcon() !== null) {
            return new ElementIcon(ElementIconTypes::PATH->value, $dataObject->getClassDefinitionIcon());
        }

        $value = match ($dataObject->getType()) {
            ElementTypes::TYPE_OBJECT => 'data-object',
            ElementTypes::TYPE_VARIANT => 'data-object-variant',
            ElementTypes::TYPE_FOLDER => 'folder',
            default => $this->defaultIcon
        };

        return new ElementIcon(ElementIconTypes::NAME->value, $value);
    }

    public function getIconForTag(): string
    {
        return 'tag';
    }

    public function getIconForLayout(?string $iconPath): ?ElementIcon
    {
        if ($iconPath === null) {
            return null;
        }

        return new ElementIcon(ElementIconTypes::PATH->value, $iconPath);
    }
}
