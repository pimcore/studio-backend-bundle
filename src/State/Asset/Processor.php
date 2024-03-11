<?php

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

namespace Pimcore\Bundle\StudioApiBundle\State\Asset;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\ModelData\V1\Hydrator\AssetHydratorServiceInterface;
use Pimcore\Model\Element\DuplicateFullPathException;

final readonly class Processor implements ProcessorInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetHydratorServiceInterface $assetHydratorService

    ) {
    }

    /**
     * @throws DuplicateFullPathException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Asset
    {
        if (
            !$operation instanceof Post ||
            !$data instanceof Asset
        ) {
            // wrong operation
            throw new OperationNotFoundException();
        }

        $asset = $this->assetService->handleAsset($data->getId(), $data);

        $asset->save();

        return $this->assetHydratorService->hydrate($asset);
    }
}