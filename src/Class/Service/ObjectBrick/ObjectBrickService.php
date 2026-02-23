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
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\DetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\ObjectBrickConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickDetail;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    private function hydrateDetail(Definition $definition): ObjectBrickDetail
    {
        $detail = $this->detailHydrator->hydrate($definition);
        $this->eventDispatcher->dispatch(new DetailEvent($detail), DetailEvent::EVENT_NAME);

        return $detail;
    }
}
