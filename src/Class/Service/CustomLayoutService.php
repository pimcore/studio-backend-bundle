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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayout\CustomLayoutCollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayout\CustomLayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CustomLayout\CustomLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LayoutServiceInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout as CoreLayout;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use function in_array;

/**
 * @internal
 */
final readonly class CustomLayoutService implements CustomLayoutServiceInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private CustomLayoutHydratorInterface $customLayoutHydrator,
        private DataObjectServiceInterface $dataObjectResolver,
        private DownloadServiceInterface $downloadService,
        private EventDispatcherInterface $eventDispatcher,
        private LayoutServiceInterface $securityLayoutService
    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException
     *
     * @return CustomLayoutCompact[]
     */
    public function getCustomLayoutEditorCollection(
        int $dataObjectId,
        UserInterface $user
    ): array {
        $dataObject = $this->dataObjectResolver->getDataObjectElement($user, $dataObjectId);
        $allowedLayouts = [];
        if (!$user->isAdmin()) {
            $allowedLayouts = $this->securityLayoutService->getUserAllowedLayoutsByClass($dataObject, $user);
        }

        $layouts = $this->getUserCustomLayouts($dataObject, $user, $allowedLayouts);
        usort($layouts, static function (CoreLayout $a, CoreLayout $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $compactLayouts = [];
        foreach ($layouts as $layout) {
            $compactLayouts[] = $this->hydrateCompactLayout($layout);
        }

        return $compactLayouts;
    }

    public function getCustomLayoutCollection(string $dataObjectClass): array
    {
        $compactLayouts = [];
        $layouts = $this->customLayoutRepository->getCustomLayouts($dataObjectClass);

        foreach ($layouts as $layout) {
            $compactLayouts[] = $this->hydrateCompactLayout($layout);
        }

        return $compactLayouts;
    }

    public function getCustomLayout(string $customLayoutId): CustomLayout
    {
        return $this->hydrateLayout(
            $this->customLayoutRepository->getCustomLayout($customLayoutId)
        );
    }

    /**
     * @return CoreLayout[]
     */
    public function getUserCustomLayouts(DataObject $dataObject, UserInterface $user, array $allowedLayouts): array
    {
        $layouts = $this->handleCustomLayoutPermissions(
            $this->customLayoutRepository->getCustomLayouts($dataObject->getClassId()),
            $user,
            $allowedLayouts
        );

        return $this->addMainLayouts($user, $allowedLayouts, $layouts);
    }

    public function deleteCustomLayout(string $customLayoutId): void
    {
        $this->customLayoutRepository->deleteCustomLayout(
            $this->customLayoutRepository->getCustomLayout($customLayoutId)
        );
    }

    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout {
        return $this->hydrateLayout(
            $this->customLayoutRepository->createCustomLayout($customLayoutId, $customLayoutParameters)
        );
    }

    public function updateCustomLayout(
        string $customLayoutId,
        CustomLayoutUpdateParameters $customLayoutParameters
    ): CustomLayout {
        return $this->hydrateLayout(
            $this->customLayoutRepository->updateCustomLayout(
                $this->customLayoutRepository->getCustomLayout($customLayoutId),
                $customLayoutParameters
            )
        );
    }

    public function exportCustomLayoutAsJson(string $customLayoutId): JsonResponse
    {
        $customLayout = $this->customLayoutRepository->getCustomLayout($customLayoutId);
        $json = $this->customLayoutRepository->exportCustomLayoutAsJson($customLayout);

        return $this->downloadService->downloadJSON(
            $json,
            'custom_definition_' . $customLayout->getName() . '_export.json'
        );
    }

    public function importCustomLayoutActionFromJson(string $customLayoutId, string $json): CustomLayout
    {
        $customLayout = $this->customLayoutRepository->getCustomLayout($customLayoutId);
        $customLayout = $this->customLayoutRepository->importCustomLayoutFromJson($customLayout, $json);

        return $this->hydrateLayout($customLayout);
    }

    public function getMainLayout(): CoreLayout
    {
        return (new CoreLayout())
            ->setName('main')
            ->setId('0')
            ->setDefault(false);
    }

    public function getMainAdminLayout(): CoreLayout
    {
        return (new CoreLayout())
            ->setName('main_admin')
            ->setId('-1')
            ->setDefault(false);
    }

    private function hydrateLayout(CoreLayout $layout): CustomLayout
    {
        $hydratedLayout = $this->customLayoutHydrator->hydrateLayout($layout);
        $this->eventDispatcher->dispatch(
            new CustomLayoutEvent($hydratedLayout),
            CustomLayoutEvent::EVENT_NAME
        );

        return $hydratedLayout;
    }

    private function hydrateCompactLayout(CoreLayout $layout): CustomLayoutCompact
    {
        $compactLayout = $this->customLayoutHydrator->hydrateCompactLayout($layout);
        $this->eventDispatcher->dispatch(
            new CustomLayoutCollectionEvent($compactLayout),
            CustomLayoutCollectionEvent::EVENT_NAME
        );

        return $compactLayout;
    }

    /**
     * @param CoreLayout[] $layouts
     *
     * @return CoreLayout[]
     */
    private function handleCustomLayoutPermissions(array $layouts, UserInterface $user, array $allowedLayouts): array
    {
        if ($user->isAdmin()) {
            return $layouts;
        }

        foreach ($layouts as $key => $layout) {
            if (!in_array($layout->getId(), $allowedLayouts, true)) {
                unset($layouts[$key]);
            }
        }

        return $layouts;
    }

    /**
     * @param string[] $allowedLayouts
     * @param CoreLayout[] $hydratedLayouts
     *
     * @return CoreLayout[]
     */
    private function addMainLayouts(UserInterface $user, array $allowedLayouts, array $hydratedLayouts): array
    {
        if ($user->isAdmin()) {
            array_unshift($hydratedLayouts, $this->getMainAdminLayout(), $this->getMainLayout());

            return $hydratedLayouts;
        }

        if (in_array('0', $allowedLayouts, true)) {
            array_unshift($hydratedLayouts, $this->getMainLayout());
        }

        return $hydratedLayouts;
    }
}
