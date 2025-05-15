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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Hydrator\DocTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\DocTypeRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DocTypeService implements DocTypeServiceInterface
{
    use UserPermissionTrait;

    public function __construct(
        private DocTypeHydratorInterface $hydrator,
        private DocTypeRepositoryInterface $docTypeRepository,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function listDocTypes(?string $type): array
    {
        $docTypes = [];
        $docTypeList = $this->docTypeRepository->listDocTypes($type);
        foreach ($docTypeList as $docType) {
            if (!$this->securityService->getCurrentUser()->isAllowed(
                $docType->getId(),
                ElementTypes::DOC_TYPE)
            ) {
                continue;
            }

            $hydrated = $this->hydrator->hydrate($docType);
            $this->eventDispatcher->dispatch(new DocTypeEvent($hydrated), DocTypeEvent::EVENT_NAME);

            $docTypes[] = $hydrated;
        }

        return $docTypes;
    }
}
