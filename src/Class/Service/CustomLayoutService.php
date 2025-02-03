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
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout as CoreLayout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
final readonly class CustomLayoutService implements CustomLayoutServiceInterface
{
    public function __construct(
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private CustomLayoutHydratorInterface $customLayoutHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private DownloadServiceInterface $downloadService
    ) {
    }

    public function getCustomLayoutCollection(string $dataObjectClass): array
    {
        $compactLayouts = [];
        $layouts = $this->customLayoutRepository->getCustomLayouts($dataObjectClass);

        foreach ($layouts as $layout) {
            $compactLayout = $this->customLayoutHydrator->hydrateCompactLayout($layout);
            $this->eventDispatcher->dispatch(
                new CustomLayoutCollectionEvent($compactLayout),
                CustomLayoutCollectionEvent::EVENT_NAME
            );
            $compactLayouts[] = $compactLayout;
        }

        return $compactLayouts;
    }

    public function getCustomLayout(string $customLayoutId): CustomLayout
    {
        return $this->hydrateLayout(
            $this->customLayoutRepository->getCustomLayout($customLayoutId)
        );
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

    private function hydrateLayout(CoreLayout $layout): CustomLayout
    {
        $hydratedLayout = $this->customLayoutHydrator->hydrateLayout($layout);
        $this->eventDispatcher->dispatch(
            new CustomLayoutEvent($hydratedLayout),
            CustomLayoutEvent::EVENT_NAME
        );

        return $hydratedLayout;
    }
}
