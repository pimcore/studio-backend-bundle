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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\LayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\ObjectLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LayoutServiceInterface as SecurityLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout as CoreLayout;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function get_class;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class LayoutService implements LayoutServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private DataObjectServiceInterface $dataObjectService,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private EventDispatcherInterface $eventDispatcher,
        private ObjectLayoutHydratorInterface $hydrator,
        private SecurityLayoutServiceInterface $securityLayoutService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function getDataObjectLayout(int $id, ?string $layoutId = null): Layout
    {
        $user = $this->securityService->getCurrentUser();
        $dataObject = $this->dataObjectService->getDataObjectElement(
            $user,
            $id
        );

        $version = $this->getLatestVersionForUser($dataObject, $user);
        $dataObject = $this->getVersionData($dataObject, $version);

        if (!$dataObject instanceof Concrete) {
            throw new InvalidElementTypeException(
                sprintf('DataObject class (%s) is not a concrete object', get_class($dataObject))
            );
        }

        try {
            $class = $dataObject->getClass();
        } catch (Exception) {
            throw new NotFoundException(type: 'class for data object', id: $id);
        }

        $layout = $this->getLayoutDefinitions($user, $dataObject, $class, $layoutId);
        $this->dataObjectServiceResolver->enrichLayoutDefinition($layout, $dataObject);
        if (!$layout instanceof Panel) {
            throw new NotFoundException(type: 'class layout for data object', id: $id);
        }

        $hydratedLayout = $this->hydrator->hydrateLayout($layout);
        $this->eventDispatcher->dispatch(new LayoutEvent($hydratedLayout), LayoutEvent::EVENT_NAME);

        return $hydratedLayout;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getLayoutDefinitions(
        UserInterface $user,
        Concrete $dataObject,
        ClassDefinition $class,
        ?string $layoutId = null
    ): CoreLayout {
        if ($layoutId === null) {
            return $class->getLayoutDefinitions();
        }

        return $this->getLayoutById($dataObject, $class, $layoutId, $user);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getLayoutById(
        Concrete $dataObject,
        ClassDefinition $class,
        string $layoutId,
        UserInterface $user
    ): CoreLayout {
        if (!$user->isAdmin()) {
            $allowedLayouts = $this->securityLayoutService->getUserAllowedLayoutsByClass($dataObject, $user);
            if ($layoutId === '-1' || !in_array($layoutId, $allowedLayouts, true)) {
                throw new ForbiddenException('Layout not allowed for this user');
            }
        }

        return match ($layoutId) {
            '0' => $class->getLayoutDefinitions(),
            '-1' => $this->dataObjectServiceResolver->getSuperLayoutDefinition($dataObject),
            default => $this->customLayoutRepository->getCustomLayout($layoutId)->getLayoutDefinitions(),
        };
    }
}
