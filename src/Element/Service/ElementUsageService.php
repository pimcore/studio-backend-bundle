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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementUsageEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementUsageItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\ElementUsageHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\ReplaceAssignmentParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\UsageParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsage;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsageItem;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class ElementUsageService implements ElementUsageServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ServiceResolverInterface $serviceResolver,
        private ElementUsageHydratorInterface $elementUsageHydrator,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws DuplicateFullPathException
     */
    public function replaceUsage(
        string $elementType,
        int $elementId,
        ReplaceAssignmentParameter $replaceAssignmentParameter
    ): int {

        $targetId = $replaceAssignmentParameter->getTargetId();
        $targetType = $replaceAssignmentParameter->getTargetType();

        if($elementType !== $targetType) {
            throw new InvalidArgumentException("Source and target element types must match.");
        }

        if($elementId === $targetId) {
            throw new InvalidArgumentException("Source and target element cannot be the same.");
        }

        $sourceElement = $this->getElement(
            $this->serviceResolver,
            $elementType,
            $elementId
        );

        $targetElement = $this->getElement(
            $this->serviceResolver,
            $replaceAssignmentParameter->getTargetType(),
            $replaceAssignmentParameter->getTargetId()
        );

        $rewriteConfig = [
            $sourceElement->getType() => [
                $sourceElement->getId() => $targetElement->getId(),
            ],
        ];

        foreach($replaceAssignmentParameter->getElements() as $elementData) {
            $element = $this->getElement(
                $this->serviceResolver,
                $elementData->getType(),
                $elementData->getId()
            );

            if(!$element->isAllowed('save')) {
                continue;
            }

            if ($element instanceof Document) {
                $element = Document\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof AbstractObject) {
                $element = DataObject\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof Asset) {
                $element = Asset\Service::rewriteIds($element, $rewriteConfig);
            }

            $element->setUserModification($this->securityService->getCurrentUser()->getId());
            $element->save();
        }

        return 10;
    }

    public function getUsages(
        string $elementType,
        int $elementId,
        UsageParameter $usageParameter
    ): ElementUsage {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementType,
            $elementId
        );

        $hydratedUsageItems = [];
        $hasHidden = false;
        $limit = $usageParameter->getPageSize();
        $queryLimit = $limit;
        $total = $element->getDependencies()->getRequiredByTotalCount();
        $queryOffset = $this->getOffset($limit, $usageParameter->getPage());

        while (
            $this->continueCollectingUsageItems(
                $hydratedUsageItems,
                $limit,
                $queryOffset,
                $total
            )
        ) {
            $elements = $element->getDependencies()
                ->getRequiredByWithPath(
                    $queryOffset,
                    $queryLimit,
                    $usageParameter->getSortBy(),
                    $usageParameter->getSortOrder()
                );

            [$hydratedUsageItems, $currentHasHiddenValue] = $this->processElementUsage($elements);
            $hasHidden = $hasHidden || $currentHasHiddenValue;

            $queryOffset += count($elements);
            $queryLimit = $limit - count($hydratedUsageItems);
        }

        $hydratedUsageCollection = $this->elementUsageHydrator->hydrateUsage(
            $hydratedUsageItems,
            $hasHidden,
            $total
        );

        $this->eventDispatcher->dispatch(
            new ElementUsageEvent($hydratedUsageCollection),
            ElementUsageEvent::EVENT_NAME
        );

        return $hydratedUsageCollection;
    }

    /**
     * @param array<ElementInterface> $elements
     *
     * @return array{array<ElementUsageItem>, bool}
     */
    private function processElementUsage(array $elements): array
    {
        $hydratedUsageItems = [];
        $hasHidden = false;

        foreach ($elements as $el) {
            $item = $this->getElement(
                $this->serviceResolver,
                $el['type'],
                $el['id']
            );

            if ($item->isAllowed('list')) {
                $hydratedUsageItem = $this->elementUsageHydrator->hydrateUsageItem($item);

                $this->eventDispatcher->dispatch(
                    new ElementUsageItemEvent($hydratedUsageItem),
                    ElementUsageItemEvent::EVENT_NAME
                );

                $hydratedUsageItems[] = $hydratedUsageItem;
            } else {
                $hasHidden = true;
            }
        }

        return [$hydratedUsageItems, $hasHidden];
    }

    private function continueCollectingUsageItems(
        array $collectedItems,
        int $maxItemsToCollect,
        int $currentOffset,
        int $totalDependencies
    ): bool {
        return count($collectedItems) < $maxItemsToCollect && $currentOffset < $totalDependencies;
    }

    private function getOffset(int $limit, int $page): int
    {
        return ($page - 1) * $limit;
    }
}
