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

use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\HardLinkData;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Settings\HardlinkSettingsData;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class HardlinkAdapter implements SetterDataInterface, SettingsNormalizerInterface
{
    private const string SOURCE_PATH_KEY = 'sourcePath';

    public function __construct(
        private DocumentResolverInterface $documentResolver
    ) {
    }

    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof Hardlink) {
            return;
        }

        if (!isset($data[DocumentFieldKeys::SETTINGS_DATA->value])) {
            return;
        }

        $settingsData = $data[DocumentFieldKeys::SETTINGS_DATA->value];
        $sourceId = null;
        if (isset($settingsData[self::SOURCE_PATH_KEY])) {
            $source = $this->documentResolver->getByPath($settingsData[self::SOURCE_PATH_KEY]);
            $sourceId = $source?->getId();
        }

        $document->setSourceId($sourceId);
        $document->setValues($settingsData);
    }

    public function normalizeSettings(Document $document): ?SettingsDataInterface
    {
        if (!$document instanceof Hardlink) {
            return null;
        }

        return new HardlinkSettingsData(
            sourceId: $document->getSourceId(),
            propertiesFromSource: $document->getPropertiesFromSource(),
            childrenFromSource: $document->getChildrenFromSource(),
            sourcePath: $document->getSourceDocument()?->getRealFullPath()
        );
    }
}
