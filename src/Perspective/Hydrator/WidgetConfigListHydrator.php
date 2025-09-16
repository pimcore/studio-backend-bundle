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

use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;

/**
 * @internal
 */
final readonly class WidgetConfigListHydrator implements WidgetConfigListHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
    ) {
    }

    public function hydrate(array $widgetData): WidgetConfig
    {
        return new WidgetConfig(
            $widgetData['id'],
            $widgetData['name'],
            $widgetData['widgetType'],
            $this->iconService->getIconForValue($widgetData['icon']),
            $widgetData['elementType']
        );
    }
}
