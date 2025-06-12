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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings\LinkSettingsData;
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
final readonly class LinkAdapter implements SetterDataInterface, SettingsNormalizerInterface
{
    private const string PATH_KEY = 'path';

    private const string LINK_TYPE_KEY = 'linktype';

    private const string INTERNAL_TYPE_KEY = 'internalType';

    private const string INTERNAL_KEY = 'internal';

    private const string DIRECT_KEY = 'direct';

    public function __construct(
        private ServiceResolverInterface $elementService,
    ) {
    }

    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof Link) {
            return;
        }

        if (!isset($data[DocumentFieldKeys::SETTINGS_DATA->value])) {
            return;
        }

        $settings = $this->setLinkData($data[DocumentFieldKeys::SETTINGS_DATA->value]);

        unset($settings[self::PATH_KEY]);
        $document->setValues($settings);
    }

    public function normalizeSettings(Document $document): ?SettingsDataInterface
    {
        if (!$document instanceof Link) {
            return null;
        }

        return new LinkSettingsData(
            $document->getInternal(),
            $document->getInternalType(),
            $document->getDirect(),
            $document->getLinktype(),
            $document->getHref(),
            $document->getRawHref(),
        );
    }

    private function setLinkData(array $settingsData): array
    {
        $path = $settingsData[self::PATH_KEY] ?? null;

        if ($path === null || $path === '') {
            return $this->clearLinkData($settingsData);
        }

        $internalTypeSet =$this->isInternalTypeKeySet($settingsData);
        if ($settingsData[self::LINK_TYPE_KEY] === self::INTERNAL_KEY && $internalTypeSet) {
            $target = $this->elementService->getElementByPath($settingsData[self::INTERNAL_TYPE_KEY], $path);
            if ($target instanceof ElementInterface) {
                $settingsData[self::INTERNAL_KEY] = $target->getId();
                $settingsData[self::DIRECT_KEY] = '';

                return $settingsData;
            }
        }

        if ($internalTypeSet) {

            return $this->setDirectLinkData($settingsData, $path);
        }

        return $this->setLinkByPath($settingsData, $path);
    }

    private function setLinkByPath(array $settingsData, string $path): array
    {
        foreach ([ElementTypes::TYPE_OBJECT, ElementTypes::TYPE_DOCUMENT, ElementTypes::TYPE_ASSET] as $type) {
            $target = $this->elementService->getElementByPath($type, $path);
            if ($target instanceof ElementInterface) {
                $settingsData[self::LINK_TYPE_KEY] = self::INTERNAL_KEY;
                $settingsData[self::INTERNAL_TYPE_KEY] = $type;
                $settingsData[self::INTERNAL_KEY] = $target->getId();
                $settingsData[self::DIRECT_KEY] = '';

                return $settingsData;
            }
        }

        return $this->setDirectLinkData($settingsData, $path);
    }

    private function setDirectLinkData(array $settingsData, string $path): array
    {
        $settingsData[self::LINK_TYPE_KEY] = self::DIRECT_KEY;
        $settingsData[self::INTERNAL_TYPE_KEY] = null;
        $settingsData[self::DIRECT_KEY] = $path;

        return $settingsData;
    }

    private function isInternalTypeKeySet(array $settingsData): bool
    {
        return isset($settingsData[self::INTERNAL_TYPE_KEY]) &&
        $settingsData[self::INTERNAL_TYPE_KEY] !== null &&
        $settingsData[self::INTERNAL_TYPE_KEY] !== '';
    }

    private function clearLinkData(array $settingsData): array
    {
        $settingsData[self::LINK_TYPE_KEY] = self::INTERNAL_KEY;
        $settingsData[self::DIRECT_KEY] = '';
        $settingsData[self::INTERNAL_TYPE_KEY] = null;
        $settingsData[self::INTERNAL_KEY] = null;

        return $settingsData;
    }
}
