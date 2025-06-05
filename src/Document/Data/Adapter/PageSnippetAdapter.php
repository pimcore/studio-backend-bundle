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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\PageSnippetData;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Snippet;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::DOCUMENT_TYPE_ADAPTER_TAG->value)]
final readonly class PageSnippetAdapter implements SetterDataInterface, DataNormalizerInterface
{
    private const string PRETTY_URL_KEY = 'prettyUrl';

    public function __construct(
        private SecurityServiceInterface $securityService,
        private StaticPageGenerator $staticPageGenerator
    ) {
    }

    public function setData(Document $document, array $data, UserInterface $user): void
    {
        if (!$document instanceof PageSnippet) {
            return;
        }

        if ($document instanceof Page || $document instanceof Snippet) {
            $this->setMissingEditable($document, $data);
        }

        $this->setSettings($document, $data, $user);
        $this->setEditableData($document, $data);
    }

    public function normalize(Document $document): array
    {
        if (!$document instanceof PageSnippet) {
            return [];
        }

        $staticLastGenerated = null;
        if ($document->getStaticGeneratorEnabled()) {
            $staticLastGenerated = $this->staticPageGenerator->getLastModified($document);
        }

        try {
            $url = $document->getUrl();
        } catch (Exception) {
            $url = null;
        }

        $data = new PageSnippetData(
            url: $url,
            staticLastGenerated: $staticLastGenerated,
            contentMainDocumentPath: $document->getContentMainDocument()?->getRealFullPath()
        );

        return $data->toArray();
    }

    private function setMissingEditable(Page|Snippet $document, array $data): void
    {
        $document->setMissingRequiredEditable(
            $data[DocumentFieldKeys::MISSING_REQUIRED_EDITABLE->value] ?? false
        );
    }

    private function setSettings(PageSnippet $document, array $data, UserInterface $user): void
    {
        if (!isset($data[DocumentFieldKeys::SETTINGS_DATA->value])) {
            return;
        }

        $settings = $data[DocumentFieldKeys::SETTINGS_DATA->value];
        if ($document instanceof Page && ($settings['published'] ?? false)) {
            $document->setMissingRequiredEditable(null);
        }

        $this->securityService->hasElementPermission($document, $user, ElementPermissions::SETTINGS_PERMISSION);
        $prettyUrl = $settings[self::PRETTY_URL_KEY] ?? null;
        if ($prettyUrl === null) {
            return;
        }

        $settings[self::PRETTY_URL_KEY] = htmlspecialchars($prettyUrl);
        $document->setValues($settings);
    }

    private function setEditableData(PageSnippet $document, array $data): void
    {
        if (!isset($data[DocumentFieldKeys::EDITABLE_DATA->value])) {
            return;
        }

        $document->setEditables(null);
        $editableData = $data[DocumentFieldKeys::EDITABLE_DATA->value];
        foreach ($editableData as $name => $value) {
            $document->setRawEditable($name, $value['type'], $value['data'] ?? null);
        }
    }
}
