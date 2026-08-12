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

use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\DraftElementResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    public function __construct(
        private CreateServiceInterface $createService,
        private DataServiceInterface $dataService,
        private DocumentQueryProviderInterface $documentQueryProvider,
        private DocumentSearchServiceInterface $documentSearchService,
        private DocumentServiceResolverInterface $documentServiceResolver,
        private EventDispatcherInterface $eventDispatcher,
        private FilterServiceProviderInterface $filterServiceProvider,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private DraftElementResolverInterface $draftElementResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function addDocument(int $parentId, DocumentAddParameters $parameters): int
    {
        $user = $this->securityService->getCurrentUser();
        $parent = $this->getValidParent($user, $parentId);
        $fullPath = $this->getElementFullPath($parent->getFullPath(), $parameters->getKey());
        if ($this->documentServiceResolver->pathExists($fullPath)) {
            throw new ElementExistsException(sprintf('Document with full path [%s] already exists', $fullPath));
        }

        return $this->createService->createDocument($parent, $parameters, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocuments(ElementParameters $parameters): Collection
    {
        /** @var SearchIndexFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(SearchIndexFilterInterface::SERVICE_TYPE);

        $documentQuery = $this->documentQueryProvider->createDocumentQuery();
        $documentQuery = $filterService->applyFilters(
            $documentQuery,
            $parameters,
            ElementTypes::TYPE_DOCUMENT
        );

        $documentQuery->setUser($this->securityService->getCurrentUser());
        $this->setTreeSorting($documentQuery, $parameters->getPathIncludeParent());
        $result = $this->documentSearchService->searchDocuments($documentQuery);
        $items = $result->getItems();

        foreach ($items as $item) {
            $this->dispatchDocumentEvent($item);
        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocument(int $id, bool $getDetailData = true): DocumentDetail
    {
        $user = $this->securityService->getCurrentUser();
        $document = $this->documentSearchService->getDocumentById($id, $user);

        if ($getDetailData) {
            $this->getDocumentDetailData($document);
        }
        $this->dispatchDocumentEvent($document);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentForUser(int $id, UserInterface $user): DocumentDetail
    {
        $document = $this->documentSearchService->getDocumentById($id, $user);

        $this->dispatchDocumentEvent($document);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentElement(
        UserInterface $user,
        int $documentId,
    ): DocumentModel {
        $document = $this->getElement($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $documentId);
        $this->securityService->hasElementPermission($document, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$document instanceof DocumentModel) {
            throw new InvalidElementTypeException($document->getType());
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentElementByPath(UserInterface $user, string $path): DocumentModel
    {
        $document = $this->getElementByPath($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $path);
        $this->securityService->hasElementPermission($document, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$document instanceof DocumentModel) {
            throw new InvalidElementTypeException($document->getType());
        }

        return $document;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getValidParent(UserInterface $user, int $parentId): DocumentModel
    {
        $parent = $this->getDocumentElement($user, $parentId);
        $this->securityService->hasElementPermission($parent, $user, ElementPermissions::CREATE_PERMISSION);

        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    private function getDocumentDetailData(DocumentDetail $document): void
    {
        $element = $this->getElement($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $document->getId());
        $user = $this->securityService->getCurrentUser();
        // version = draft identity for `draftData`; element state comes from the resolver
        $version = $this->getLatestVersionForUser($element, $user);
        $element = $this->draftElementResolver->resolve($element, $user);

        if (!$element instanceof DocumentModel) {
            return;
        }

        $this->dataService->setDocumentDetailData($document, $element, $version);
    }

    private function setTreeSorting(DocumentQueryInterface $documentQuery, bool $includeParent): void
    {
        if ($includeParent) {
            $documentQuery->orderByPath('asc');

            return;
        }

        $documentQuery->orderByIndex();
    }

    private function dispatchDocumentEvent(Document $document): void
    {
        $this->eventDispatcher->dispatch(
            new DocumentEvent($document),
            DocumentEvent::EVENT_NAME
        );
    }
}
