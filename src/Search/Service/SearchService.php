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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementViewPermissionTrait;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class SearchService implements SearchServiceInterface
{
    use ElementProviderTrait;
    use ElementViewPermissionTrait;

    public function __construct(
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private SearchRepositoryInterface $searchRepository,
        private SecurityServiceInterface $securityService,
        private ServiceProviderInterface $previewHydratorLocator,
        private SimpleSearchHydratorInterface $simpleSearchHydrator,
        private ServiceResolverInterface $serviceResolver,
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
        $user = $this->securityService->getCurrentUser();
        foreach ($items as $item) {
            // The index only returns elements the user may view. Items whose index permissions say otherwise
            // (workspace parents on older index versions) are checked against the core permissions.
            try {
                $isViewable = $item->getPermissions()->isView()
                    || $this->isElementViewAllowed($this->getElementTypeFromItem($item), $item->getId(), $user);
            } catch (NotFoundException) {
                // The index references an element that no longer exists.
                continue;
            }

            if (!$isViewable) {
                continue;
            }

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

    private function getElementTypeFromItem(ElementSearchResultItemInterface $item): string
    {
        return match ($item->getElementType()) {
            ElementType::ASSET => ElementTypes::TYPE_ASSET,
            ElementType::DOCUMENT => ElementTypes::TYPE_DOCUMENT,
            ElementType::DATA_OBJECT => ElementTypes::TYPE_OBJECT,
        };
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
