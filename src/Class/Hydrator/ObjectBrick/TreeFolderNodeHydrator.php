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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;

/**
 * @internal
 */
final readonly class TreeFolderNodeHydrator implements TreeFolderNodeHydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(string $groupName, array $children): ObjectBrickTreeNodeFolder
    {
        return new ObjectBrickTreeNodeFolder(
            'group_' . $groupName,
            htmlspecialchars($groupName),
            new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            $groupName,
            $children,
        );
    }
}
