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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\HardLinkData;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
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
final readonly class HardlinkAdapter implements SetterDataInterface, DataNormalizerInterface
{
    private const string SOURCE_PATH_KEY = 'sourcePath';

    public function __construct(
        private DocumentResolverInterface $documentResolver
    )
    {
    }

    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof Hardlink) {
            return;
        }

        if (!isset($data[DocumentFieldKeys::EDITABLE_DATA->value])) {
            return;
        }

        $editableData = $data[DocumentFieldKeys::EDITABLE_DATA->value];
        $sourceId = null;
        if (isset($editableData[self::SOURCE_PATH_KEY])) {
            $source = $this->documentResolver->getByPath($editableData[self::SOURCE_PATH_KEY]);
            $sourceId = $source?->getId();
        }

        $document->setSourceId($sourceId);
        $document->setValues($editableData);
    }

    public function normalize(Document $document): array
    {
        if (!$document instanceof Hardlink) {
            return [];
        }

        $data = new HardLinkData(
            sourceId: $document->getSourceId(),
            childrenFromSource: $document->getChildrenFromSource(),
            propertiesFromSource: $document->getPropertiesFromSource(),
            sourcePath: $document->getSourceDocument()?->getRealFullPath()
        );

        return $data->toArray();
    }
}
