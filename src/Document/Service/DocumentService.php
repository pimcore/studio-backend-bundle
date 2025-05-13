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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocumentService implements DocumentServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    public function __construct(
        private DocumentSearchServiceInterface $documentSearchService,
        private DataServiceInterface $dataService,
        private EventDispatcherInterface $eventDispatcher,
        private FilterServiceProviderInterface $filterServiceProvider,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowDetailsServiceInterface $workflowDetailsService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getDocuments(ElementParameters $parameters): Collection
    {
        /** @var SearchIndexFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(SearchIndexFilterInterface::SERVICE_TYPE);

        /** @var DocumentQueryInterface $documentQuery */
        $documentQuery = $filterService->applyFilters(
            $parameters,
            ElementTypes::TYPE_DOCUMENT
        );

        $documentQuery->orderByPath('asc');
        $documentQuery->setUser($this->securityService->getCurrentUser());

        $result = $this->documentSearchService->searchDocuments($documentQuery);

        $items = $result->getItems();

        foreach ($items as $item) {
            $this->dispatchDocumentEvent($item);
        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * {@inheritDoc}
     */
    public function getDocument(int $id, bool $getDetailData = true): Document
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
     * {@inheritDoc}
     */
    public function getDocumentForUser(int $id, UserInterface $user): Document
    {
        $document = $this->documentSearchService->getDocumentById($id, $user);

        $this->dispatchDocumentEvent($document);

        return $document;
    }

    /**
     * {@inheritDoc}
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
     * @throws InvalidElementTypeException|NotFoundException
     */
    private function getDocumentDetailData(Document $document): void
    {
        $element = $this->getElement($this->serviceResolver, ElementTypes::TYPE_DOCUMENT, $document->getId());
        $version = $this->getLatestVersionForUser($element, $this->securityService->getCurrentUser());
        $element = $this->getVersionData($element, $version);

        if (!$element instanceof DocumentModel) {
            return;
        }

        $this->dataService->setDocumentDetailData($document, $element, $version);
    }

    private function dispatchDocumentEvent(mixed $document): void
    {
        $this->eventDispatcher->dispatch(
            new DocumentEvent($document),
            DocumentEvent::EVENT_NAME
        );
    }
}
