<?php

namespace Pimcore\Bundle\StudioApiBundle\State\Asset;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator\AssetHydratorServiceInterface;

final readonly class Processor implements ProcessorInterface
{

    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetHydratorServiceInterface $assetHydratorService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Asset
    {
        if (
            !$operation instanceof Patch ||
            !$data instanceof Asset
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }
        $asset = $this->assetService->processAsset($data);

        return $this->assetHydratorService->hydrate($asset);
    }
}