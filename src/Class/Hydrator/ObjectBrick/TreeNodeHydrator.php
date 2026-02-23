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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * @internal
 */
final readonly class TreeNodeHydrator implements TreeNodeHydratorInterface
{
    public function hydrate(Definition $definition): ObjectBrickTreeNode
    {
        return new ObjectBrickTreeNode(
            $definition->getKey() ?? '',
            $definition->getTitle() ?? '',
            new ElementIcon(ElementIconTypes::NAME->value, 'objectbricks'),
            $definition->getGroup(),
        );
    }
}
