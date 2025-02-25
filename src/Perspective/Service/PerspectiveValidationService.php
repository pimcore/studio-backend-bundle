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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
final readonly class PerspectiveValidationService implements PerspectiveValidationServiceInterface
{
    public function __construct(
        private ContextPermissionsServiceInterface $contextPermissionsService,
        private WidgetServiceInterface $widgetService,
        private WidgetValidationServiceInterface $widgetValidationService,
    ) {
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function validateWidgets(SavePerspectiveConfig $perspectiveConfig): void
    {
        $widgetGroups = [
            $perspectiveConfig->getWidgetsLeft(),
            $perspectiveConfig->getWidgetsRight(),
            $perspectiveConfig->getWidgetsBottom(),
        ];

        foreach ($widgetGroups as $widgets) {
            foreach ($widgets as $widgetId => $widgetType) {
                $this->widgetValidationService->validateWidgetType($widgetType);
                $this->widgetService->loadRepositoryByType($widgetType)->getConfiguration($widgetId);
            }
        }
    }

    /**
     * @throws NotFoundException
     */
    public function validateExpandedWidgets(SavePerspectiveConfig $perspectiveConfig): void
    {
        $this->validateExpandedWidget(
            $perspectiveConfig->getExpandedLeft(),
            $perspectiveConfig->getWidgetsLeft(),
            'left'
        );

        $this->validateExpandedWidget(
            $perspectiveConfig->getExpandedRight(),
            $perspectiveConfig->getWidgetsRight(),
            'right'
        );
    }

    public function getValidContextPermissions(array $perspectivePermissions): array
    {
        $contextPermissions = $this->contextPermissionsService->list();

        if (empty($contextPermissions)) {
            return $perspectivePermissions;
        }

        if (empty($perspectivePermissions)) {
            return $contextPermissions;
        }

        $perspectivePermissions = array_intersect_key($perspectivePermissions, $contextPermissions);
        $perspectivePermissions = $this->filterValidPermissions($perspectivePermissions, $contextPermissions);

        return $this->addMissingPermissions($perspectivePermissions, $contextPermissions);
    }

    /**
     * @throws NotFoundException
     */
    private function validateExpandedWidget(?string $expandedWidget, array $widgets, string $position): void
    {
        if ($expandedWidget === null) {
            return;
        }

        if (!array_key_exists($expandedWidget, $widgets)) {
            throw new NotFoundException(sprintf('widget in %s widgets', $position), $expandedWidget);
        }
    }

    private function filterValidPermissions(array $perspectivePermissions, array $contextPermissions): array
    {
        $filteredPermissions = [];
        foreach ($perspectivePermissions as $group => $permissions) {
            $filteredPermissions[$group] = array_intersect_key($permissions, $contextPermissions[$group]);
        }

        return $filteredPermissions;
    }

    private function addMissingPermissions(array $perspectivePermissions, array $contextPermissions): array
    {
        foreach ($contextPermissions as $group => $permissions) {
            $perspectivePermissions[$group] = array_replace(
                $permissions,
                $perspectivePermissions[$group] ?? []
            );
        }

        return $perspectivePermissions;
    }
}
