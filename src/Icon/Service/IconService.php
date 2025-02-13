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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

final readonly class IconService implements IconServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(private ClassDefinitionResolverInterface $classDefinitionResolver)
    {
    }

    private const string DEFAULT_ICON = 'unknown';

    public function getIconForElement(ElementSearchResultItemInterface $resultItem): ElementIcon
    {
        return match (true) {
            $resultItem instanceof AssetSearchResultItem => $this->getIconForAsset(
                $resultItem->getType(),
                $resultItem->getMimeType()
            ),
            $resultItem instanceof DataObjectSearchResultItem => $this->getIconForDataObject($resultItem),
            default => new ElementIcon(ElementIconTypes::NAME->value, self::DEFAULT_ICON)
        };
    }

    public function getIconForAsset(string $assetType, ?string $mimeType): ElementIcon
    {
        if ($assetType === 'document' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx-csv',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'txt-docs',
                'application/pdf' => 'pdf',
                default => self::DEFAULT_ICON
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        if ($assetType === 'text' && $mimeType !== null) {
            $value = match ($mimeType) {
                'application/json' => 'json',
                'application/type9' => 'pdf',
                'text/plain' => 'txt-docs',
                'text/csv' => 'xlsx-csv',
                default => self::DEFAULT_ICON
            };

            return new ElementIcon(ElementIconTypes::NAME->value, $value);
        }

        $value = match ($assetType) {
            'folder' => 'folder',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            default => self::DEFAULT_ICON
        };

        return new ElementIcon(ElementIconTypes::NAME->value, $value);
    }

    public function getIconForDataObject(DataObjectSearchResultItem|DataObject $dataObject): ElementIcon
    {
        $classIcon = $this->getClassIcon($dataObject);
        if ($classIcon instanceof ElementIcon) {
            return $classIcon;
        }

        $value = match ($dataObject->getType()) {
            ElementTypes::TYPE_OBJECT => 'data-object',
            ElementTypes::TYPE_VARIANT => 'data-object-variant',
            ElementTypes::TYPE_FOLDER => 'folder',
            default => self::DEFAULT_ICON
        };

        return new ElementIcon(ElementIconTypes::NAME->value, $value);
    }

    public function getIconForTag(): string
    {
        return 'tag';
    }

    public function getIconForClassDefinition(?string $iconPath): ElementIcon
    {
        $type = ElementIconTypes::PATH->value;
        if ($iconPath === null) {
            $type = ElementIconTypes::NAME->value;
            $iconPath = 'class';
        }

        return new ElementIcon($type, $iconPath);
    }

    public function getIconForLayout(?string $iconPath): ?ElementIcon
    {
        if ($iconPath === null) {
            return null;
        }

        return new ElementIcon(ElementIconTypes::PATH->value, $iconPath);
    }

    public function getIconForValue(?array $iconData = null): ElementIcon
    {
        if ($iconData === null || !isset($iconData['type'], $iconData['value'])) {
            // ToDo: Add default icon based on type when available values are available
            return new ElementIcon(ElementIconTypes::NAME->value, self::DEFAULT_ICON);
        }

        return new ElementIcon($iconData['type'], $iconData['value']);
    }

    private function getClassIcon(DataObjectSearchResultItem|DataObject $dataObject): ?ElementIcon
    {
        if ($dataObject instanceof Concrete) {
            $class = $this->getValidClass($this->classDefinitionResolver, $dataObject->getClassId());
            if ($class->getIcon() !== null) {
                return new ElementIcon(ElementIconTypes::PATH->value, $class->getIcon());
            }
        }

        if ($dataObject->getClassDefinitionIcon() !== null) {
            return new ElementIcon(ElementIconTypes::PATH->value, $dataObject->getClassDefinitionIcon());
        }

        return null;
    }
}
