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

use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\SimpleSearchPreviewEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse\SimpleSearchResultEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\SimpleSearchHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SearchRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\AssetSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DocumentSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\SimpleSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class SearchService implements SearchServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private SearchRepositoryInterface $searchRepository,
        private SecurityServiceInterface $securityService,
        private ServiceProviderInterface $previewHydratorLocator,
        private SimpleSearchHydratorInterface $simpleSearchHydrator,
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

    /**
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function getSearchPreview(
        ElementParameters $parameters
    ): AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview {
        $element = $this->elementService->getAllowedElementById(
            $parameters->getType(),
            $parameters->getId(),
            $this->securityService->getCurrentUser()
        );

        $this->securityService->hasElementPermission(
            $element,
            $this->securityService->getCurrentUser(),
            ElementPermissions::LIST_PERMISSION
        );

        $preview = $this->hydrate($element);
        $this->dispatchPreviewEvent($preview);

        return $preview;
    }

    private function dispatchSearchEvent(SimpleSearchResult $resultItem): void
    {
        $this->eventDispatcher->dispatch(
            new SimpleSearchResultEvent($resultItem),
            SimpleSearchResultEvent::EVENT_NAME
        );
    }

    private function dispatchPreviewEvent(
        AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview $preview
    ): void {
        $this->eventDispatcher->dispatch(
            new SimpleSearchPreviewEvent($preview),
            SimpleSearchPreviewEvent::EVENT_NAME
        );
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function hydrate(
        ElementInterface $element
    ): AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview {
        $class = $this->getElementClass($element);
        if ($this->previewHydratorLocator->has($class)) {
            return $this->previewHydratorLocator->get($class)->hydrate($element);
        }

        throw new InvalidElementTypeException($class);
    }
}
