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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse\RecycleBinEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\Element\Recyclebin as ElementRecycleBin;
use Pimcore\Model\Element\Recyclebin\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class RecycleBinService implements RecycleBinServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private RecycleBinHydratorInterface $hydrator,
        private RecycleBinRepositoryInterface $recycleBinRepository
    ) {
    }

    public function listRecycleBin(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->recycleBinRepository->getListing($this->getFilterParameters($parameters));
        $items = $listing->load();
        $list = [];

        foreach ($items as $item) {
            $list[] = $this->getHydratedItem($item);
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    public function flushRecycleBin(): void
    {
        $bin = new ElementRecycleBin();
        $bin->flush();
    }

    private function getHydratedItem(Item $item): RecycleBin
    {
        $entry = $this->hydrator->hydrate($item);
        $this->eventDispatcher->dispatch(
            new RecycleBinEvent($entry),
            RecycleBinEvent::EVENT_NAME
        );

        return $entry;
    }

    private function getFilterParameters(CollectionFilterParameter $parameters): FilterParameter
    {
        $filterParameters = new FilterParameter();
        if ($parameters->getFilters()) {
            $filterParameters = $this->filterMapper->map($parameters);
        }

        return $filterParameters;
    }
}
