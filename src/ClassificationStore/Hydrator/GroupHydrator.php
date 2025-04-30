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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Group;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
final class GroupHydrator implements GroupHydratorInterface
{
    public function hydrate(GroupConfig $data): Group
    {
        return new Group(
            id: $data->getId(),
            name: $data->getName(),
            description: $data->getDescription()
        );
    }
}
