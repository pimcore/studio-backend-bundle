<?php

namespace Pimcore\Bundle\StudioApiBundle\State\Asset;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

final readonly class Processor implements ProcessorInterface
{

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Asset
    {
        if (
            !$operation instanceof Put ||
            !$data instanceof Asset
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }


    }
}