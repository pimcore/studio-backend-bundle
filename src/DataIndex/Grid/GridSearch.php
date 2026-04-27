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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class GridSearch implements GridSearchInterface
{
    private SearchIndexFilterInterface $filterService;

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DocumentSearchServiceInterface $documentSearchService,
        private FilterServiceProviderInterface $filterServiceProvider,
        private QueryFactoryInterface $queryFactory,
        private SecurityServiceInterface $securityService
    ) {
        $this->filterService = $this->filterServiceProvider->create(SearchIndexFilterInterface::SERVICE_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function searchAssets(GridParameter $gridParameter): AssetSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            $gridParameter,
            $this->securityService->getCurrentUser()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function searchAssetsForUser(GridParameter $gridParameter, UserInterface $user): AssetSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            $gridParameter,
            $user
        );
    }

    /**
     * {@inheritdoc}
     */
    public function searchDataObjects(GridParameter $gridParameter): DataObjectSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            $gridParameter,
            $this->securityService->getCurrentUser()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function searchDocuments(GridParameter $gridParameter): DocumentSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_DOCUMENT,
            $gridParameter,
            $this->securityService->getCurrentUser()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function searchElementsForUser(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): AssetSearchResult|DataObjectSearchResult|DocumentSearchResult {
        /** @var AssetQueryInterface|DataObjectQueryInterface|DocumentQueryInterface $query */
        $query = $this->getSearchQuery($type, $gridParameter, $user);

        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->searchAssets($query),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->searchDataObjects($query),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->searchDocuments($query),
            default => throw new InvalidElementTypeException($type)
        };
    }

    /**
     * {@inheritdoc}
     */
    public function searchElementIdsForUser(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): array {
        /** @var AssetQueryInterface|DataObjectQueryInterface $query */
        $query = $this->getSearchQuery($type, $gridParameter, $user);

        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->fetchAssetIds($query),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->fetchDataObjectIds($query),
            default => throw new InvalidElementTypeException($type)
        };
    }

    private function getSearchQuery(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): QueryInterface
    {
        $filter = $gridParameter->getFilters();
        $type = $this->getStudioElementType($type);
        $filter = $this->setFilterPath($filter, $type, $gridParameter->getFolderId(), $user);

        $query = $this->queryFactory->create($type);
        $query = $this->filterService->applyFilters($query, $filter, $type);
        $query->setUser($user);

        return $query;
    }

    private function setFilterPath(
        FilterParameter $filter,
        string $type,
        int $folderId,
        ?UserInterface $user
    ): FilterParameter {
        $folder = match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->getAssetById($folderId, $user),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->getDataObjectById($folderId, $user),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->getDocumentById($folderId, $user),
            default => throw new InvalidElementTypeException($type)
        };

        if (!$this->isFolderOfType($type, $folder)) {
            throw new NotFoundException($type . ' Folder', $folderId);
        }

        $filter->setPath($folder->getFullPath());

        return $filter;
    }

    private function isFolderOfType(string $type, StudioElementInterface $element): bool
    {
        if ($type === ElementTypes::TYPE_ASSET && $element instanceof AssetFolder) {
            return true;
        }

        if ($type === ElementTypes::TYPE_DATA_OBJECT && $element instanceof DataObjectFolder) {
            return true;
        }

        // We handle Object as folders since they can have child items.
        if ($type === ElementTypes::TYPE_DATA_OBJECT && $element instanceof DataObject) {
            return true;
        }

        // Allow all documents as folder since they can all have parent items.
        if ($type === ElementTypes::TYPE_DOCUMENT) {
            return true;
        }

        return false;
    }

    private function getStudioElementType(string $type): string
    {
        return match (true) {
            $type === ElementTypes::TYPE_OBJECT => ElementTypes::TYPE_DATA_OBJECT,
            default => $type
        };
    }
}
