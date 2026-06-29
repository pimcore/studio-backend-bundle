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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\EditLockResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\EditLockEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\EditLockHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\EditLock;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
final readonly class EditLockService implements EditLockServiceInterface
{
    use RequestTrait;
    use ValidateElementTypeTrait;

    public function __construct(
        private EditLockResolverInterface $editLockResolver,
        private EditLockHydratorInterface $editLockHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {
    }

    public function getEditLock(int $id, string $elementType): EditLock
    {
        $sessionId = $this->getCurrentSession($this->requestStack)->getId();
        $this->validateStudioTypes($elementType);
        $elementType = $this->getCoreElementType($elementType);
        $isLocked = $this->editLockResolver->isLocked($id, $elementType, $sessionId);

        $editLockModel = $isLocked
            ? $this->editLockResolver->getByElement($id, $elementType)
            : null;

        $editLock = $this->editLockHydrator->hydrateEditLock($editLockModel);

        $this->eventDispatcher->dispatch(
            new EditLockEvent($editLock),
            EditLockEvent::EVENT_NAME
        );

        return $editLock;
    }

    public function lockElement(int $id, string $elementType): void
    {
        $sessionId = $this->getCurrentSession($this->requestStack)->getId();
        $this->validateStudioTypes($elementType);
        $elementType = $this->getCoreElementType($elementType);
        $this->editLockResolver->lock($id, $elementType, $sessionId);
    }

    public function unlockElement(int $id, string $elementType): void
    {
        $this->validateStudioTypes($elementType);
        $elementType = $this->getCoreElementType($elementType);
        $this->editLockResolver->unlock($id, $elementType);
    }
}
