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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\TranslationParentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\TranslationsEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation\TranslationLink;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation\TranslationLinks;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation\TranslationParent;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Service as DocumentService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class TranslationService implements TranslationServiceInterface
{
    private DocumentService $coreDocumentService;

    public function __construct(
        private readonly DocumentServiceInterface $documentService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SecurityServiceInterface $securityService,
    ) {
        $this->coreDocumentService = new DocumentService();
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationParent(int $id, string $language): TranslationParent
    {
        $user = $this->securityService->getCurrentUser();
        $document = $this->documentService->getDocumentElement($user, $id);
        $parent = $document->getId() === 1 ? $document : $document->getParent();

        if (!$parent instanceof Document) {
            throw new NotFoundException('parent document for document', $document->getId());
        }

        try {
            $translations = $this->coreDocumentService->getTranslations($document);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        if (!isset($translations[$language])) {
            throw new NotFoundException('translation', $language, 'language');
        }

        $parentElement = $this->documentService->getDocumentElement($user, $translations[$language]);
        $parentTranslation = new TranslationParent($parentElement->getId(), $parentElement->getRealFullPath());
        $this->eventDispatcher->dispatch(
            new TranslationParentEvent($parentTranslation),
            TranslationParentEvent::EVENT_NAME
        );

        return $parentTranslation;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentTranslations(int $id): TranslationLinks
    {
        $user = $this->securityService->getCurrentUser();
        $document = $this->documentService->getDocumentElement($user, $id);
        $this->validateDocumentLanguage($document, 'source');

        $translationLinks = [];
        $language = $document->getProperty('language');

        try {
            $links = $this->coreDocumentService->getTranslations($document);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        foreach ($links as $language => $documentId) {
            $translationLinks[] = new TranslationLink($language, $documentId);
        }

        $translations = new TranslationLinks($language, $translationLinks);
        $this->eventDispatcher->dispatch(
            new TranslationsEvent($translations),
            TranslationsEvent::EVENT_NAME
        );

        return $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function addDocumentTranslation(int $sourceId, int $targetId): void
    {
        $user = $this->securityService->getCurrentUser();
        $source = $this->documentService->getDocumentElement($user, $sourceId);
        $target = $this->documentService->getDocumentElement($user, $targetId);
        $this->validateDocumentLanguage($source, 'source');
        $this->validateDocumentLanguage($target, 'target');

        try {
            $targetTranslationSource = $this->coreDocumentService->getTranslationSourceId($target);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        if ($targetTranslationSource !== $target->getId()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Target Document is already linked to Source Document(ID: %s).' .
                    'Please unlink existing relation first.',
                    $targetTranslationSource,
                )
            );
        }

        $this->coreDocumentService->addTranslation($source, $target);
    }


    /**
     * {@inheritdoc}
     */
    public function removeDocumentTranslation(int $sourceId, int $targetId): void
    {
        $user = $this->securityService->getCurrentUser();
        $source = $this->documentService->getDocumentElement($user, $sourceId);
        $target = $this->documentService->getDocumentElement($user, $targetId);

        try {
             $this->coreDocumentService->removeTranslationLink($source, $target);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateDocumentLanguage(Document $document, string $type): void
    {
        if (empty($document->getProperty('language'))) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s Document(ID: %s) has no Language property set.',
                    ucfirst($type),
                    $document->getId(),
                )
            );
        }
    }

}
