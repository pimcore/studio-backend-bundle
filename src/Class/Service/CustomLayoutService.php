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

use Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayout\CustomLayoutCollectionEvent;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\CustomLayout\CustomLayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CustomLayout\CustomLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CustomLayoutService implements CustomLayoutServiceInterface
{
    public function __construct(
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private CustomLayoutHydratorInterface $customLayoutHydrator,
        private EventDispatcherInterface $eventDispatcher
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
        $layout = $this->customLayoutRepository->getCustomLayout($customLayoutId);
        $layout = $this->customLayoutHydrator->hydrateLayout($layout);
        $this->eventDispatcher->dispatch(
            new CustomLayoutEvent($layout),
            CustomLayoutEvent::EVENT_NAME
        );

        return $layout;
    }

    public function deleteCustomLayout(string $customLayoutId): void
    {
        $this->customLayoutRepository->deleteCustomLayout($customLayoutId);
    }
}
