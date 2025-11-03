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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Psr\Log\LoggerInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class PerspectiveConfigDetailHydrator implements PerspectiveConfigDetailHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private LoggerInterface $pimcoreLogger,
        private PerspectiveValidationServiceInterface $validationService,
        private WidgetServiceInterface $widgetService,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hydrate(array $perspectiveData): PerspectiveConfigDetail
    {
        $isDefault = $perspectiveData['id'] === Perspectives::DEFAULT_ID->value;

        return new PerspectiveConfigDetail(
            $perspectiveData['id'],
            $perspectiveData['name'],
            $this->iconService->getIconForValue($perspectiveData['icon']),
            $isDefault ? false : $perspectiveData['isWriteable'],
            $this->validationService->getValidContextPermissions($perspectiveData['contextPermissions']),
            $this->hydrateWidgets($perspectiveData['widgetsLeft'], $isDefault),
            $this->hydrateWidgets($perspectiveData['widgetsRight'], $isDefault),
            $this->hydrateWidgets($perspectiveData['widgetsBottom'], $isDefault),
            $perspectiveData['expandedLeft'],
            $perspectiveData['expandedRight'],
        );
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return WidgetConfig[]
     */
    private function hydrateWidgets(array $widgets, bool $isDefault): array
    {
        if ($isDefault) {
            return $this->hydrateDefaultWidgets($widgets);
        }

        $widgetData = [];
        foreach ($widgets as $widgetId => $widgetType) {
            try {
                $widgetData[] = $this->widgetService->getWidgetConfigData($widgetType, $widgetId);
            } catch (InvalidArgumentException|NotFoundException $e) {
                $this->pimcoreLogger->error(sprintf(
                    'Failed to retrieve widget (%s): %s', $widgetId, $e->getMessage())
                );
            } catch (ForbiddenException) {
                continue;
            }
        }

        return $widgetData;
    }

    private function hydrateDefaultWidgets(array $widgets): array
    {
        $widgetData = [];
        foreach ($widgets as $widget) {
            $widgetData[] = $this->widgetService->loadHydratorByType($widget['widget_type'])->hydrate($widget);
        }

        return $widgetData;
    }
}
