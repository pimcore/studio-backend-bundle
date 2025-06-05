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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class LinkAdapter implements SetterDataInterface, DataNormalizerInterface
{
    private const string PATH_KEY = 'path';

    private const string LINK_TYPE_KEY = 'linktype';

    private const string INTERNAL_TYPE_KEY = 'internalType';

    private const string INTERNAL_KEY = 'internal';

    private const string DIRECT_KEY = 'direct';

    private const string RAW_HREF_KEY = 'rawHref';


    public function __construct(
        private ServiceResolverInterface $elementService,
    )
    {
    }

    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof Link) {
            return;
        }

        if (!isset($data[DocumentFieldKeys::EDITABLE_DATA->value])) {
            return;
        }

        $editableData = $this->setLinkData($data[DocumentFieldKeys::EDITABLE_DATA->value]);

        unset($editableData[self::PATH_KEY]);
        $document->setValues($editableData);
    }

    public function normalize(Document $document): array
    {
        if (!$document instanceof Link) {
            return [];
        }

        return [self::RAW_HREF_KEY => $document->getRawHref()];
    }

    private function setLinkData(array $editableData): array
    {
        $path = $editableData[self::PATH_KEY] ?? null;

        if ($path === null || $path === '') {
            return $this->clearLinkData($editableData);
        }

        $internalTypeSet =$this->isInternalTypeKeySet($editableData);
        if ($editableData[self::LINK_TYPE_KEY] === self::INTERNAL_KEY && $internalTypeSet) {
            $target = $this->elementService->getElementByPath($editableData[self::INTERNAL_TYPE_KEY], $path);
            if ($target instanceof ElementInterface) {
                $editableData[self::INTERNAL_KEY] = $target->getId();
                $editableData[self::DIRECT_KEY] = '';

                return $editableData;
            }
        }

        if ($internalTypeSet) {

            return $this->setDirectLinkData($editableData, $path);
        }

        return $this->setLinkByPath($editableData, $path);
    }

    private function setLinkByPath(array $editableData, string $path): array
    {
        foreach ([ElementTypes::TYPE_OBJECT, ElementTypes::TYPE_DOCUMENT, ElementTypes::TYPE_ASSET] as $type) {
            $target = $this->elementService->getElementByPath($type, $path);
            if ($target instanceof ElementInterface) {
                $editableData[self::LINK_TYPE_KEY] = self::INTERNAL_KEY;
                $editableData[self::INTERNAL_TYPE_KEY] = $type;
                $editableData[self::INTERNAL_KEY] = $target->getId();
                $editableData[self::DIRECT_KEY] = '';

                return $editableData;
            }
        }

        return $this->setDirectLinkData($editableData, $path);
    }

    private function setDirectLinkData(array $editableData, string $path): array
    {
        $editableData[self::LINK_TYPE_KEY] = self::DIRECT_KEY;
        $editableData[self::INTERNAL_TYPE_KEY] = null;
        $editableData[self::DIRECT_KEY] = $path;

        return $editableData;
    }

    private function isInternalTypeKeySet(array $editableData): bool
    {
        return isset($editableData[self::INTERNAL_TYPE_KEY]) &&
        $editableData[self::INTERNAL_TYPE_KEY] !== null &&
        $editableData[self::INTERNAL_TYPE_KEY] !== '';
    }

    private function clearLinkData(array $editableData): array
    {
        $editableData[self::LINK_TYPE_KEY] = self::INTERNAL_KEY;
        $editableData[self::DIRECT_KEY] = '';
        $editableData[self::INTERNAL_TYPE_KEY] = null;
        $editableData[self::INTERNAL_KEY] = null;

        return $editableData;
    }
}
