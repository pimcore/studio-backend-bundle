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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
final class GroupLayoutHydrator implements GroupLayoutHydratorInterface
{
    public function hydrate(array $keys, GroupConfig $group): GroupLayout
    {
        return new GroupLayout(
            id: $group->getId(),
            name: $group->getName(),
            description: $group->getDescription(),
            keys: $keys,
        );
    }
}
