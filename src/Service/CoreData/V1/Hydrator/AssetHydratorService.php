<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Asset as CoreAsset;
use Symfony\Contracts\Service\ServiceProviderInterface;

final readonly class AssetHydratorService implements AssetHydratorInterface
{
    public function __construct(
        private ServiceProviderInterface $assetHydratorLocator,
    ) {
    }

    public function hydrate(CoreAsset $item): ?Asset
    {
        $class = get_class($item);
        if($this->assetHydratorLocator->has($class)) {
            return $this->assetHydratorLocator->get($class)->hydrate($item);
        }

        return null;
        //return new Asset($item->getId());
    }
}