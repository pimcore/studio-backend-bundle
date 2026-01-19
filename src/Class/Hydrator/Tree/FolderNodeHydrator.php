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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\Tree;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;

/**
 * @internal
 */
final readonly class FolderNodeHydrator implements FolderNodeHydratorInterface
{
    private const string ICON_NAME = 'class_folder';

    /**
     * {@inheritdoc}
     */
    public function hydrate(string $groupName, array $children): ClassDefinitionTreeNodeFolder
    {
        return new ClassDefinitionTreeNodeFolder(
            'folder_' . $groupName,
            $groupName,
            $groupName,
            new ElementIcon(ElementIconTypes::NAME->value, self::ICON_NAME),
            null,
            $children
        );
    }
}
