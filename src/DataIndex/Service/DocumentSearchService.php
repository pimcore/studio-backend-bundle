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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DocumentSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Element\Util\Trait\SearchTermTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DocumentSearchService implements DocumentSearchServiceInterface
{
    use SearchTermTrait;

    public function __construct(
        private DocumentSearchAdapterInterface $documentSearchAdapter,
        private DocumentQueryProviderInterface $documentQueryProvider,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function searchDocuments(DocumentQueryInterface $documentQuery): DocumentSearchResult
    {
        return $this->documentSearchAdapter->searchDocuments($documentQuery);
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentById(int $id, ?UserInterface $user): Document
    {
        return $this->documentSearchAdapter->getDocumentById($id, $user);
    }

    public function getChildrenIds(string $parentPath, ?string $sortDirection = null): array
    {
        $query = $this->documentQueryProvider->createDocumentQuery();
        $query->filterPath($parentPath, true, false);
        if ($sortDirection) {
            $query->orderByPath($sortDirection);
        }

        return $this->fetchDocumentIds($query);
    }

    public function fetchDocumentIds(DocumentQueryInterface $documentQuery): array
    {
        return $this->documentSearchAdapter->fetchDocumentIds($documentQuery);
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchTerm(string $searchTerm, ?UserInterface $user): int
    {
        $query = $this->documentQueryProvider->createDocumentQuery();
        $this->applySearchTerm($query, $searchTerm, $user);
        $result = $this->documentSearchAdapter->fetchDocumentIds($query);

        if (empty($result)) {
            throw new NotFoundException('asset', $searchTerm);
        }

        return reset($result);
    }

    /**
     * {@inheritDoc}
     */
    public function findElementInTree(QueryInterface $query): ?ElementSearchResultItemInterface
    {
        return $this->documentSearchAdapter->findInTree($query);
    }
}
