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
    private string $defaultIcon = 'file-question-02';

    public function getIconForAsset(string $assetType, ?string $mimeType): ElementIcon
    {
        if ($assetType === 'document' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation-chart-01',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-x-03',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-02',
                'application/pdf' => 'file-check-02',
                default => $this->defaultIcon
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        if ($assetType === 'text' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/json' => 'file-code-01',
                'application/type9' => 'file-check-02',
                'text/plain' => 'file-02',
                'text/csv' => 'file-x-03',
                default => $this->defaultIcon
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        $value = match ($assetType) {
            'folder' => 'folder',
            'image' => 'image-01',
            'video' => 'video-recorder',
            'audio' => 'volume-max',
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
            ElementTypes::TYPE_OBJECT => 'vector',
            ElementTypes::TYPE_VARIANT => 'variant-icon',
            ElementTypes::TYPE_FOLDER => 'folder',
            default => $this->defaultIcon
        };

        return new ElementIcon(ElementIconTypes::NAME->value, $value);
    }

    public function getIconForTag(): string
    {
        return 'tag-02';
    }
}
