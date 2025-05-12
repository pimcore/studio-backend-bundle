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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter;

use Pimcore\Bundle\GenericDataIndexBundle\Exception\DocumentSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\DocumentSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Document\DocumentSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DocumentHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator\DocumentHydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final readonly class DocumentSearchAdapter implements DocumentSearchAdapterInterface
{
    public function __construct(
        private SearchResultIdListServiceInterface $searchResultIdListService,
        private DocumentSearchServiceInterface $searchService,
        private DocumentHydratorServiceInterface $hydratorService
    ) {
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDocumentById(int $id, ?UserInterface $user = null): Document
    {
        try {
            /** @var User $user
             *  Because of byId method in the GDI
             * */
            $document = $this->searchService->byId($id, $user);
        } catch (DocumentSearchException) {
            throw new SearchException(sprintf('Document with id %s', $id));
        }

        if (!$document) {
            throw new NotFoundException('Document', $id);
        }

        return $this->hydratorService->hydrateDocuments($document);
    }

    public function fetchDocumentIds(QueryInterface $documentQuery): array
    {
        try {
            $search = $documentQuery->getSearch();
            $search->addModifier(new OrderByFullPath());

            return $this->searchResultIdListService->getAllIds($search);
        } catch (DocumentSearchException) {
            throw new SearchException('documents');
        }
    }

    /**
     * @throws InvalidSearchException|SearchException
     */
    public function findInTree(QueryInterface $dataObjectQuery): ?ElementSearchResultItemInterface
    {
        try {
            $searchResult = $this->searchService->search(
                $this->validateSearch($dataObjectQuery->getSearch())
            );
        } catch (DocumentSearchException) {
            throw new SearchException('Document');
        }

        $results = $searchResult->getItems();
        if (empty($results)) {
            return null;
        }

        return reset($results);
    }

    /**
     * @throws InvalidSearchException
     */
    private function validateSearch(SearchInterface $search): DocumentSearchInterface
    {
        if (!$search instanceof DocumentSearchInterface) {
            throw new InvalidSearchException(
                HttpResponseCodes::BAD_REQUEST->value,
                sprintf(
                    'Expected search to be an instance of %s, got %s',
                    DocumentSearchInterface::class,
                    get_class($search)
                )
            );
        }

        return $search;
    }
}
