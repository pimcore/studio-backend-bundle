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
use ApiPlatform\State\ProviderInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Tag;

final class AssetProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // collection of assets, needs to be further investigated
        if ($operation instanceof CollectionOperationInterface) {
            $assetListing = new Asset\Listing();
            $assetListing->setLimit(10);

            if (isset($context['filters']['page'])) {
                $assetListing->setOffset(10 * $context['filters']['page']);
            }

            return $assetListing;
        }
        // getting a single asset by id
        $test = Asset::getById($uriVariables['id']);

        //$tag = Tag::getTagsForElement('asset', $test->getId());
        return $test;
    }
}
