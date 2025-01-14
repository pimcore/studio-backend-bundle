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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;

/**
 * @internal
 */
final class LayoutDefinitionHydrator implements LayoutDefinitionHydratorInterface
{
    public function hydrate(ObjectBrickDefinition $data): LayoutDefinition
    {
        $layout = $data->getLayoutDefinitions();

        return new LayoutDefinition(
            $data->getKey(),
            $layout->getDatatype(),
            $layout->getName(),
            $layout->getType(),
            $layout->getRegion(),
            $layout->getTitle(),
            $layout->getWidth(),
            $layout->getHeight(),
            $layout->getCollapsible(),
            $layout->getCollapsed(),
            $layout->getChildren(),
        );
    }
}
