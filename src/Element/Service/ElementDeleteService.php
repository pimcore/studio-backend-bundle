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

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\AssetDeleteEvent;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\DataObjectDeleteEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\DocumentDeleteEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\DeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class ElementDeleteService implements ElementDeleteServiceInterface
{
    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DeleteServiceInterface $deleteService,
        private DocumentSearchServiceInterface $documentSearchService,
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private int $recycleBinThreshold
    ) {
    }

    /**
     * @throws ElementDeletionFailedException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function deleteElements(
        ElementParameters $elementParameters,
        UserInterface $user
    ): ?int {
        $element = $this->elementService->getAllowedElementById(
            $elementParameters->getType(),
            $elementParameters->getId(),
            $user
        );
        if (!$this->elementService->hasElementChildren($element)) {
            $this->addElementToRecycleBin($element, $user);
            $this->deleteParentElement($element, $user);

            return null;
        }
        $childrenIds = $this->getChildrenIds($element, 'desc');

        return $this->deleteService->deleteElementsWithExecutionEngine(
            $element,
            $user,
            $elementParameters->getType(),
            $childrenIds,
            count($childrenIds) <= $this->recycleBinThreshold,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function batchDeleteElements(IdsParameter $elementIds, string $elementType, UserInterface $user): ?int
    {
        if (!in_array($elementType, [ElementTypes::TYPE_ASSET, ElementTypes::TYPE_DATA_OBJECT], true)) {
            throw new InvalidElementTypeException($elementType);
        }

        $elements = [];
        foreach ($elementIds->getIds() as $elementId) {
            $elements[] = $this->elementService->getAllowedElementById($elementType, $elementId, $user);
        }

        if (empty($elements)) {
            return null;
        }

        return $this->deleteService->batchDeleteElements(
            $elements,
            $user,
            $elementType
        );
    }

    /**
     * {@inheritDoc}
     */
    public function processBatchDelete(ElementInterface $parentElement, UserInterface $user, string $elementType): void
    {
        if (!$this->elementService->hasElementChildren($parentElement)) {
            $this->deleteElementWithRecycleBin($parentElement, $user);

            return;
        }

        $this->deleteElementWithChildren($parentElement, $user, $elementType);
    }

    /**
     * @throws ElementDeletionFailedException|EnvironmentException|ForbiddenException|InvalidElementTypeException
     */
    public function deleteParentElement(
        ElementInterface $element,
        UserInterface $user
    ): void {
        $event = $this->getDeleteEvent($element);
        $this->eventDispatcher->dispatch($event, $event::EVENT_NAME);

        if (!$event->getDeletionAllowed()) {
            throw new ElementDeletionFailedException(
                $event->getElement()->getId(),
                $event->getReason()
            );
        }

        $this->deleteElement($element, $user);
    }

    /**
     * @throws ElementDeletionFailedException|EnvironmentException
     */
    public function deleteElement(
        ElementInterface $element,
        UserInterface $user
    ): void {

        /** @var User $user because of the core method */
        if (!$element->isAllowed(ElementPermissions::DELETE_PERMISSION, $user)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing %s permission on target element %s',
                    ElementPermissions::DELETE_PERMISSION,
                    $element->getId()
                )
            );
        }

        if ($element->isLocked()) {
            throw new ForbiddenException(sprintf('Element %s is locked', $element->getId())
            );
        }

        if ($this->elementService->hasElementChildren($element)) {
            throw new EnvironmentException(
                'Element has existing children'
            );
        }

        try {
            $this->synchronousProcessingService->enable();
            $element->delete();
        } catch (Exception $exception) {
            throw new ElementDeletionFailedException(
                $element->getId(),
                $element->getType(),
                $exception->getMessage()
            );
        }
    }

    /**
     * @throws InvalidElementTypeException
     */
    public function useRecycleBinForElement(
        ElementInterface $element,
        UserInterface $user
    ): bool {
        $path = $element->getRealFullPath();

        $childrenCount = match (true) {
            $element instanceof Asset => $this->assetSearchService->countChildren($path),
            $element instanceof DataObject => $this->dataObjectSearchService->countChildren($path),
            $element instanceof Document => $this->documentSearchService->countChildren($path),
            default => throw new InvalidElementTypeException($element->getType())
        };

        return $childrenCount <= $this->recycleBinThreshold;
    }

    public function addElementToRecycleBin(
        ElementInterface $element,
        UserInterface $user
    ): void {
        $bin = new Item();
        $bin->setElement($element);
        /** @var User $user because of the core method */
        $bin->save($user);
    }

    public function getElementDeleteInfo(
        ElementInterface $element,
        UserInterface $user
    ): DeleteInfo {
        $hasDependencies = $this->elementService->hasElementDependencies($element);
        $canUseRecycleBin = $this->useRecycleBinForElement($element, $user);

        return new DeleteInfo($hasDependencies, $canUseRecycleBin);
    }

    private function deleteElementWithRecycleBin(ElementInterface $element, UserInterface $user): void
    {
        $this->addElementToRecycleBin($element, $user);
        $this->deleteParentElement($element, $user);
    }

    private function deleteElementWithChildren(
        ElementInterface $parentElement,
        UserInterface $user,
        string $elementType
    ): void
    {
        $childrenIds = $this->getChildrenIds($parentElement, 'desc');
        $useRecycleBin = count($childrenIds) <= $this->recycleBinThreshold;

        $this->deleteChildElements($childrenIds, $elementType, $user, $useRecycleBin);
        $this->deleteParentElementConditionally($parentElement, $user, $useRecycleBin);
    }

    private function deleteChildElements(
        array $childrenIds,
        string $elementType,
        UserInterface $user,
        bool $useRecycleBin
    ): void {
        foreach ($childrenIds as $childrenId) {
            try {
                $element = $this->elementService->getElementById($elementType, $childrenId);
            } catch (NotFoundException) {
                continue;
            }

            if ($useRecycleBin) {
                $this->addElementToRecycleBin($element, $user);
            }

            $this->deleteElement($element, $user);
        }
    }

    private function deleteParentElementConditionally(
        ElementInterface $parentElement,
        UserInterface $user,
        bool $useRecycleBin
    ): void {
        if ($useRecycleBin) {
            $this->addElementToRecycleBin($parentElement, $user);
        }
        $this->deleteParentElement($parentElement, $user);
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getChildrenIds(ElementInterface $element, string $sortDirection): array
    {
        $path = $element->getRealFullPath();

        return match (true) {
            $element instanceof Asset => $this->assetSearchService->getChildrenIds($path, $sortDirection),
            $element instanceof DataObject => $this->dataObjectSearchService->getChildrenIds($path, $sortDirection),
            $element instanceof Document => $this->documentSearchService->getChildrenIds($path, $sortDirection),
            default => throw new InvalidElementTypeException($element->getType())
        };
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getDeleteEvent(
        ElementInterface $element
    ): AssetDeleteEvent|DataObjectDeleteEvent|DocumentDeleteEvent {
        return match (true) {
            $element instanceof Asset => new AssetDeleteEvent($element),
            $element instanceof DataObject => new DataObjectDeleteEvent($element),
            $element instanceof Document => new DocumentDeleteEvent($element),
            default => throw new InvalidElementTypeException($element->getType())
        };
    }
}
