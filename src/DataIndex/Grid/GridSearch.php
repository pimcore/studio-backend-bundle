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

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class GridSearch implements GridSearchInterface
{
    use ElementProviderTrait;

    private SearchIndexFilterInterface $filterService;

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DocumentSearchServiceInterface $documentSearchService,
        private FilterServiceProviderInterface $filterServiceProvider,
        private QueryFactoryInterface $queryFactory,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver
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
        $type = $this->getStudioElementType($type);
        /** @var AssetQueryInterface|DataObjectQueryInterface|DocumentQueryInterface $query */
        $query = $this->getSearchQuery($type, $gridParameter, $user);

        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->searchAssets($query, PermissionTypes::VIEW),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->searchDataObjects(
                $query,
                PermissionTypes::VIEW
            ),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->searchDocuments(
                $query,
                PermissionTypes::VIEW
            ),
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
        $type = $this->getStudioElementType($type);
        /** @var AssetQueryInterface|DataObjectQueryInterface $query */
        $query = $this->getSearchQuery($type, $gridParameter, $user);

        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->fetchAssetIds($query, PermissionTypes::VIEW),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->fetchDataObjectIds(
                $query,
                PermissionTypes::VIEW
            ),
            default => throw new InvalidElementTypeException($type)
        };
    }

    private function getSearchQuery(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): QueryInterface {
        $filter = $gridParameter->getFilters();
        $filter = $this->setFilterPath($filter, $type, $gridParameter->getFolderId(), $user);

        $query = $this->queryFactory->create($type);
        $query->setUser($user);

        return $this->filterService->applyFilters($query, $filter, $type);
    }

    private function setFilterPath(
        FilterParameter $filter,
        string $type,
        int $folderId,
        ?UserInterface $user
    ): FilterParameter {
        $supportedTypes = [ElementTypes::TYPE_ASSET, ElementTypes::TYPE_DATA_OBJECT, ElementTypes::TYPE_DOCUMENT];
        if (!in_array($type, $supportedTypes, true)) {
            throw new InvalidElementTypeException($type);
        }

        // The folder only scopes the search by path, the search itself only returns elements the user may view.
        // Like the tree, use the core list check, which also allows the parent folders of the user's workspaces.
        $folder = $this->getElement($this->serviceResolver, $type, $folderId);
        /** @var User|null $user */
        if ($user !== null && !$folder->isAllowed(ElementPermissions::LIST_PERMISSION, $user)) {
            throw new ForbiddenException(sprintf('You dont have %s permission', ElementPermissions::LIST_PERMISSION));
        }

        if (!$this->isFolderOfType($type, $folder)) {
            throw new NotFoundException($type . ' Folder', $folderId);
        }

        $filter->setPath($folder->getRealFullPath());

        return $filter;
    }

    private function isFolderOfType(string $type, ElementInterface $element): bool
    {
        // Data objects and documents can all have child items, so they are handled as folders.
        return $type !== ElementTypes::TYPE_ASSET || $element instanceof AssetFolder;
    }

    private function getStudioElementType(string $type): string
    {
        return match (true) {
            $type === ElementTypes::TYPE_OBJECT => ElementTypes::TYPE_DATA_OBJECT,
            default => $type
        };
    }
}
