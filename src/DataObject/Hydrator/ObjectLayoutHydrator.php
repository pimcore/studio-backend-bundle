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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;

/**
 * @internal
 */
final readonly class ObjectLayoutHydrator implements ObjectLayoutHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrateLayout(Panel $panel): Layout
    {
        return new Layout(
            $panel->getName(),
            $panel->getDatatype(),
            $panel->fieldtype,
            $panel->getType(),
            $panel->getLayout(),
            $panel->getRegion(),
            $panel->getTitle(),
            $panel->getWidth(),
            $panel->getHeight(),
            $panel->getCollapsible(),
            $panel->getCollapsed(),
            $panel->getBodyStyle(),
            $panel->getLocked(),
            $panel->getChildren(),
            $this->iconService->getIconForLayout($panel->getIcon()),
            $panel->getLabelAlign(),
            $panel->getLabelWidth(),
            $panel->getBorder()
        );
    }
}
