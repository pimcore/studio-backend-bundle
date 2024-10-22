<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class GridSearch implements GridSearchInterface
{
    private OpenSearchFilterInterface $filterService;

    public function __construct(
        private FilterServiceProviderInterface $filterServiceProvider,
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private SecurityServiceInterface $securityService
    ) {
        $this->filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);
    }

    /**
     * @throws NotFoundException|SearchException|InvalidArgumentException
     */
    public function searchAssets(GridParameter $gridParameter): AssetSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            $gridParameter,
            $this->securityService->getCurrentUser()
        );
    }

    public function searchAssetsForUser(GridParameter $gridParameter, UserInterface $user): AssetSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            $gridParameter,
            $user
        );
    }

    public function searchDataObjects(GridParameter $gridParameter): DataObjectSearchResult
    {
        return $this->searchElementsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            $gridParameter,
            $this->securityService->getCurrentUser()
        );
    }

    public function searchElementsForUser(
        string $type,
        GridParameter $gridParameter,
        UserInterface $user
    ): AssetSearchResult|DataObjectSearchResult {
        $filter = $gridParameter->getFilters();

        $folder = match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->getAssetById(
                $gridParameter->getFolderId(),
                $user
            ),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->getDataObjectById(
                $gridParameter->getFolderId(),
                $user
            ),
            default => throw new InvalidElementTypeException($type)
        };

        if (!$this->isFolderOfType($type, $folder)) {
            throw new NotFoundException($type . ' Folder', $gridParameter->getFolderId());
        }


        $filter->setPath($folder->getFullPath());

        /** @var AssetQueryInterface|DataObjectQueryInterface $query */
        $query = $this->filterService->applyFilters(
            $filter,
            $type
        );

        $query->setUser($user);

        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->searchAssets($query),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->searchDataObjects($query),
            default => throw new InvalidElementTypeException($type)
        };
    }

    private function isFolderOfType(string $type, ElementInterface $element): bool
    {
        if ($type === ElementTypes::TYPE_ASSET && $element instanceof AssetFolder) {
            return true;
        }

        if ($type === ElementTypes::TYPE_DATA_OBJECT && $element instanceof DataObjectFolder) {
            return true;
        }

        return false;
    }
}
