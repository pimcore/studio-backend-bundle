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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\ConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\DetailEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\UsageDataEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\DetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\ObjectBrickConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateObjectBrickParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrickUsageData;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use function count;

/**
 * @internal
 */
final readonly class ObjectBrickService implements ObjectBrickServiceInterface
{
    public function __construct(
        private ObjectBrickRepositoryInterface $objectBrickRepository,
        private ObjectBrickConfigHydratorInterface $objectBrickConfigHydrator,
        private DetailHydratorInterface $detailHydrator,
        private DownloadServiceInterface $downloadService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function listObjectBricks(): Collection
    {
        $definitions = $this->objectBrickRepository->listObjectBricks();
        $objectBricks = [];

        foreach ($definitions as $definition) {
            $objectBrick = $this->objectBrickConfigHydrator->hydrate($definition);
            $this->eventDispatcher->dispatch(new ConfigEvent($objectBrick), ConfigEvent::EVENT_NAME);
            $objectBricks[] = $objectBrick;
        }

        return new Collection(count($objectBricks), $objectBricks);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectBrickByKey(string $key): ObjectBrickDetail
    {
        return $this->hydrateDetail(
            $this->objectBrickRepository->getObjectBrickByKey($key)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createObjectBrick(CreateObjectBrickParameters $parameters): ObjectBrickDetail
    {
        return $this->hydrateDetail(
            $this->objectBrickRepository->create($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateObjectBrick(string $key, UpdateParameters $parameters): ObjectBrickDetail
    {
        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);

        return $this->hydrateDetail(
            $this->objectBrickRepository->update($definition, $parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteObjectBrick(string $key): void
    {
        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        $this->objectBrickRepository->delete($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function exportObjectBrick(string $key): Response
    {
        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        $json = $this->objectBrickRepository->exportAsJson($definition);

        return $this->downloadService->downloadJSON(
            $json,
            'objectbrick_' . $definition->getKey() . '_export.json'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function importObjectBrickFromJson(string $key, string $json): ObjectBrickDetail
    {
        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        $definition = $this->objectBrickRepository->importFromJson($definition, $json);

        return $this->hydrateDetail($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectBrickUsages(string $key): array
    {
        $this->objectBrickRepository->getObjectBrickByKey($key);
        $usages = $this->objectBrickRepository->getObjectBrickUsages($key);
        $hydratedUsages = [];

        foreach ($usages as $usage) {
            $usageData = new ObjectBrickUsageData($usage['class'], $usage['field']);
            $this->eventDispatcher->dispatch(new UsageDataEvent($usageData), UsageDataEvent::EVENT_NAME);
            $hydratedUsages[] = $usageData;
        }

        return $hydratedUsages;
    }

    private function hydrateDetail(Definition $definition): ObjectBrickDetail
    {
        $detail = $this->detailHydrator->hydrate($definition);
        $this->eventDispatcher->dispatch(new DetailEvent($detail), DetailEvent::EVENT_NAME);

        return $detail;
    }
}
