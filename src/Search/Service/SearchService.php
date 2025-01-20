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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\SimpleSearchResultEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\SimpleSearchHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SearchRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\SimpleSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SearchService implements SearchServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    public function __construct(
        private SearchRepositoryInterface $searchRepository,
        private SimpleSearchHydratorInterface $simpleSearchHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws SearchException|UserNotFoundException
     */
    public function doSimpleSearch(SimpleSearchParameter $parameters): Collection
    {
        $result = $this->searchRepository->searchElements($parameters);
        $items = $result->getItems();

        $hydratedItems = [];
        foreach ($items as $item) {
            $hydratedItem = $this->simpleSearchHydrator->hydrate($item);
            $this->dispatchSearchEvent($hydratedItem);

            $hydratedItems[] = $hydratedItem;
        }

        return new Collection($result->getPagination()->getTotalItems(), $hydratedItems);
    }

    private function dispatchSearchEvent(SimpleSearchResult $resultItem): void
    {
        $this->eventDispatcher->dispatch(
            new SimpleSearchResultEvent($resultItem),
            SimpleSearchResultEvent::EVENT_NAME
        );
    }
}
