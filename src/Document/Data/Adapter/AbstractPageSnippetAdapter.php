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
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\SettingsDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface;
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
abstract readonly class AbstractPageSnippetAdapter implements SetterDataInterface, SettingsNormalizerInterface
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

    abstract public function normalizeSettings(Document $document): ?SettingsDataInterface;

    protected function getBasePageSnippetData(PageSnippet $document): array
    {
        try {
            $url = $document->getUrl();
        } catch (Exception) {
            $url = null;
        }

        $staticLastGenerated = null;
        if ($document->getStaticGeneratorEnabled()) {
            $staticLastGenerated = $this->staticPageGenerator->getLastModified($document);
        }
        
        return [
            $document->getController(),
            $document->getTemplate(),
            $document->getContentMainDocumentId(),
            $document->getContentMainDocument()?->getRealFullPath(),
            $document->supportsContentMain(),
            $document->getStaticGeneratorEnabled() ?? false,
            $document->getStaticGeneratorLifetime(),
            $staticLastGenerated,
            $url
        ];
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

        $this->securityService->hasElementPermission($document, $user, ElementPermissions::SETTINGS_PERMISSION);
        $settings = $data[DocumentFieldKeys::SETTINGS_DATA->value];
        if ($document instanceof Page && ($settings['published'] ?? false)) {
            $document->setMissingRequiredEditable(null);
        }

        $prettyUrl = $settings[self::PRETTY_URL_KEY] ?? null;
        if ($prettyUrl !== null) {
            $settings[self::PRETTY_URL_KEY] = htmlspecialchars($prettyUrl);
        }

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
