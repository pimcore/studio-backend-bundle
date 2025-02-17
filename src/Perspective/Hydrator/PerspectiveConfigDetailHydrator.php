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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Psr\Log\LoggerInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class PerspectiveConfigDetailHydrator implements PerspectiveConfigHydratorInterface
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
    public function hydrate(array $widgetData): PerspectiveConfigDetail
    {
        return new PerspectiveConfigDetail(
            $widgetData['id'],
            $widgetData['name'],
            $this->iconService->getIconForValue($widgetData['icon']),
            $this->validationService->getValidContextPermissions($widgetData['contextPermissions']),
            $this->hydrateWidgets($widgetData['widgetsLeft']),
            $this->hydrateWidgets($widgetData['widgetsRight']),
            $this->hydrateWidgets($widgetData['widgetsBottom']),
            $widgetData['isWriteable'],
            $widgetData['expandedLeft'],
            $widgetData['expandedRight'],
        );
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return WidgetConfig[]
     */
    private function hydrateWidgets(array $widgets): array
    {
        $widgetData = [];
        foreach ($widgets as $widgetId => $widgetType) {
            try {
                $widgetData[] = $this->widgetService->getWidgetConfigData($widgetType, $widgetId);
            } catch (InvalidArgumentException $e) {
                $this->pimcoreLogger->error(sprintf(
                    'Failed to retrieve widget (%s): %s', $widgetId, $e->getMessage())
                );
            }
        }

        return $widgetData;
    }
}
