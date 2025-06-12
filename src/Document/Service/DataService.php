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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Data\EditableDataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\SettingsNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippetDraftData;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;

/**
 * @internal
 */
final readonly class DataService implements DataServiceInterface
{
    public function __construct(
        private DocumentTypeServiceInterface $documentTypeService,
        private WorkflowDetailsServiceInterface $workflowDetailsService,
    ) {
    }

    public function setDocumentDetailData(
        DocumentDetail $document,
        DocumentModel $element,
        ?Version $documentVersion = null,
    ): void {
        $document->setHasWorkflowAvailable($this->workflowDetailsService->hasElementWorkflows($element));

        $adapter = $this->documentTypeService->tryTypeAdapter($document->getType());

        if ($adapter instanceof EditableDataNormalizerInterface) {
            $document->setEditableData($adapter->normalizeEditableData($element));
        }

        if ($adapter instanceof SettingsNormalizerInterface) {
            $document->setSettingsData($adapter->normalizeSettings($element));
        }

        if (method_exists($element, 'getMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(
                $element->getMissingRequiredEditable() ?? false
            );
        }

        if (method_exists($document, 'setDraftData')) {
            $document->setDraftData($this->getDraftData($element, $documentVersion));
        }
    }

    public function updateDocumentData(
        DocumentModel $document,
        array $data,
        UserInterface $user
    ): void {
        $adapter = $this->documentTypeService->tryTypeAdapter($document->getType());
        if (!$adapter instanceof SetterDataInterface) {
            return;
        }
        $adapter->setData($document, $data, $user);
    }

    private function getDraftData(
        DocumentModel $document,
        ?Version $version = null
    ): ?PageSnippetDraftData {
        if (!$version || $document->getModificationDate() < $version->getDate()) {
            return null;
        }

        return new PageSnippetDraftData(
            $version->getId(),
            $version->getDate(),
            $version->isAutoSave()
        );
    }
}
