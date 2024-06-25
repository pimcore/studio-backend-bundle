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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\AssetDeleteEvent;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\DataObjectDeleteEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ElementDeleteService implements ElementDeleteServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private ElementServiceInterface $elementService,
        private EventDispatcherInterface $eventDispatcher,
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private int $recycleBinThreshold
    ) {
    }

    /**
     * @throws ElementDeletionFailedException|ForbiddenException
     */
    public function deleteParentElement(
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
            throw new ForbiddenException(
                sprintf(
                    'Asset %s is locked',
                    $element->getId()
                )
            );
        }

        $event = $this->getDeleteEvent($element);
        $this->eventDispatcher->dispatch($event);

        if (!$event->getDeletionAllowed()) {
            throw new ElementDeletionFailedException(
                $event->getAsset()->getId(),
                $event->getReason()
            );
        }

        $this->deleteElement($element, $user);
    }

    /**
     * @throws ElementDeletionFailedException
     */
    public function deleteElement(
        ElementInterface $element,
        UserInterface $user
    ): void {
        if (($element instanceof Asset ||
            $element instanceof Document ||
            $element instanceof DataObject) &&
            $element->hasChildren()
        ) {
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

    public function useRecycleBinForElement(
        ElementInterface $element,
        UserInterface $user
    ): bool {
        $path = $element->getRealFullPath();
        // ToDO Implement For Documents
        $childrenCount = match (true) {
            $element instanceof Asset => $this->assetSearchService->countChildren($path),
            $element instanceof DataObject => $this->dataObjectSearchService->countChildren($path),
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
    ): DeleteInfo
    {
        $hasDependencies = $this->elementService->hasElementDependencies($element);
        $canUseRecycleBin = $this->useRecycleBinForElement($element, $user);

        return new DeleteInfo($hasDependencies, $canUseRecycleBin);
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getDeleteEvent(
        ElementInterface $element
    ): AssetDeleteEvent|DataObjectDeleteEvent {
        return match (true) {
            $element instanceof Asset => new AssetDeleteEvent($element),
            $element instanceof DataObject => new DataObjectDeleteEvent($element),
            default => throw new InvalidElementTypeException($element->getType())
        };
    }
}
