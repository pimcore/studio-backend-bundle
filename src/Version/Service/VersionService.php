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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\VersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\VersionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Response\Collection;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class VersionService implements VersionServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private VersionRepositoryInterface $repository,
        private ServiceResolverInterface $serviceResolver,
        private ServiceProviderInterface $versionPublisherLocator,
        private VersionHydratorInterface $versionHydrator,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getVersions(
        ElementParameters $elementParameters,
        CollectionParameters $parameters,
        UserInterface $user
    ): Collection {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementParameters->getType(),
            $elementParameters->getId(),
        );

        $list = $this->repository->listVersions($element, $elementParameters->getType(), $parameters, $user);

        $versions = [];
        $versionObjects = $list->load();
        foreach ($versionObjects as $versionObject) {
            $hydratedVersion = $this->versionHydrator->hydrate(
                $versionObject,
                $element->getVersionCount(),
                $element->getModificationDate()
            );

            $this->eventDispatcher->dispatch(
                new VersionEvent(
                    $hydratedVersion
                ),
                VersionEvent::EVENT_NAME
            );

            $versions[] = $hydratedVersion;
        }

        return new Collection(
            $versions,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $list->getTotalCount()
        );
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws ElementPublishingFailedException
     */
    public function publishVersion(
        int $versionId,
        UserInterface $user
    ): int {
        $version = $this->repository->getVersionById($versionId);
        $element = $this->repository->getElementFromVersion(
            $version,
            $user
        );
        $elementId = $element->getId();

        $currentElement = $this->getElement(
            $this->serviceResolver,
            $this->getElementType($element),
            $elementId,
        );

        $this->securityService->hasElementPermission(
            $currentElement,
            $user,
            ElementPermissions::PUBLISH_PERMISSION
        );

        $class = $this->getElementClass($currentElement);
        if (!$this->versionPublisherLocator->has($class)) {
            throw new InvalidElementTypeException($class);
        }

        $this->versionPublisherLocator->get($class)->publish(
            $element,
            $user
        );

        $lastVersion = $this->repository->getLastVersion(
            $elementId,
            $this->getElementType($element),
            $user
        );

        if (!$lastVersion) {
            throw new ElementPublishingFailedException(
                $elementId,
                'No last version was found'
            );
        }

        return $lastVersion->getId();
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function cleanupVersions(
        ElementParameters $elementParameters,
        UserInterface $user
    ): array {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementParameters->getType(),
            $elementParameters->getId()
        );

        $this->securityService->hasElementPermission(
            $element,
            $user,
            ElementPermissions::PUBLISH_PERMISSION
        );

        return $this->repository->cleanupVersions(
            $elementParameters,
            $element->getModificationDate()
        );
    }
}
