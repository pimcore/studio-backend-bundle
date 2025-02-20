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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

final readonly class ElementSearchService implements ElementSearchServiceInterface
{
    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DocumentSearchServiceInterface $documentSearchService,

    ) {
    }

    public function getElementById(string $type, int $id, ?UserInterface $user = null): mixed
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->getAssetById($id, $user),
            ElementTypes::TYPE_OBJECT => $this->dataObjectSearchService->getDataObjectById($id, $user),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->getDocumentById($id, $user),
            default => throw new InvalidElementTypeException($type),
        };
    }

    public function getChildrenIds(string $type, string $parentPath, ?string $sortDirection = null): array
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->getChildrenIds($parentPath, $sortDirection),
            ElementTypes::TYPE_OBJECT => $this->dataObjectSearchService->getChildrenIds($parentPath, $sortDirection),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->getChildrenIds($parentPath, $sortDirection),
            default => throw new InvalidElementTypeException($type),
        };
    }

    /**
     * @throws InvalidElementTypeException|NotFoundException|SearchException
     */
    public function getElementBySearchTerm(string $type, string $searchTerm, ?UserInterface $user = null): int
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->getBySearchTerm($searchTerm, $user),
            ElementTypes::TYPE_OBJECT => $this->dataObjectSearchService->getSearchTerm($searchTerm, $user),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->getSearchTerm($searchTerm, $user),
            default => throw new InvalidElementTypeException($type),
        };
    }

    /**
     * @throws InvalidElementTypeException|InvalidSearchException|SearchException
     */
    public function findElementInTree(string $type, int $id, QueryInterface $query): ?ElementSearchResultItemInterface
    {
        $query->searchById($id);

        return match ($type) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->findElementInTree($query),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectSearchService->findElementInTree($query),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->findElementInTree($query),
            default => throw new InvalidElementTypeException($type),
        };
    }
}
