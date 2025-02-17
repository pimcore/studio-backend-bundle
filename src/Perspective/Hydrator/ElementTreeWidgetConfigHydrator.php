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

use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;

/**
 * @internal
 */
final readonly class ElementTreeWidgetConfigHydrator implements WidgetConfigHydratorInterface
{
    public function __construct(
        private ContextPermissionServiceInterface $contextPermissionService,
        private IconServiceInterface $iconService,
    ) {
    }

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::ELEMENT_TREE->value;
    }

    public function hydrate(array $widgetData): ElementTreeWidgetConfig
    {
        return new ElementTreeWidgetConfig(
            $widgetData['id'],
            $widgetData['name'],
            $this->contextPermissionService->setElementContextPermissions(
                $widgetData['elementType'],
                $widgetData['contextPermissions']
            ),
            $this->iconService->getIconForValue($widgetData['icon']),
            $widgetData['elementType'],
            $widgetData['rootFolder'],
            $widgetData['showRoot'],
            $widgetData['classes'],
            $widgetData['pql'],
            $widgetData['isWriteable']
        );
    }
}
