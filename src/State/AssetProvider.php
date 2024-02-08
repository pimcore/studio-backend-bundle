<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\Search\Tree\AssetTreeServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Filter\AssetParentIdFilter;
use Pimcore\Model\Asset\Image as ImageModel;


final class AssetProvider implements ProviderInterface
{
    public function __construct(
        private readonly AssetResolverInterface    $assetResolver,
        private readonly AssetTreeServiceInterface $assetTreeService,
        private readonly Pagination                $pagination
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $result =[];
            $totalItems = 0;
            if (array_key_exists(AssetParentIdFilter::ASSET_PARENT_ID_FILTER_CONTEXT, $context)){
                $parentId = (int)($context[AssetParentIdFilter::ASSET_PARENT_ID_FILTER_CONTEXT] ?? 1);
                $items = $this->assetTreeService->fetchTreeItems(
                    $parentId,
                    $this->pagination->getPage($context),
                    $this->pagination->getLimit($operation, $context)
                );
                $totalItems = $items->getPagination()->getTotalItems();
                foreach ($items->getItems() as $item) {
                    $assetModel = $this->assetResolver->getById($item->getId());
                    if ($assetModel === null) {
                        continue;
                    }
                    $result[] = new Asset($assetModel, new Asset\Permissions());
                }
            }

            return new TraversablePaginator(
                new ArrayIterator($result),
                $this->pagination->getPage($context),
                $this->pagination->getLimit($operation, $context),
                $totalItems
            );
        }
        $assetModel = $this->assetResolver->getById($uriVariables['id']);

        if ($assetModel === null) {
            return null;
        }

        if ($assetModel instanceof ImageModel) {
            return new Image($assetModel, new Asset\Permissions());
        }
        return new Asset($assetModel, new Asset\Permissions());
    }
}
